<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\Ai\EmbeddingService;
use App\Services\Ai\MetaClient;
use App\Services\Ai\RagService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Agentic task loop: plan → retrieve → draft → verify → revise, with a
 * scripted model (SQLite in memory, no network).
 */
final class AgentTaskTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('CREATE TABLE categorie (id INTEGER PRIMARY KEY, nom TEXT)');
        $this->pdo->exec('CREATE TABLE documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, filename TEXT, mime_type TEXT,
            categorie_id INTEGER NULL, supprime_le TEXT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
        $this->pdo->exec('CREATE TABLE document_chunks (
            id INTEGER PRIMARY KEY AUTOINCREMENT, document_id INTEGER NOT NULL, version_document_id INTEGER NULL,
            chunk_index INTEGER NOT NULL, content TEXT NOT NULL, embedding TEXT NOT NULL
        )');
    }

    /**
     * @param array<string, string> $script reply per call type (plan, verify, revise, draft)
     * @param list<string> $calls receives the type of every model call, in order
     */
    private function rag(array $script, array &$calls): RagService
    {
        $client = new class ($script, $calls) extends MetaClient {
            /** @param array<string, string> $script @param list<string> $calls */
            public function __construct(private array $script, private array &$calls)
            {
                parent::__construct([]);
            }

            public function embed(string $text): array
            {
                return [1.0, 0.0, 0.0];
            }

            public function chat(string $prompt, array $context = [], ?string $model = null, ?float $temperature = null): string
            {
                $system = (string) ($context[0]['content'] ?? '');
                $type = match (true) {
                    str_contains($system, 'You plan the research') => 'plan',
                    str_contains($system, 'strict fact-checker') => 'verify',
                    str_contains($system, 'REVISION:') => 'revise',
                    str_contains($system, 'traducteur') => 'translate',
                    default => 'draft',
                };
                $this->calls[] = $type;

                return $type === 'translate'
                    ? (string) preg_replace('/^QUESTION:.*\n\nEXTRAIT\(S\):\n/s', '', $prompt)
                    : ($this->script[$type] ?? '');
            }
        };

        $rag = new RagService($client, new EmbeddingService($client), $this->pdo);
        foreach (['Budget 2026' => 'The parish budget for the feast is 500 000 CFA francs.', 'Choir rules' => 'Rehearsals take place every Thursday at 6 pm.'] as $title => $text) {
            $this->pdo->exec('INSERT INTO documents (title) VALUES (' . $this->pdo->quote($title) . ')');
            $rag->indexDocument((int) $this->pdo->lastInsertId(), $text);
        }

        return $rag;
    }

    public function testAgentPlansVerifiesAndRevisesUnsupportedClaims(): void
    {
        $calls = [];
        $rag = $this->rag([
            'plan' => '```json' . "\n" . '{"queries": ["budget fête", "répétitions chorale"]}' . "\n```",
            'draft' => 'Note: the budget is 900 000 CFA francs [1]. Rehearsals are on Thursday [2]. Sources : [1], [2]',
            'verify' => '{"checked": 2, "unsupported": ["the budget is 900 000 CFA francs — extract [1] says 500 000"]}',
            'revise' => 'Note: the budget is 500 000 CFA francs [1]. Rehearsals are on Thursday [2]. Sources : [1], [2]',
        ], $calls);

        $result = $rag->task('Prepare a note on the feast budget and choir rehearsals');

        self::assertSame(['plan', 'draft', 'verify', 'revise'], array_slice($calls, 0, 4));
        self::assertStringContainsString('500 000', $result['draft']);
        self::assertStringNotContainsString('900 000', $result['draft']);

        $steps = array_column($result['steps'], null, 'step');
        self::assertSame(['Prepare a note on the feast budget and choir rehearsals', 'budget fête', 'répétitions chorale'], $steps['plan']['queries']);
        self::assertCount(3, $steps['retrieve']['queries']);
        self::assertSame(2, $steps['retrieve']['documents']);
        self::assertSame(['step' => 'verify', 'checked' => 2, 'unsupported' => 1, 'revised' => true, 'available' => true], $steps['verify']);
        self::assertCount(2, $result['sources']);
    }

    public function testSupportedDraftIsNotRevised(): void
    {
        $calls = [];
        $rag = $this->rag([
            'plan' => '{"queries": ["budget"]}',
            'draft' => 'The budget is 500 000 CFA francs [1]. Sources : [1]',
            'verify' => '{"checked": 1, "unsupported": []}',
        ], $calls);

        $result = $rag->task('Summarize the budget');

        self::assertNotContains('revise', $calls);
        self::assertFalse(array_column($result['steps'], null, 'step')['verify']['revised']);
    }

    public function testPlannerAndVerifierFailuresDegradeGracefully(): void
    {
        $calls = [];
        $rag = $this->rag([
            'plan' => 'not json at all',
            'draft' => 'The budget is 500 000 CFA francs [1]. Sources : [1]',
            'verify' => 'cannot comply',
        ], $calls);

        $result = $rag->task('Summarize the budget');
        $steps = array_column($result['steps'], null, 'step');

        self::assertSame(['Summarize the budget'], $steps['plan']['queries'], 'falls back to the instruction itself');
        self::assertFalse($steps['verify']['available']);
        self::assertStringContainsString('500 000', $result['draft']);
    }
}
