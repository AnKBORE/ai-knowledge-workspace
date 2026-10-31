<?php

declare(strict_types=1);

namespace App\Tests;

use App\Models\Correction;
use App\Services\Ai\Apprentissage;
use PDO;
use PHPUnit\Framework\TestCase;

final class ApprentissageTest extends TestCase
{
    private PDO $pdo;
    private Correction $corrections;
    private Apprentissage $apprentissage;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec(
            'CREATE TABLE correction_document (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                document_id INTEGER NOT NULL,
                version_document_id INTEGER NULL,
                langue TEXT NOT NULL DEFAULT "fr",
                texte_source TEXT NULL,
                texte_corrige TEXT NOT NULL,
                auteur_id INTEGER NULL,
                statut TEXT NOT NULL DEFAULT "valide",
                cree_le TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->corrections = new Correction($this->pdo);
        $this->apprentissage = new Apprentissage($this->corrections);
    }

    public function testNothingIsLearnedWithoutCorrections(): void
    {
        $this->assertSame('', $this->apprentissage->exemples('Le conseil diocésain a validé le rapport.', 'fr'));
        $this->assertSame(0, $this->apprentissage->count());
    }

    public function testOnlyCorrectionsSharingVocabularyAreInjected(): void
    {
        $this->corrections->create(
            1,
            'fr',
            'Le conseil diocésain a validé le rapport du vicariat pour le premier semestre.',
            'Le conseil a validé le rapport.'
        );
        $this->corrections->create(
            2,
            'fr',
            'La météo de Ouagadougou annonce une saison des pluies tardive cette année.',
            'La météo annonce des pluies.'
        );

        $block = $this->apprentissage->exemples('Le vicariat a transmis le rapport du conseil diocésain.', 'fr');

        $this->assertStringContainsString('Validated phrasings', $block);
        $this->assertStringContainsString('vicariat', $block);
        $this->assertStringNotContainsString('météo', $block);
    }

    public function testRareTermsOutweighGenericOnes(): void
    {
        // "projet" is common to both corrections, "catéchiste" is rare.
        $this->corrections->create(1, 'fr', 'Le projet avance correctement pour cette année.', null);
        $this->corrections->create(2, 'fr', 'Le catéchiste a présenté le projet aux paroisses.', null);

        $block = $this->apprentissage->exemples('Le catéchiste présente le projet.', 'fr');

        $this->assertStringContainsString('catéchiste', $block);
    }

    public function testLanguageIsolation(): void
    {
        $this->corrections->create(1, 'mo', 'La yĩnga, m Amerika rãmba, da sok ye.', null);

        $this->assertSame('', $this->apprentissage->exemples('Le conseil diocésain a validé le rapport.', 'fr'));
        $this->assertStringContainsString('rãmba', $this->apprentissage->exemples('La yĩnga, m Amerika rãmba.', 'mo'));
    }

    public function testExampleCountIsCapped(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->corrections->create(
                $i,
                'fr',
                'Le conseil diocésain numéro ' . $i . ' a validé le rapport du vicariat.',
                null
            );
        }

        $block = $this->apprentissage->exemples('Le conseil diocésain a validé le rapport du vicariat.', 'fr', 3);

        // At most three examples, so at most three "- " lines after the header.
        $this->assertLessThanOrEqual(3, substr_count($block, "\n- "));
    }

    public function testDeletedCorrectionStopsFeedingThePrompt(): void
    {
        $id = $this->corrections->create(
            1,
            'fr',
            'Le conseil diocésain a validé le rapport du vicariat.',
            null
        );

        $this->assertNotSame('', $this->apprentissage->exemples('Le conseil diocésain a validé le rapport.', 'fr'));

        $this->pdo->prepare('DELETE FROM correction_document WHERE id = :id')->execute(['id' => $id]);

        $this->assertSame('', $this->apprentissage->exemples('Le conseil diocésain a validé le rapport.', 'fr'));
    }
}
