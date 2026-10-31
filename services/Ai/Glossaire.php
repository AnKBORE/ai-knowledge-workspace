<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Domain glossary injected into translation prompts so that professional terms
 * stay consistent from one slice to the next (live transcription included).
 */
final class Glossaire
{
    /**
     * French term => [English, Mooré hint (empty when unknown)].
     *
     * Only terms whose translation is certain are listed: the model keeps the
     * Mooré orthography from MooreReference for the rest.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const TERMES = [
        'catéchiste' => ['catechist', ''],
        'catéchumène' => ['catechumen', ''],
        'paroisse' => ['parish', ''],
        'diocèse' => ['diocese', ''],
        'sacrement' => ['sacrament', ''],
        'baptême' => ['baptism', ''],
        'suivi-évaluation' => ['monitoring and evaluation', ''],
        'renforcement des capacités' => ['capacity building', ''],
        'rapport d’activités' => ['activity report', ''],
        'compte rendu' => ['minutes / report', ''],
        'procès-verbal' => ['minutes', ''],
        'ordre du jour' => ['agenda', ''],
        'atelier' => ['workshop', ''],
        'formation' => ['training', ''],
        'bénéficiaire' => ['beneficiary', ''],
        'organisme' => ['organization', ''],
        'conseil diocésain' => ['diocesan council', ''],
        'vicariat' => ['vicariate', ''],
        'aumônerie' => ['chaplaincy', ''],
        'séminariste' => ['seminarian', ''],
    ];

    /** Build a compact "use these exact terms" block for a prompt. */
    public static function promptFor(string $to): string
    {
        $lines = [];
        foreach (self::TERMES as $fr => [$en]) {
            $target = $to === 'en' ? $en : $fr;
            $lines[] = '- ' . $fr . ' → ' . $target;
        }

        return "\n\nDomain glossary (use these exact terms, keep them consistent):\n" . implode("\n", $lines);
    }
}
