<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Remove direct identifiers from a text before it is indexed or sent to the
 * inference API (defence in depth for sensitive documents).
 *
 * Detects: e-mail addresses, IBAN, payment-card numbers and phone numbers.
 * Enabled with `ANONYMISATION=1`.
 */
final class Anonymiseur
{
    public function anonymiser(string $texte): string
    {
        // E-mail addresses.
        $texte = (string) preg_replace(
            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
            '[EMAIL]',
            $texte
        );

        // IBAN (2 letters + 2 digits + up to 7 groups of 4).
        $texte = (string) preg_replace(
            '/\b[A-Z]{2}\d{2}(?:[ ]?\d{4}){3,7}\b/',
            '[IBAN]',
            $texte
        );

        // Payment-card numbers (13 to 16 digits, optional separators).
        $texte = (string) preg_replace(
            '/\b(?:\d[ \-]*?){13,16}\b/',
            '[CARTE]',
            $texte
        );

        // International phone numbers (+ or 00 prefix).
        $texte = (string) preg_replace(
            '/(?:\+|00)\d{1,3}[\s.\-]?(?:\d[\s.\-]?){6,12}\d/',
            '[TELEPHONE]',
            $texte
        );

        // National phone numbers (10 digits starting with 0).
        $texte = (string) preg_replace(
            '/\b0[1-9](?:[\s.\-]?\d{2}){4}\b/',
            '[TELEPHONE]',
            $texte
        );

        return $texte;
    }
}
