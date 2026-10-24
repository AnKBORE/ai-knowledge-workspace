<?php

declare(strict_types=1);

/**
 * Front controller — point d'entrée unique de l'application.
 *
 * En production, le document root du serveur web doit pointer sur `public/`.
 * Le routage, la configuration et la sécurité sont chargés par le noyau (core/).
 */

// Charge l'autoloader de Composer.
require __DIR__ . '/../vendor/autoload.php';

// Sécurité : en-têtes de base (à compléter par core/SecurityHeaders.php).
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

// Point de démarrage du noyau applicatif (à brancher sur le socle existant).
echo 'AI Knowledge Workspace — front controller ready.';
