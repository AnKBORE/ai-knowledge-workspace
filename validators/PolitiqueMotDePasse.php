<?php

declare(strict_types=1);

namespace App\Validators;

/**
 * Password policy and hashing.
 *
 * Minimum 12 characters, with at least one uppercase, one lowercase and one
 * digit. Hashing uses PHP's default (bcrypt/argon2id depending on the build).
 */
final class PolitiqueMotDePasse
{
    public const MIN_LENGTH = 12;

    /** @return list<string> */
    public static function validate(string $motDePasse): array
    {
        $errors = [];

        if (mb_strlen($motDePasse) < self::MIN_LENGTH) {
            $errors[] = 'Le mot de passe doit contenir au moins ' . self::MIN_LENGTH . ' caractères.';
        }
        if (preg_match('/[A-Z]/', $motDePasse) !== 1) {
            $errors[] = 'Le mot de passe doit contenir une majuscule.';
        }
        if (preg_match('/[a-z]/', $motDePasse) !== 1) {
            $errors[] = 'Le mot de passe doit contenir une minuscule.';
        }
        if (preg_match('/[0-9]/', $motDePasse) !== 1) {
            $errors[] = 'Le mot de passe doit contenir un chiffre.';
        }

        return $errors;
    }

    public static function hash(string $motDePasse): string
    {
        return password_hash($motDePasse, PASSWORD_DEFAULT);
    }

    public static function verify(string $motDePasse, string $hash): bool
    {
        return password_verify($motDePasse, $hash);
    }
}
