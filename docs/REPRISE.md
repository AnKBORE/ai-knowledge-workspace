# Reprise de session — 3 novembre 2026, matin

> **À lire en premier.** Ce fichier existe pour qu'une session neuve, sans aucun
> souvenir des échanges précédents, puisse reprendre le travail sans rien refaire
> ni rien casser. Il est **opérationnel** : chaque affirmation a été vérifiée à
> l'écriture. Si un chiffre ici diffère de la base, c'est la base qui fait foi —
> et il faut comprendre pourquoi avant de continuer.

---

## 1. Le projet, en trois phrases

**Agentic Knowledge Workspace™** — PHP 8.4.27 / MySQL 8.4.13, sans framework,
architecture MVC stricte. Un espace de travail documentaire qui transforme des
documents **et une voix enregistrée** en source interrogeable, avec citation des
sources et refus explicite quand la réponse n'existe pas. Construit seul pour le
**Meta Global AI Developer Hackathon** (thème *Build for Your World*, piste
*Indie Developer*).

- Dépôt : `C:\wamp64\www\projet hackaton\ai-knowledge-workspace`
- Distant : `https://github.com/AnKBORE/ai-knowledge-workspace` (public, MIT)
- **Soumission : mercredi 4 novembre 2026, 21 h 00 ET** (= 02 h 00 UTC le 5)

---

## 2. État exact au moment de la reprise

| Élément | Valeur |
|---|---|
| Branche | `main`, **1 commit en avance sur `origin/main`** |
| Tête locale | `10e58a2` · distant `84e0a80` |
| Tests | **124 tests, 295 assertions** |
| Migrations | **25** (50 fichiers `.sql`, up + down) → 22 tables |
| Comptes | **2** (1 protégé) · Espaces **2** · Adhésions **3** |
| Documents | **4** · chunks **8** · versions **4** · corrections **0** |
| Journal | 129 entrées |
| Captures | **5** dans `docs/PREUVES/` — il en manque **3** |

**Première action de la nouvelle session :** demander au propriétaire de lancer

```powershell
git push origin main
```

Le commit en attente contient la réparation de l'outil d'audit et la preuve
qu'il sait échouer. Rien ne doit être perdu.

### Les 4 documents

| # | Titre | Type | Langue | Espaces | Chunks |
|---|---|---|---|---|---|
| 1 | Note chantier Koudougou | texte | fr | Général + Chantier test | 1 |
| 2 | Rapport scanné Koudougou | PDF | fr | Général + Chantier test | 4 |
| 3 | **Doc mooré scanné** | PDF | **mo** | Général | 2 |
| 4 | **Meeting Validation** | audio wav | **en** | Général | 1 |

Le document 3 est un scan mooré lu par **OCR** (1 093 caractères).
Le document 4 est un **enregistrement vocal** transcrit en 9 secondes, avec
traduction française adjointe.

---

## 3. Ce qui a été fait cette nuit — et qu'il ne faut **pas** refaire

Une passe de recette complète a trouvé **vingt défauts** (P-51 à P-70 dans
`docs/REGISTRE_PROBLEMES.fr.md`). Tous corrigés, testés, documentés. Les plus
importants :

| Défaut | Ce qu'il était |
|---|---|
| P-54 | l'OCR ne lisait que les images JPEG ; un scan mooré était refusé |
| P-58, P-65 | l'espace affiché ne limitait ni la recherche ni le catalogue |
| P-59 | une réponse **fausse** sur le mauvais sujet, sans refus |
| P-66 | trois routes acceptaient un `document_id` sans contrôle par document |
| P-68 | la traduction inventait un nom propre (« Voodoonor » pour *the donor*) |
| P-69 | la page d'accueil annonçait « 100 tests » au lieu de 124 |

**Ne pas re-corriger ces points : ils sont faits, vérifiés et poussés.**
En cas de doute, lire le registre avant de toucher au code.

---

## 4. Où tout se trouve

| Fichier | Rôle |
|---|---|
| `docs/ETAT_DE_DEMO.fr.md` | **l'état de référence** : comptes, espaces, documents, ce que voit chaque profil, chiffres exacts à retrouver à l'écran |
| `docs/DEMO_VIDEO_ET_SOUMISSION.fr.md` | **la fiche de tournage** : 9 séquences minutées, règles du concours, check-list de soumission |
| `docs/RECETTE_AZ.fr.md` | la recette pas à pas, avec le bilan de la passe |
| `docs/REGISTRE_PROBLEMES.fr.md` | les 69 problèmes rencontrés, dont 19 cette nuit |
| `docs/SECURITE_DONNEES_API.fr.md` | ce qui part vers l'API, ce qui ne part jamais |
| `docs/PREUVES/README.md` | la liste des captures à faire et ce que chacune prouve |
| `tools/` | les outils de vérification (voir §5) |

Les mêmes documents existent en anglais (`.md` sans suffixe).

---

## 5. Vérifier l'état en deux minutes

```powershell
cd "C:\wamp64\www\projet hackaton\ai-knowledge-workspace"

php tools\audit_references.php      # références de fichiers, routes, variables, captures
php tools\audit_numbers.php         # chiffres annoncés vs base et vs config
php tools\lint_docs.php             # structure des 20 documents
php vendor/phpunit/phpunit/phpunit  # 124 tests
php tools\recette_check.php         # instantané de la base
```

**Ce que chaque outil garantit — et ce qu'il ne garantit pas :**

- `audit_numbers` vérifie **11 affirmations chiffrées** sur 207 nombres du
  document d'état ; les 196 autres sont des dates, durées, scores et montants,
  non vérifiables automatiquement. Il l'affiche lui-même : ne pas lire
  « tous les chiffres correspondent » comme « tout est vrai ».
- Les trois outils ont été **testés par mutation** : une erreur injectée
  volontairement est bien détectée.

---

## 6. Ce qui reste — trois gestes, dans cet ordre

### 6.1 Les deux captures manquantes (15 minutes)

| # | Geste | Fichier attendu |
|---|---|---|
| 1 | `/tache` → documents **Doc mooré scanné** + **Meeting Validation** → instruction `Prepare a short note summarising the school budget decision and the Mooré document.` → attendre **25 s** | `04-agentic-trace.png` (les 6 étapes visibles) |
| 2 | puis **✦ Clean** → **DOCX** | `05-docx-export.png` |
| 3 | session `membre.recette@example.org` → `/documents` (2 documents) → `/journal` (**403**) | `08-member-403.png` |

Déposer dans `docs/PREUVES/`, puis mettre à jour `docs/PREUVES/README.md`
(marquer les lignes ✅) et commiter.

### 6.2 L'enregistrement vidéo

La fiche est `docs/DEMO_VIDEO_ET_SOUMISSION.fr.md`, séquences 0:00 → 2:50.
Trois choses suffisent à la démonstration, dans cet ordre :

1. une **voix** devient un document interrogeable (9 s) ;
2. la réponse **cite**, et **refuse** quand elle ne sait pas ;
3. l'**espace de travail** limite ce que l'assistant voit.

⚠️ **Le geste 2 crée un 5ᵉ document** : la page en affichera 5 ensuite, alors que
`ETAT_DE_DEMO` décrit l'état **avant** la prise. C'est normal et documenté.

### 6.3 La soumission

Formulaire : `https://cerebralvalley.ai/e/meta-ai-developer-hackathon/hackathon/submit`

Check-list complète en §13 de `docs/DEMO_VIDEO_ET_SOUMISSION.fr.md`.

---

## 7. Règles de travail à respecter dans cette session

| Règle | Pourquoi |
|---|---|
| **Français**, réponses courtes, tableaux, commandes exactes | préférence du propriétaire |
| **Ne jamais affirmer sans vérifier** | trois audits de cette nuit ont menti avant d'être corrigés |
| Vérifier **après** chaque modification (syntaxe, page servie, base) | deux fichiers ont été corrompus par un script en une seule nuit |
| Ne pas écrire de **secret dans le dépôt** (public) | les identifiants sont fournis oralement par le propriétaire |
| Toujours **pousser** après un correctif | le jury ne lit que le dépôt distant |

### Identifiants

Ils **ne sont pas dans ce dépôt**. Le propriétaire les fournit au début de la
session. Le compte protégé se régénère avec :

```powershell
php tools/seed_admin.php --reset
```

---

## 8. Risques connus, à traiter après la soumission

| # | Risque | Action |
|---|---|---|
| 1 | La **clé API Meta/Baseten** a pu apparaître dans une trace de débogage locale | la régénérer **après** l'événement |
| 2 | Plafond de dépense Baseten | vérifier la console avant la prise, couper ensuite |
| 3 | Les vecteurs de recherche sont calculés **localement** (hachage, 256 dimensions) | déclaré honnêtement dans le README ; ne pas le présenter comme un modèle Meta |
| 4 | La portée d'espace est appliquée à **quatre endroits** (recherche, catalogue, tâche, documents demandés) | répétition assumée : le refactor est noté, pas fait |

---

## 9. Message à coller au début de la nouvelle session

> Projet : Agentic Knowledge Workspace, PHP 8.4 / MySQL, hackathon Meta.
> Lis d'abord `docs/REPRISE.md` du dépôt : il contient l'état exact, ce qui est
> fait, ce qui reste et les règles de travail. Puis lance les cinq commandes de
> vérification du §5 et dis-moi ce qui diffère de ce que le document annonce.
> On continue ensuite avec les captures 04, 05, 08 puis le tournage.

---

*Écrit le 3 novembre 2026 au matin, après une passe de recette de bout en bout.
Chaque chiffre de ce document a été lu dans la base ou dans le dépôt, pas de
mémoire.*
