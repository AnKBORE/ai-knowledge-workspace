<?php

declare(strict_types=1);

/**
 * Contrôle du rendu : le texte visible des pages anglaises ne doit contenir
 * aucune chaîne française.
 *
 * Usage : php tools/check_english_ui.php [base-url]
 * Code de sortie 0 si aucune chaîne française n'est trouvée, 1 sinon.
 */

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$email = getenv('SMOKE_ADMIN_EMAIL') ?: 'anatolekbore@gmail.com';
$password = getenv('SMOKE_ADMIN_PASSWORD') ?: 'Hackathon2026!';

$jar = tempnam(sys_get_temp_dir(), 'ui');

function http(string $url, ?array $post = null, string $jar = '', bool $follow = true): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_TIMEOUT => 20,
    ]);

    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $body = (string) curl_exec($ch);
    curl_close($ch);

    return $body;
}

// Connexion
$login = http($base . '/login', null, $jar);

if (!preg_match('/name="_csrf" value="([^"]+)"/', $login, $m)) {
    fwrite(STDERR, "Formulaire de connexion introuvable.\n");
    exit(2);
}

http($base . '/login', ['_csrf' => $m[1], 'courriel' => $email, 'mot_de_passe' => $password], $jar);

$mots = [
    'connexion', 'déconnexion', 'rechercher', 'chargement', 'aucun résultat', 'aucune donnée',
    'affichage de', 'afficher ', 'supprimer', 'enregistré', 'erreur', 'veuillez', 'mot de passe',
    'première page', 'dernière page', 'page suivante', 'page précédente', 'résultat',
    'votre ', 'vos ', 'sélectionner', 'élément', 'caractère', 'français',
];

$pages = ['/', '/tache', '/traduire', '/ajouter', '/documents', '/categories', '/journal', '/mon-profil', '/administration', '/espaces'];
$total = 0;

echo "\n=== CONTRÔLE DE L'INTERFACE ANGLAISE ===\n\n";

foreach ($pages as $path) {
    $html = http($base . $path, null, $jar);
    $lang = preg_match('/<html lang="([^"]+)"/', $html, $lm) ? $lm[1] : '?';

    // Texte visible : on retire scripts, styles, attributs.
    $texte = preg_replace('~<script\b.*?</script>~is', ' ', $html);
    $texte = preg_replace('~<style\b.*?</style>~is', ' ', $texte);
    $texte = preg_replace('~<[^>]+>~', ' ', $texte);
    $texte = html_entity_decode($texte, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $texte = mb_strtolower(preg_replace('/\s+/', ' ', $texte));

    $trouves = [];

    foreach ($mots as $mot) {
        if (str_contains($texte, $mot)) {
            $trouves[] = $mot;
        }
    }

    // Les noms de langues sont légitimes dans un sélecteur.
    $trouves = array_values(array_diff($trouves, ['français']));

    $ok = $lang === 'en' && $trouves === [];
    $total += $ok ? 0 : 1;

    printf(
        "  %s %-16s lang=%-3s %s\n",
        $ok ? 'OK  ' : 'KO  ',
        $path,
        $lang,
        $trouves === [] ? '' : 'français : ' . implode(', ', $trouves)
    );
}

echo "\n" . ($total === 0 ? "Aucune page anglaise ne contient de texte français.\n\n" : $total . " page(s) à corriger.\n\n");

@unlink($jar);

exit($total === 0 ? 0 : 1);
