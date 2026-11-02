# Registre des problèmes & résolutions

🇬🇧 English version: [REGISTRE_PROBLEMES.md](REGISTRE_PROBLEMES.md)

> **Document vivant** : suivi de tous les blocages et écarts du projet.
> Chaque problème reçoit un **ID**. Quand il est résolu :
> 1. passer le **Statut** à ✅,
> 2. renseigner **Résolution** (date + note),
> 3. ajouter une ligne dans le **Journal des résolutions**.
>
> C'est la **source de vérité** pour savoir « où on en est ».

## Légende

| Symbole | Signification |
|---|---|
| 🔴 | Ouvert (bloquant ou à faire) |
| 🟡 | En cours |
| ✅ | Résolu |

---

## 1. Problèmes ouverts (à résoudre)

| ID | Problème | Impact | Priorité | Statut | Résolution |
|---|---|---|---|---|---|
| **O4** | Endpoints `transcription` (voix) et `vision` (scan) non confirmés par la doc Meta | Le multimodal voix/scan n'est pas testé | Moyenne | 🔴 | — |
| **O9** | **Vidéo de démonstration** (3 min) non enregistrée | Élément central de notation | 🔥 Critique | 🟡 | Script + checklist prêts (`docs/DEMO_VIDEO_ET_SOUMISSION.md`) |
| **O10** | **Soumission** au hackathon non faite | Sans soumission => pas de prix | 🔥 Critique | 🟡 | Checklist prête ; formulaire à remplir |
| **O11** | `php` absent du PATH Windows | Commandes `php …` en erreur | Faible | 🟡 | Contournement : `serve.bat` / chemin complet |

---

## 2. Problèmes résolus (historique)

| ID | Problème | Résolution | Date |
|---|---|---|---|
| **R1** | Versions PHP/MySQL imprécises | Épinglées sur **PHP 8.4.27 / MySQL 8.4.13** | 2026-10-24 |
| **R2** | Pas de base ni de schéma | Base `ai_knowledge_workspace` + 5 tables via `tools/migrate.php` | 2026-10-26 |
| **R3** | Pas de pipeline RAG | `RagService` (chunk, index, retrieve, query) implémenté | 2026-10-26 |
| **R4** | Endpoint API supposé (`/v1/chat/completions`) | Aligné sur **`POST /v1/responses`**, modèle `muse-wonder-1.0` | 2026-10-26 |
| **R5** | Dépendance à un endpoint d'embeddings incertain | **Embedding local (hashing)** par défaut, `META_EMBEDDING_MODE=api` en option | 2026-10-26 |
| **R6** | Pas d'interface | UI web (design du socle) : upload + chat + liste | 2026-10-26 |
| **R7** | Upload PDF fonctionnel | `smalot/pdfparser` + chunking + stockage (ex. « Offre technique », 150 chunks) | 2026-10-26 |
| **R8** | Pas de tests | **19 tests PHPUnit** verts | 2026-10-26 |
| **R9** | Commandes manuelles complexes | `setup.ps1` / `setup.bat` / `serve.bat` | 2026-10-26 |
| **R10** | Erreurs PSR-4 (`services/ai` vs `Ai`) | Dossier renommé `services/Ai`, autoload régénéré | 2026-10-26 |
| **R11** | Pas de feuille de route | `docs/GUIDE_DE_REALISATION.md` (9 phases) | 2026-10-26 |
| **R12** | Erreurs API opaques | Mode **`APP_DEBUG=1`** → message d'erreur réel | 2026-10-26 |
| **O3** | Team ID / formulaire de crédits | Formulaire de crédits **soumis** (Team ID renseigné) | 2026-10-26 |
| **O8** | Dépôt GitHub privé | Dépôt passé en **public** | 2026-10-26 |
| **O2** | Crédits $150 non visibles | **Crédits actifs** : « Free credits in use: $150,00 » | 2026-10-26 |
| **O1** | Clé API / chat | Clé **Baseten** configurée (Plan B) ; réponse `[OK]` | 2026-10-26 |
| **O7** | Document client indexé | Supprimé de la base ; démo sur document neutre | 2026-10-26 |
| **O12** | Test réel de l'API | **Pipeline RAG complet validé** (retrieve + Muse Spark Open) | 2026-10-26 |
| **O5** | Sources citées absentes | `RagService::answer()` renvoie les **sources** ; affichées dans l'UI | 2026-10-26 |
| **O6** | Tâches agentiques | `RagService::task()` + section « Tâche agentique » dans l'UI | 2026-10-26 |
| **R13** | Auth / RBAC / versions / audit | **Phase 1 livrée** : `utilisateur`, `Session`, `Csrf`, `Auth`, `AuthentificationService`, login/logout, 30 tests verts | 2026-10-26 |
| **R14** | RBAC (espaces & adhésions) | **Phase 2 livrée** : `espace`, `adhesion`, `historique_adhesions`, `journal`, `PermissionService` (global/local, héritage, espaces privés), 37 tests verts | 2026-10-26 |
| **R15** | Documents & versions | **Phase 3 livrée** : `document` enrichi, `version_document` (SHA-256, numéros uniques), `DocumentService` (stockage hors web), upload live validé, 41 tests verts | 2026-10-26 |
| **R16** | Journalisation (audit) | **Phase 7 livrée** : `JournalService` append-only + instrumentation (connexion, déconnexion, dépôt, question), journal live validé, 45 tests verts | 2026-10-26 |
| **R17** | Résumé automatique | **Livré** : `RagService::summarize()` + `/api/summarize.php` + bouton « Résumer » dans l'UI, résumé live validé (sources incluses), 47 tests verts | 2026-10-26 |
| **R18** | Crédits Baseten (partenaires) | **Demande soumise** (100 $ + 2M TPM) ; sélection par cas d'usage, réponse par e-mail | 2026-10-26 |
| **R19** | RBAC effectif, documents rattachés, versions, suppression | **Livré** : RBAC appliqué aux 4 endpoints (200 responsable / 403 membre sans adhésion), `espace_id` renseigné, badge versions, suppression + journal, 52 tests | 2026-10-26 |
| **R20** | Recherche sémantique faible | **Livré** : recherche **hybride** (cosinus 60 % + recouvrement lexical 40 %), `search()` exposé, test de classement, 53 tests | 2026-10-26 |
| **R21** | Multimodal non démontré | **Livré et testé** : **vision** via Muse Spark Open (`image_url`, OCR « BONJOUR LE MONDE ») + **audio** via `audio_url` (discours de JFK transcrit) ; upload image de bout en bout validé | 2026-10-26 |
| **R22** | Audio limité à 5 min | **Livré** : découpage interne en chunks de 5 min (WAV/MP3, **streaming**), limite app **10 min** avec message clair, timeout/mémoire augmentés, 55 tests | 2026-10-26 |

---

## 3. Journal des résolutions

> Une ligne par problème résolu (ordre chronologique). C'est ici qu'on « notifie » une résolution.

| Date | ID | Notification |
|---|---|---|
| 2026-10-24 | R1 | Versions sécurisées PHP 8.4.27 / MySQL 8.4.13 intégrées (docs + code). |
| 2026-10-26 | R2–R12 | Socle, base, RAG, API réelle, UI, tests, scripts et guide livrés. |
| 2026-10-26 | — | **Constat** : l'upload fonctionne (150 chunks) ; le chat échoue car `META_MODEL_API_KEY` vide (**O1**). |
| 2026-10-26 | O3 | Formulaire de crédits **soumis** (Team ID renseigné). |
| 2026-10-26 | O8 | Dépôt GitHub passé en **public**. |
| 2026-10-26 | O2 | Crédits $150 activés (« Free credits in use: $150,00 »). |
| 2026-10-26 | O1 | Clé Baseten configurée (Plan B) ; appel test `[OK]`. |
| 2026-10-26 | O7 | Document client supprimé ; démo sur `docs/sample-report.txt`. |
| 2026-10-26 | O12 | Pipeline RAG complet validé (réponse sourcée sur le rapport neutre). |
| 2026-10-26 | O5 | Sources citées affichées sous la réponse. |
| 2026-10-26 | O6 | Tâche agentique (« Générer un brouillon ») fonctionnelle. |
| 2026-10-26 | R13 | Phase 1 (auth, CSRF, anti-brute-force) livrée ; 30 tests verts. |
| 2026-10-26 | R14 | Phase 2 (espaces, adhésions, PermissionService) livrée ; 37 tests verts. |
| 2026-10-26 | R15 | Phase 3 (documents versionnés, SHA-256, stockage hors web) livrée ; 41 tests verts. |
| 2026-10-26 | R16 | Phase 7 (journal d'audit append-only) livrée ; 45 tests verts. |
| 2026-10-26 | R17 | Résumé automatique livré (sources incluses) ; 47 tests verts. |
| 2026-10-26 | R18 | Demande de crédits Baseten soumise (100 $ + 2M TPM) ; réponse par e-mail si sélectionné. |
| 2026-10-26 | R19 | RBAC appliqué aux endpoints (403 vérifié) ; documents rattachés à un espace ; versions visibles ; suppression de document. |
| 2026-10-26 | R20 | Recherche hybride (sémantique + lexicale) livrée ; bruit de retrieval supprimé ; 53 tests verts. |
| 2026-10-26 | R21 | Vision via Muse Spark Open validée en live (OCR) ; audio (`audio_url`) validé en live (discours de JFK) ; upload image de bout en bout. |
| 2026-10-26 | R22 | Audio jusqu'à 10 min (découpage streaming en chunks de 5 min) ; message clair au-delà de 10 min. |

---

## 4. Convention de notification (à respecter)

Quand un problème est résolu :

1. Dans le tableau **« Problèmes ouverts »** : passer le **Statut** à **✅** et remplir **Résolution** (date + note courte).
2. **Déplacer** la ligne vers **« Problèmes résolus »** (ou la marquer ✅).
3. Ajouter une ligne au **Journal des résolutions** :
   ```
   | AAAA-MM-JJ | O<num> | <ce qui a été fait, en une phrase> |
   ```
4. Mettre à jour la **checklist finale** du guide (`GUIDE_DE_REALISATION.md`).
5. Si applicable, mettre à jour le **README** (si le problème concernait la doc).

> Règle d'or : **aucun problème ne disparaît sans trace** — il est toujours résolu + journalisé.

---

## 5. Incidents de la phase 5 — corrections et RAG

| # | Symptôme observé | Cause réelle | Résolution | État |
|---|---|---|---|---|
| P-20 | Réponse « Meeting6 ne contient pas de mooré » alors qu'une correction existe | `Illegal mix of collations` entre `correction_document.langue` (`utf8mb4_unicode_ci`) et `documents.langue` (`utf8mb4_0900_ai_ci`) ; l'exception était avalée par un `catch` | préférence de langue décidée **en PHP**, plus aucune comparaison SQL | ✅ |
| P-21 | Correction jamais appliquée en question / tâche agentique | même cause que P-20, mais silencieuse : `catch (Throwable) { return $chunks; }` | `catch` réduit au strict nécessaire + requête corrigée | ✅ |
| P-22 | Réponse tronquée en pleine phrase, sans source | plafond `max_tokens` à 1 200 (réglé pour la latence de traduction) | plafond 3 000 ; les traductions s'arrêtent d'elles-mêmes | ✅ |
| P-23 | Panneau SOURCES vide malgré une réponse ancrée | la réponse tronquée ne citait aucun `[n]`, et les sources sont dérivées des citations | `sourcesFor()` : repli sur les 3 premiers extraits | ✅ |
| P-24 | Badge « version corrigée » invisible malgré un correctif déployé | pages HTML non versionnées → ancien `?v=` → ancien JS en cache | `Cache-Control: no-store` sur toutes les pages | ✅ |
| P-25 | Correction `.docx` refusée | liste blanche limitée à `.txt/.md/.csv` + libellé anglais non mis à jour | `.pdf` et `.docx` acceptés, extraction PDF/DOCX, libellés FR **et** EN corrigés | ✅ |
| P-26 | Téléchargement d'une correction en `.txt` au lieu du format d'origine | le fichier était jeté, seul le texte conservé | fichier copié dans `storage`, servi à l'octet près (SHA-256 identique) | ✅ |
| P-27 | Enregistrement de réunion muet pendant 47 s | deadlock : une tranche rejetée consommait un numéro de séquence jamais rempli | séquence allouée **au moment de l'envoi** | ✅ |
| P-28 | Mots manquants en transcription live | seuil calculé sur le **pic** : une voix calme tombait sous 0,012 et 6 s étaient jetées | seuil **RMS** 0,0015 | ✅ |
| P-29 | « Are you going today? » rendu « Do you want it today? » | la forme fléchie `rabda` ne matchait pas l'entrée `rabde` du lexique | recherche par **racine** (4 lettres) | ✅ |
| P-30 | Corrections de test laissées en base, devenues prioritaires | vérifications d'API effectuées sans nettoyage | suppression des corrections de test, conservation de la correction réelle | ✅ |

---

## 6. Incidents de la phase 6 — précision documentaire et livrable

| # | Symptôme observé | Cause réelle | Résolution | État |
|---|---|---|---|---|
| P-31 | « Parle-moi de Meeting6 » décrivait un texte mooré alors que le document est un audio français | `preferCorrections()` **remplaçait** tous les extraits par le corrigé : le document perdait son identité | carte d'identité (nature, langue d'origine, catégorie, date, fichier) **plus** les versions corrigées | ✅ |
| P-32 | « Traduis-le » impossible ; la réponse affirmait que le transcript français manquait | le texte original était supprimé du contexte | troisième extrait « Texte original (FR) » conservé | ✅ |
| P-33 | « Traduit le » produisait une description au lieu d'une traduction | aucune détection de l'intention de traduction | `wantsTranslation()` + règle 4h (produire la traduction) | ✅ |
| P-34 | Discours « à base de Meeting6 » citant la matrice d'un autre projet | le plan cherchait dans **tout** le corpus ; aucune contrainte de périmètre | `documentScope()` + `document_ids` + étape `scope` + note `SCOPE` | ✅ |
| P-35 | Les sources d'une question de liste ne portaient pas le badge « corrigé » | le chemin **catalogue** contournait `preferCorrections()` | `annotateCorrections()` : correction déclarée sans écraser les métadonnées | ✅ |
| P-36 | « en mooré » répondait **en** mooré au lieu de présenter le contenu mooré | confusion entre langue du contenu et langue de réponse | règle 4g + `requestedLanguage()` + tri de l'extrait demandé en tête | ✅ |
| P-37 | Le nettoyage laissait « Référence documentaire », « Note de méthode », `[Proposition]` | le nettoyage travaillait sur le **HTML** : les titres en `<strong>` échappaient aux motifs | nettoyage sur le **texte**, puis rendu reconstruit | ✅ |
| P-38 | La mention « Sources removed… » apparaissait dans le texte et partait dans le DOCX | la mention était ajoutée **dans** `#result-content` | insérée en **frère** du contenu | ✅ |
| P-39 | Dates au format ISO dans les phrases et dans les libellés | libellés et dates produits en dur, sans langue | `t()` + `formatDate()` + règle 4a (dates dans la langue de la réponse) | ✅ |
| P-40 | Un correctif invisible malgré un déploiement | onglet exécutant l'ancien JavaScript | pages en `no-store` + `Ctrl+F5` documenté dans le protocole de démo | ✅ |
| P-41 | Un membre pouvait lister, et télécharger par identifiant, des documents d'un espace dont il n'est pas membre | la liste (page + API) et `download_document` ne vérifiaient que la permission **globale** `document.consulter`, jamais le document lui-même | `PermissionService::documentsVisibles()` partagée avec le moteur de récupération ; liste, recherche et téléchargement sont cloisonnés, un document inaccessible renvoie 404 (sans révéler son existence) | ✅ |
| P-42 | Un simple membre pouvait ouvrir la page du journal d'audit et lire toutes les entrées (adresses IP, actions) | la page `/journal` ne vérifiait jamais `journal.lire`, contrairement à son export CSV et à sa purge | 403 et message clair pour qui n'a pas la permission, refus journalisé ; vérifié : membre 403 / 0 ligne, administrateur 200 / 58 lignes | ✅ |
| P-43 | L'affirmation « le Super Admin est protégé » était déclarée mais jamais appliquée | `compte_super_protege()` n'avait aucun appelant, et aucun endpoint ne permettait de suspendre ou supprimer un compte : la garantie était théorique et non testée | invariants déplacés dans le modèle (`changerRole`, `changerStatut`, `supprimerLogique` refusent le compte protégé), endpoints suspension/suppression/rôle ajoutés derrière le mot de passe, amorçage auto-réparateur avec `--reset`, 2 tests | ✅ |
| P-44 | Les dépôts étaient cassés : chaque envoi échouait sur un TypeError | un artefact de génération avait écrit des séquences d'échappement littérales dans un commentaire //, ce qui commentait l'affectation à $result et laissait finalize() lire une variable indéfinie | commentaire rétabli, $result affecté avant usage, l'argument de confidentialité atteint désormais le modèle ; un balayage des séquences littérales dans les sources PHP passe | ✅ |
| P-45 | La suppression d'un document ne faisait plus rien | la liaison des boutons était posée **en fin** de `workspace.js` : une erreur d'initialisation antérieure l'empêchait d'être atteinte, sans message | délégation sur `document` + liaison précoce et idempotente ; le drapeau est déclaré **avant** l'appel, sinon il était réinitialisé et la suppression partait deux fois | ✅ |
| P-46 | Le tableau des comptes affichait un avertissement DataTables | mon bloc d'actions avait été inséré dans la **cellule du nom**, avalant e-mail, rôle et statut (6 en-têtes, 2 cellules) | ligne reconstruite, 6 colonnes pour 6 en-têtes, vérifié sur les trois inventaires | ✅ |
| P-47 | La page `/categories` était lisible par un simple lecteur | aucune garde sur la page, alors que les endpoints exigeaient `document.deposer` / `document.supprimer` | 403 + message dédié pour qui ne peut ni déposer ni supprimer, refus journalisé | ✅ |
| P-48 | Trois endpoints répondaient par une redirection au lieu d'un refus | `$respond` ne lisait que `X-Requested-With` : un client JSON recevait un 302 illisible par `fetch` | négociation de contenu : 401 JSON pour un client JSON, redirection pour un formulaire natif | ✅ |
| P-49 | Un script inline du journal était bloqué par la CSP | substitution de motif annoncée mais non appliquée : le nonce manquait sur la balise `<script>` | nonce posé ligne à ligne, vérifié page par page (scripts inline = nonces) | ✅ |
| P-50 | `--transfer` laissait croire à un ajout | le nom disait « transfert » pour un déplacement, puis a changé de sens avec le modèle multiple | `--grant` / `--revoke`, `--transfer` conservé en alias ; aide `--help` décrivant le modèle | ✅ |
| P-51 | Le refus « Adresse e-mail invalide » ne dit pas ce qui est attendu : membre.recette@local est rejeté sans explication (ilter_var exige un point dans le domaine) | message générique, aucune indication de format | message explicite avec un exemple valide, dans l'API et dans le profil | ✅ |
| P-52 | Le compteur de documents par espace affichait « Général — 0 document » alors que Général contenait les deux documents | le compte lisait `documents.espace_id` (colonne primaire) au lieu de la table de liaison `document_espace` | compte calculé sur l'union pivot + colonne primaire, vérifié : 2 et 2 | ✅ |
| P-53 | Un scan sans couche de texte était refusé avec « Le fichier est vide ou illisible » | message générique, aucune indication sur la cause ni sur la suite | message explicite : aucun texte extrait, vérifier la page, l'OCR ne lit pas encore le mooré | ✅ |
| P-54 | Un PDF scanné dont les images ne sont pas JPEG était refusé avec « fichier vide ou illisible », sans que l'OCR soit seulement tenté | le filtre ne retenait que les flux JPEG (`FF D8 FF`) ; les bitmaps Flate étaient ignorés en silence | conversion des bitmaps bruts 8 bits (gris ou RVB) en PNG avant l'appel, refus explicite pour les encodages inhabituels — vérifié : un scan mooré extrait 1 093 caractères en 8,6 s | ✅ |
| P-55 | Les appels d'OCR (vision) n'apparaissaient pas dans le journal des appels au modèle | ce chemin passe par `curl_multi` et contourne `request()` | journalisation ajoutée sur le chemin parallèle, avec l'URL réelle et la durée — vérifié : deux lignes à 200 en 8,5 s | ✅ |
| P-56 | La langue d'un document mooré était enregistrée `fr` alors que le corps est en mooré | aucune détection n'existait : la colonne prenait sa valeur par défaut `fr` | détection à l'ingestion, mooré d'abord (diacritiques ɛ ɔ ã ẽ ĩ õ ũ), puis français/anglais par mots-outils — vérifié : mooré → `mo`, français → `fr`, anglais → `en` | ✅ |
| P-57 | Les dépôts en échec laissaient la colonne `resultat` **vide** au journal | la colonne est un enum `succes / refus / erreur` et le worker écrivait `echec`, valeur refusée : MySQL stockait une chaîne vide | le worker écrit `erreur`, et `JournalService` normalise toute valeur hors enum vers `erreur` — vérifié : demander `echec` stocke `erreur` | ✅ |
| P-58 | L'espace affiché en haut ne limitait pas la recherche : une question posée dans « Chantier test » renvoyait une réponse tirée d'un document de « Général » | la récupération ne recevait que l'identifiant utilisateur, jamais l'espace actif | `RagService` reçoit l'espace, filtre les documents sur la liaison, et `ask.php` transmet l'espace de session — vérifié : même question, « Général » cite l'audio (10 millions), « Chantier test » cite sa propre note (4 200 000) | ✅ |
| P-59 | Une question sur le budget de l'école, posée dans un espace qui ne contient pas le document, recevait une réponse **confiante mais fausse** sur un autre chantier | la récupération renvoyait toujours les meilleurs extraits, même très peu pertinents, et le modèle répondait quand même | seuil de pertinence mesuré (0,25) : au-dessous, refus explicite « je ne trouve pas dans cet espace » ; au-dessus, réponse sourcée — vérifié sur quatre cas : 0,72 et 0,53 répondent, 0,166 et 0,157 refusent | ✅ |
| P-60 | Le bouton « Résumer » de la liste des documents ne faisait rien | les lignes rendues par PHP portent `.doc-confirm`, et `bindDocConfirm()` n'était appelé qu'après un rafraîchissement dynamique : au chargement, aucun gestionnaire | liaison déplacée au démarrage, à côté de celle du bouton supprimer — le bouton ouvre la modale de confirmation puis le résumé | ✅ |
| P-61 | Le bouton « Rejouer » du modal de résultat ne faisait souvent rien | il n'était actif que si une question ou une tâche avait été mémorisée ; après un résumé, un nettoyage ou une traduction, `lastRequest` valait `null` et le clic sortait sans bruit | l'action mémorise désormais son **point d'entrée** (résumé inclus), et le bouton est **désactivé** quand rien n'est rejouable — l'infobulle dit « Aucune requête à rejouer » | ✅ |
| P-62 | Les réponses s'affichaient **entièrement en gras**, et la traduction montrait des `**` en clair | le rendu convertissait tout `**…**` en `<strong>`, or les modèles entourent des phrases entières ; le panneau de traduction est un textarea, où les marqueurs restent visibles | le gras ne s'applique qu'aux segments de **60 caractères au plus** ; au-delà, les marqueurs sont retirés ; la traduction nettoie les `**` — vérifié sur trois cas | ✅ |
| P-63 | Les réponses et les résumés affichaient leurs puces en `*` littéraux : le résultat ressemblait à un brouillon | le rendu ne connaissait que `**gras**` et les retours à la ligne, pas les listes | rendu dédié : puces `*`/`-` en `<ul>`, numéros `1.` en `<ol>`, titres en gras, texte échappé — vérifié sur le résumé mooré réel | ✅ |

