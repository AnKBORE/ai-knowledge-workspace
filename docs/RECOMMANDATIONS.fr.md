# Registre des recommandations

🇬🇧 English version: [RECOMMANDATIONS.md](RECOMMANDATIONS.md)

> Chaque recommandation faite pendant la construction est écrite ici avec son
> **statut**, pour qu'aucune ne se perde entre deux conversations et qu'aucune ne
> soit déclarée faite sans preuve. Une ligne ne quitte ce registre que lorsque le
> travail est fusionné.

| # | Recommandation | Statut | Preuve |
|---|---|---|---|
| R-01 | Faire concorder la langue déclarée de la page avec celle du texte (`lang="en"` alors que la balise disait `fr`) | **fait** | `config/app.php`, les trois layouts ; `tools/check_english_ui.php` passe sur dix pages |
| R-02 | Réparer le sélecteur de langue mort de la page de connexion (la route répondait avant la bascule) | **fait** | `public/index.php`, bascule déplacée avant la route du login |
| R-03 | Empêcher la page de présentation de choisir la langue de toute l'application | **fait** | `/presentation` n'écrit plus `lang` |
| R-04 | Traduire le chrome côté client (DataTables, Select2, boutons, sélecteur, actions du journal) | **fait** | 47 clés, `public/assets/js/ui-i18n.js`, `tools/scan_french_ui.php` |
| R-05 | Déclarer les droits de chaque élément, comme l'exigent les organisateurs | **fait** | section « Ressources tierces et droits » dans les deux README |
| R-06 | Cesser de redistribuer les PDF mooré (© SIL International 2016) | **fait** | retirés du suivi, `.gitignore`, cités dans le README |
| R-07 | Supprimer les vues non référencées et le layout par défaut inutilisé | **fait** | `views/ai/index.php`, `views/layouts/main.php` supprimés ; layout par défaut = `app` |
| R-08 | Corriger le champ de mot de passe dupliqué de la modale de contrôle | **fait** | `views/ai/documents.php` — une étiquette, un champ |
| R-09 | Rendre les appels au modèle visibles, comme l'exigent les organisateurs | **fait** | `MetaClient::logModelCall()`, hôte inclus |
| R-10 | Conserver des preuves capturées dans le dépôt | **commencé** | `docs/PREUVES/` — premier appel réel versionné |
| R-11 | Enregistrer la démo en anglais, avec la capture en direct en ouverture | **décidé** | `docs/DEMO_VIDEO_ET_SOUMISSION.fr.md` §3 |
| R-12 | Réécrire `ETAT_DE_DEMO.md` à partir de l'état produit par la recette | **ouvert** | à faire après `RECETTE_AZ.fr.md` |
| R-13 | Dérouler la recette de bout en bout sur la base neuve | **ouvert** | `RECETTE_AZ.fr.md`, étapes 1 à 8 |
| R-14 | Pousser les commits locaux | **ouvert** | `git status` — 14 en avance |
| R-15 | **Renouveler la clé API Baseten** une fois l'événement terminé | **ouvert** | la clé est apparue dans cette conversation |
| R-16 | Fixer une limite de dépense sur le compte Baseten | **ouvert** | les appels sont facturés sur les 100 $ de crédits accordés à l'auteur |
| R-17 | Trancher le sort des deux comptes protégés | **décidé** | deux sont conservés volontairement : le modèle est un ensemble, pas un siège |
| R-18 | Ne pas réécrire l'historique Git pour effacer les PDF | **décidé** | cela détruirait les preuves datées que le jury doit pouvoir vérifier |
| R-19 | Tests fonctionnels des 16 endpoints couverts seulement par le balayage anonyme | **ouvert** | optionnel, après soumission |
| R-20 | Retirer l'alias `--transfer` quand le modèle multiple sera expliqué partout | **ouvert** | optionnel, cosmétique |
| R-21 | Audit d'accessibilité (clavier, contrastes, lecteur d'écran) | **ouvert** | jamais fait ; limite assumée |
| R-22 | Garder le dépôt public, comme l'exigent les organisateurs | **décidé** | Cerebral Valley, 28 octobre |
| R-23 | Vérifier que la clé de l'API Model n'atteint jamais le navigateur | **fait** | huit pages et les fichiers JS contrôlés : aucune clé, aucune variable globale `window.*KEY*` |
| R-24 | Garder les logos et mascottes Meta hors de l'application et du dépôt | **fait** | aucune image versionnée dans `public/` ; les modèles sont nommés en texte simple |
| R-25 | Déclarer quelles parties sont des appels au modèle Meta et lesquelles sont des fonctions du système | **fait** | section dans les deux README ; la transcription est Meta par défaut, locale en repli seulement |
| R-26 | Dire quels modèles Meta ouverts sont utilisés et où ils tournent | **fait** | `muse-spark-open-1026` sur Baseten ; aucun modèle Meta sur la machine de l'auteur, dit explicitement |
| R-27 | Établir qu'un modèle Meta à poids ouverts tournant sur sa propre machine est éligible | **fait** | Cerebral Valley, 2 novembre : ne pas utiliser l'API Model ne pénalise pas (Credit Terms §3.8), consigné dans les deux README |

---

*Tenu à la main, comme le registre des problèmes. Une ligne n'est marquée `fait`
que si une commande ou un fichier de ce dépôt le prouve.*
