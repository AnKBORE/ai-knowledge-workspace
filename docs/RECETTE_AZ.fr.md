# Recette — exécution réelle sur base neuve, 2 novembre 2026

🇬🇧 English version: [RECETTE_AZ.md](RECETTE_AZ.md)

> **Objet.** Vérifier que le système s'exécute réellement de bout en bout sur une
> base **neuve**, et non sur un état préparé à la main. Chaque étape dit quoi
> faire et ce qui doit apparaître. Une étape qui ne produit pas le résultat
> attendu est un **constat**, pas une gêne : elle s'inscrit au registre.
>
> La base a été vidée le 2 novembre : 25 migrations appliquées depuis zéro, un
> compte d'amorçage, un espace, aucun document. Tout ce qui suit reconstruit
> l'état.

---

## 0. Point de départ

| Élément | Valeur attendue |
|---|---|
| Migrations | **25** |
| Comptes | **1** — `anatolekbore@gmail.com` (technique, protégé) |
| Espaces | **1** — « Général » (restreint) |
| Documents | **0** |
| Journal | **1** entrée (l'amorçage) |

Contrôle :

```powershell
C:\wamp64\bin\mysql\mysql8.4.13\bin\mysql.exe -u root -D ai_knowledge_workspace ^
  -e "SELECT (SELECT COUNT(*) FROM migrations) m, (SELECT COUNT(*) FROM utilisateur) u, (SELECT COUNT(*) FROM documents) d;"
```

---

## 1. Installation et connexion

| # | Faire | Attendre |
|---|---|---|
| 1.1 | `php tools/migrate.php` | « Migrated 0 migration(s) » — tout est déjà appliqué |
| 1.2 | `php tools/seed_admin.php` | « Protected account confirmed », 1 protégé |
| 1.3 | Ouvrir `/login`, se connecter | Redirection vers `/`, barre du haut : compte + « Général » comme espace actif |
| 1.4 | Ouvrir les dix pages du menu | Toutes en 200, aucune page blanche, aucune erreur console |

---

## 2. Gouvernance — créer les acteurs de test

| # | Faire | Attendre |
|---|---|---|
| 2.1 | `/administration` → **Créer un utilisateur** | Compte créé, listé dans l'onglet *Comptes*, compteur +1 |
| 2.2 | **Créer un espace collaboratif** « Chantier test » | Espace listé, statut `actif`, vous en êtes responsable |
| 2.3 | **Ajouter un membre** : le nouveau compte → « Chantier test », rôle `contributeur` | Adhésion listée, compteur +1 |
| 2.4 | Se déconnecter, se reconnecter avec le nouveau compte | Menu plus court, ni *Administration* ni *Journal d'audit* |

> Un simple `lecteur` doit recevoir **403** sur `/journal` et `/administration`,
> et une **page 403** (pas un plantage) sur `/categories` s'il ne peut pas déposer.

---

## 3. Dépôt — les trois natures

| # | Faire | Attendre |
|---|---|---|
| 3.1 | Déposer un fichier **texte**, choisir deux espaces | Document présent, **deux** badges d'espaces |
| 3.2 | Déposer un **PDF scanné** | OCR exécuté, document indexé, statut `publie` |
| 3.3 | Déposer un **audio** (court enregistrement) | Transcription produite, chunks indexés, traductions disponibles |
| 3.4 | Contrôler la liste des documents | 3 lignes, catégories, badges, aucune colonne cassée |

---

## 4. Récupération et génération

| # | Faire | Attendre |
|---|---|---|
| 4.1 | Poser une question sur un des documents | Réponse construite dessus, **sources numérotées** sous la réponse |
| 4.2 | Poser une question nommant un document non lisible (session membre) | Refus explicite, aucune réponse inventée |
| 4.3 | Tâche agentique sur deux documents | Résultat structuré, sources, *Nettoyer* et **DOCX** fonctionnent |
| 4.4 | Traduire un extrait en mooré | Traduction, panneau des extraits, entrée au journal |

---

## 5. Correction humaine — la boucle d'apprentissage

| # | Faire | Attendre |
|---|---|---|
| 5.1 | Corriger une transcription (ou déposer un fichier corrigé) | Correction enregistrée, visible dans la liste des corrections |
| 5.2 | Reposer la même question | La version corrigée est préférée, et le texte source reste conservé |
| 5.3 | `php tools/prove_provenance.php <id>` | Chaîne intacte, correction **liée à sa version** |

---

## 6. Cloisonnement — ce qu'un membre voit réellement

| # | Contrôle | Attendu |
|---|---|---|
| 6.1 | `/documents` en membre | Seulement les documents de ses espaces |
| 6.2 | `/api/documents.php` en membre | Le même ensemble, jamais les autres |
| 6.3 | Téléchargement d'un identifiant étranger | **404**, pas de fichier |
| 6.4 | `/journal` et `/administration` en membre | **403** avec un message clair |

---

## 7. Cycle de vie d'un espace

| # | Faire | Attendre |
|---|---|---|
| 7.1 | Clore « Chantier test » | Statut `archive`, plus aucun dépôt accepté (422) |
| 7.2 | Ouvrir un document de cet espace | Toujours lisible — clore n'efface pas |
| 7.3 | Réouvrir l'espace | Les dépôts repassent |
| 7.4 | Terminer une adhésion | Accès coupé **immédiatement** (403 dès la requête suivante) |

---

## 8. Protection des comptes

| # | Faire | Attendre |
|---|---|---|
| 8.1 | Couronne sur un autre compte | Devient protégé, listé avec ★ |
| 8.2 | Tenter de le supprimer ou de le suspendre | **403**, refus journalisé |
| 8.3 | Retirer les deux protections | Bandeau d'avertissement « aucun compte protégé » |
| 8.4 | `php tools/seed_admin.php` | Au moins une protection rétablie |

---

## 9. Preuves à collecter

```powershell
php vendor/phpunit/phpunit/phpunit            # 124 tests, 295 assertions
php tools/smoke_admin_endpoints.php           # 27 vérifications
php tools/smoke_endpoints_anonymous.php       # 31 endpoints × 2 méthodes
php tools/lint_docs.php                       # documentation
php tools/prove_provenance.php <id>           # chaîne de provenance
```

---

## 10. Que faire des constats

| Résultat | Action |
|---|---|
| L'étape se comporte comme attendu | La cocher, garder l'état |
| L'étape échoue | **L'écrire dans `docs/REGISTRE_PROBLEMES.md`**, avec l'attendu et l'observé |
| L'étape est ambiguë | Réécrire l'étape — un test de recette ambigu est un test qui ne peut pas échouer |

---

*Exécutée sur base neuve le 2 novembre 2026. Le document d'état de démonstration
(`ETAT_DE_DEMO.md`) doit être réécrit dès que cette recette produit un état stable.*

---

## 11. Bilan de la passe du 2 novembre 2026

La recette a été faite **en faisant les gestes**, sur une base remise à zéro, avec
le compte super administrateur fraîchement créé. Les sections 1 à 4 ont été
parcourues de bout en bout ; la section 6 partiellement (le refus et la session
membre remplacent l'« espace vide » qui n'existe plus).

**Dix-neuf défauts ont été trouvés et corrigés** — `REGISTRE_PROBLEMES.fr.md`,
P-51 à P-69. Quatre auraient été visibles à la caméra :

| Défaut | Ce que ça donnait à l'écran |
|---|---|
| P-52 | le formulaire annonçait *« Général — 0 document »* alors que Général en contenait deux |
| P-58 | l'espace affiché en haut ne limitait pas la recherche |
| P-59 | une réponse confiante sur le **mauvais** chantier, sans refus |
| P-60 | le bouton *Résumer* ne faisait rien |

Ce que la passe a produit, mesuré :

| Contrôle | Résultat |
|---|---|
| Dépôt audio → indexé | **9 s** |
| Scan mooré refusé, puis lu | 0 caractère → **1 093 caractères** en 8,6 s |
| Question sur l'enregistrement | **10 millions de francs CFA**, une seule source, l'audio |
| Même question dans un autre espace | **refus**, zéro source |
| Question de catalogue, deux espaces | **4** documents, puis **2** |
| Suite de tests après la passe | **124 tests, 295 assertions** |
| Installation sur une base vierge | **25 migrations, 22 tables**, premier administrateur amorcé et protégé |

L'état laissé derrière est décrit dans `ETAT_DE_DEMO.fr.md`, et c'est cet état que
la vidéo doit montrer.
