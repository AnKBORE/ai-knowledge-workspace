<?php

declare(strict_types=1);

namespace App\Tests;

use App\Validators\PolitiqueMotDePasse;
use PHPUnit\Framework\TestCase;

final class PolitiqueMotDePasseTest extends TestCase
{
    public function testAcceptsStrongPassword(): void
    {
        $this->assertSame([], PolitiqueMotDePasse::validate('Hackathon2026!'));
    }

    public function testRejectsShortPassword(): void
    {
        $this->assertNotEmpty(PolitiqueMotDePasse::validate('Short1'));
    }

    public function testRejectsPasswordWithoutUppercase(): void
    {
        $this->assertNotEmpty(PolitiqueMotDePasse::validate('hackathon2026'));
    }

    public function testRejectsPasswordWithoutDigit(): void
    {
        $this->assertNotEmpty(PolitiqueMotDePasse::validate('HackathonOnly'));
    }

    public function testHashAndVerify(): void
    {
        $hash = PolitiqueMotDePasse::hash('Hackathon2026!');

        $this->assertNotSame('Hackathon2026!', $hash);
        $this->assertTrue(PolitiqueMotDePasse::verify('Hackathon2026!', $hash));
        $this->assertFalse(PolitiqueMotDePasse::verify('wrong', $hash));
    }
}
