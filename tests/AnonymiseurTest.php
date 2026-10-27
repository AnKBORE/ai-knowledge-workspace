<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\Anonymiseur;
use PHPUnit\Framework\TestCase;

final class AnonymiseurTest extends TestCase
{
    public function testRedactsEmailPhoneIbanAndCard(): void
    {
        $texte = 'Contact : jean.dupont@example.com, +226 70 12 34 56, '
            . 'IBAN BF42 1234 5678 9012 3456 7890, carte 4111 1111 1111 1111.';

        $sortie = (new Anonymiseur())->anonymiser($texte);

        $this->assertStringContainsString('[EMAIL]', $sortie);
        $this->assertStringContainsString('[TELEPHONE]', $sortie);
        $this->assertStringContainsString('[IBAN]', $sortie);
        $this->assertStringContainsString('[CARTE]', $sortie);
        $this->assertStringNotContainsString('jean.dupont@example.com', $sortie);
        $this->assertStringNotContainsString('4111 1111 1111 1111', $sortie);
    }

    public function testKeepsOrdinaryText(): void
    {
        $texte = 'Le rapport annuel 2025 présente les activités des paroisses.';

        $this->assertSame($texte, (new Anonymiseur())->anonymiser($texte));
    }
}
