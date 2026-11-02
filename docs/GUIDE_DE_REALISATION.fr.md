# Guide de réalisation — Agentic Knowledge Workspace

🇬🇧 English version: [GUIDE_DE_REALISATION.md](GUIDE_DE_REALISATION.fr.md)

> **Gestion collaborative des connaissances, réinventée par l'IA.**
>
> Pour les **administrations publiques et privées, les entreprises et les organisations de développement** : centralisez vos documents, préservez votre mémoire institutionnelle et transformez vos connaissances en un **actif stratégique**, interrogeable en langage naturel grâce à l'intelligence artificielle.
>
> Réalisé pour le **Meta Global AI Developer Hackathon** — Thème : **Build for Your World** · Piste : **Indie Developer** · Modèle : **Muse Spark Open** · Licence : **MIT**.

---

## 0. Vision en une phrase

**Un espace de travail collaboratif, augmenté par l'IA, qui transforme les documents, scans et enregistrements vocaux d'une organisation en une base de connaissances interrogeable en langage naturel — avec des sources citées, un historique versionné et un journal d'audit complet.**

---

## 1. La problématique

Dans de nombreuses organisations, l'information critique est **dispersée entre dossiers papier, e-mails, partages de fichiers et ordinateurs personnels**.

**Conséquences :**

- Perte de connaissances lors des départs de collaborateurs.
- Difficulté d'intégration des nouveaux membres.
- Temps considérable consacré à la recherche d'informations.
- Risques de duplication, d'erreurs et de manque de traçabilité.

**Les connaissances existent, mais restent difficiles à exploiter.**

---

## 2. La solution intelligente

**Agentic Knowledge Workspace** est une plateforme de gestion des connaissances assistée par l'IA qui permet aux organisations de **centraliser, sécuriser et exploiter** leur patrimoine documentaire.

Au lieu de rechercher manuellement dans des centaines de documents, les utilisateurs posent une question :

> *« Peux-tu me présenter les rapports d'activités de 2025 concernant le renforcement des capacités de l'équipe de suivi-évaluation des projets ? »*

L'assistant IA analyse les documents **autorisés**, produit une **réponse synthétique** et **cite précisément ses sources**.

Puis, pour aller plus loin, l'utilisateur demande une **tâche agentique** :

> *« Génère une synthèse avec les dates, objectifs, participants, résultats obtenus et recommandations concernant ces dits rapports. »*

Le résultat est un **document structuré**, exportable en **PDF ou DOCX**, avec ses sources.

**Chaque document, chaque réponse et chaque action restent traçables et vérifiables.**

---

## 3. Plus qu'une simple application RAG

Le règlement du hackathon **exclut explicitement les applications RAG basiques**. Notre approche va au-delà du simple modèle *question → recherche → réponse* : la plateforme adopte une **logique agentique orientée tâches métier**.

| RAG basique | Agentic Knowledge Workspace |
|---|---|
| Réponse à une question | Exécution de tâches documentaires complètes |
| PDF textuels uniquement | PDF, images scannées et contenus multimédias |
| Réponse unique | Rapports, fiches, synthèses et notes structurées |
| Faible traçabilité | Sources citées et vérifiables |
| Aucun contrôle organisationnel | RBAC, versions et audit |
| Chatbot générique | Solution métier adaptée au terrain africain |

> ⚠️ Ne jamais présenter le projet comme « un chatbot qui répond aux questions sur des documents ». Toujours insister sur **agentique + multimodal + traçabilité + gouvernance**.

---

## 4. Fonctionnalités clés — **MVP**

| Fonctionnalité | Détail |
|---|---|
| **Authentification sécurisée** | Argon2, CSRF, sessions, verrouillage progressif |
| **Gouvernance et contrôle d'accès** | RBAC multi-niveaux, permissions globales et par espace |
| **Gestion documentaire avancée** | Stockage hors racine web, SHA-256, versions immuables, suppression logique |
| **Assistant IA contextuel** | Réponses en langage naturel, fondées sur les documents autorisés, sources citées |
| **Recherche intelligente** | Recherche hybride vectorielle et lexicale, compréhension du contexte |
| **Génération de contenu** | Résumés, notes de synthèse, rapports structurés, citations |
| **Audit et conformité** | Journalisation complète, horodatage, historique immuable |
| **Export professionnel** | PDF, DOCX, partage simplifié |

---

## 5. Une innovation issue du terrain

L'idée d'**Agentic Knowledge Workspace** est née de l'expérience acquise avec **GES-SACREMENTS-KTO-BF**, une plateforme déjà déployée dans certaines paroisses du Burkina Faso pour la gestion de la **formation**, du **suivi-évaluation des catéchumènes** et des **sacrements des fidèles chrétiens**.

Malgré la digitalisation des activités, les documents, rapports, **procédures** et connaissances institutionnelles demeurent souvent **dispersés entre plusieurs supports et systèmes**, rendant leur accès, leur exploitation et leur valorisation difficiles.

Le même constat s'observe dans les **administrations, les ONG et les entreprises**, où la mémoire institutionnelle se trouve fragmentée entre plusieurs systèmes et supports.

**Agentic Knowledge Workspace** répond à ce défi en transformant les documents et les données organisationnelles en une **base de connaissances intelligente, centralisée et interrogeable en langage naturel**.

---

## 6. Cas d'usage

| Public | Ce que ça résout |
|---|---|
| **Administrations publiques et privées** | Retrouver rapidement règlements, procédures, archives administratives et rapports officiels, sans dépendre d'une personne clé. |
| **Entreprises** | Réduire le temps de recherche documentaire, capitaliser les savoir-faire et améliorer le partage des connaissances entre équipes. |
| **Organisations de développement** | Capitaliser les connaissances des projets, faciliter le transfert d'expérience et produire des rapports fiables et sourcés. |
| **Organisations confessionnelles** | Préserver les archives pastorales, administratives et de formation, comme le fait déjà GES-SACREMENTS-KTO-BF dans les paroisses. |

---

## 7. Architecture

```
Navigateur
   │
   ▼
public/index.php (front controller, routes, en-têtes de sécurité)
   │
   ├── Controllers (Auth, AiAssistant)
   ├── Core (Router, View, Session, CSRF, Auth, PDO, Env)
   ├── Models (Utilisateur, Espace, Adhesion, Document, VersionDocument, Journal)
   ├── Services (Ai\RagService, Ai\MetaClient, ExportService, PermissionService, DocumentService, JournalService)
   └── Validators (PolitiqueMotDePasse)
         │
         ├── MySQL 8.4.13 (InnoDB / UTF8MB4)
         └── Muse Spark Open (partenaire Meta, API OpenAI-compatible)
```

### Structure du dépôt

```
public/        front controller + assets (seul dossier exposé)
config/        configuration d'environnement
core/          HTTP, routing, session, CSRF, auth, PDO
controllers/   contrôleurs MVC
models/        accès aux données (utilisateurs, espaces, documents, versions, journal)
services/      logique métier (+ Ai/ pour l'intégration des modèles)
validators/    validation des entrées
views/         templates et pages (dont views/public/landing.php)
storage/       fichiers, logs, sauvegardes (jamais exposé)
migrations/    schéma versionné
tests/         PHPUnit
```

### Tables principales

| Table | Rôle |
|---|---|
| `utilisateur` | Comptes (rôles globaux, mot de passe Argon2) |
| `espace` | Espaces de travail |
| `adhesion` | Rôles par espace (lecteur → responsable) |
| `document` | Documents (propriétaire, espace, statut) |
| `version_document` | Versions **immuables** (SHA-256, nom original, taille) |
| `document_chunks` | Segments indexés (recherche hybride) |
| `journal` | Journal d'audit **en ajout seul** |

---

## 8. Interface web

L'espace de travail est découpé en **cinq pages navigables**, pilotées par le menu vertical :

| Page | URL | Rôle |
|---|---|---|
| **Question** | `/` | Poser une question, obtenir une réponse sourcée |
| **Tâche agentique** | `/tache` | Demander une note / un rapport / une fiche ; le brouillon s'ouvre en modal |
| **Documents** | `/documents` | Liste des documents indexés (versions, résumé, téléchargement, suppression) |
| **Ajouter un document** | `/ajouter` | Indexer un texte, un PDF, une image ou un audio |
| **Journal d'audit** | `/journal` | Traçabilité en ajout seul (DataTables, export CSV) |

Une **page publique de présentation** est disponible sans authentification : `/presentation` (FR / EN).

Chaque résultat s'ouvre dans un **modal** avec téléchargement **PDF** et **DOCX**.

---

## 9. Sécurité

| Mécanisme | Mise en œuvre |
|---|---|
| **Mots de passe** | Argon2id (repli bcrypt) |
| **CSRF** | Jeton par session, vérifié sur chaque POST |
| **Sessions** | Cookies `HttpOnly`, `SameSite=Lax`, régénération à la connexion |
| **Force brute** | Verrouillage progressif après 5 échecs |
| **RBAC** | `PermissionService::peut()` et `peutAilleurs()`, refus par défaut |
| **Fichiers** | Stockés **hors racine web**, nommés par **SHA-256** |
| **Journal** | **Ajout seul**, détaillé (titre, version, déposant, empreinte) |
| **En-têtes** | `CSP`, `X-Frame-Options`, `nosniff`, `Referrer-Policy` |

---

## 10. Tests

```powershell
php vendor/phpunit/phpunit/phpunit
```

**100 tests, 230 assertions** — couvrant l'authentification, le RBAC, les versions de documents, l'audit, le RAG, les exports et les services multimodaux. Aucun serveur de base de données ni API requis (SQLite en mémoire + doubles déterministes).

---

## 10.1 Corrections humaines et apprentissage

### Pourquoi

Un modèle laisse toujours passer des erreurs de vocabulaire ou de tournure.
Plutôt que d'espérer un modèle parfait, le produit **capte la correction
humaine** et la réutilise. C'est la réponse honnête au problème des langues à
faible ressource.

### Les deux tables

| Table | Colonnes décisives |
|---|---|
| `correction_document` | `document_id`, `langue`, `texte_corrige`, `texte_source`, `nom_fichier`, `fichier`, `auteur_id`, `statut` |
| `document_chunks` | reste **inchangée** : le texte brut demeure interrogeable |

> Le fichier corrigé est stocké dans `storage/files/corrections/<doc>/<sha256>.<ext>`
> et servi **à l'octet près** ; seul son texte est extrait pour l'apprentissage.

### Le parcours

```
Documents indexés → ✏️  →  langue + nom proposé + fichier (.pdf/.docx/.txt/.md/.csv)
                        →  correction enregistrée (validée par nature)
                        →  immédiatement prioritaire dans le RAG
```

### Les quatre effets

| Effet | Implémentation |
|---|---|
| **Récupération prioritaire** | `RagService::correctionHits()` puis `preferCorrections()` : les segments bruts du document sont remplacés par le texte corrigé, un extrait par document |
| **Contexte étiqueté** | `[Extrait n — Titre · VERSION CORRIGÉE <langue> du <date>]` + note explicite au modèle |
| **Sources citées** | `sources()` expose `corrige: true` et `correction {id, langue, date, fichier}` ; l'interface affiche un badge et un lien de téléchargement |
| **Apprentissage de style** | `Apprentissage::exemples()` injecte les formulations validées dans les prompts de traduction / reformulation |

### Les règles de conception

- **Aucune approbation** : celui qui dépose maîtrise la langue ou possède le document.
- **Fichier d'origine conservé** : un `.docx` reste un `.docx` (téléchargement SHA-256 identique).
- **Nom proposé** : `<document>_corrige_vN.<ext>`, modifiable, `N` incrémental.
- **Suppression** : auteur ou `document.supprimer`, effet **immédiat** sur les prompts.
- **Isolation par langue** : une correction mooré ne pollue jamais le français.

### Tests dédiés (6)

`ApprentissageTest` couvre : absence de correction, filtrage par vocabulaire,
poids des termes rares (IDF), isolation par langue, plafond d'exemples et
**effet réel de la suppression**.

### Piège à connaître

`correction_document` et `documents` n'ont pas la même collation. Toute
comparaison SQL entre leurs colonnes `langue` lève `Illegal mix of collations` :
la préférence de langue se décide **en PHP**.

## 10.2 Tâche agentique — périmètre, réponses ouvertes, conformité

La boucle ne produit pas toujours un document administratif : la **forme de
sortie dépend de l'instruction**.

| Instruction | Sortie |
|---|---|
| « Rédige un rapport sur… », « fais une note… » | **document** avec la structure de son genre (note, rapport, compte rendu, courrier, leçon APC/PI) |
| « Analyse les risques… », « quels sont les obstacles ? » | **réponse ouverte** : synthèse de 2-3 phrases, 3 à 6 sections titrées et citées, conclusion opérationnelle — 400 mots maximum |

### Le cadrage documentaire

Un document **nommé** dans l'instruction (« à base de Meeting6 ») ou **coché** dans
le sélecteur de la page Tâche borne toute la tâche à ces documents :

1. `documentScope()` compare l'instruction aux titres accessibles ;
2. `document_ids` (sélection explicite) l'emporte sur la détection ;
3. l'intersection avec les **droits d'accès** est toujours appliquée ;
4. le contexte reçoit `SCOPE: … N'utilise aucun autre document` ;
5. une étape `scope` apparaît dans la trace.

Sans ce cadrage, un discours « à base de Meeting6 » citait la matrice d'un autre
projet : extraits ancrés, mais mauvais document.

### L'étape de conformité

`verify` contrôle que chaque affirmation est **soutenue** par son extrait.
`conformity` contrôle que la sortie **répond à l'instruction** — un brouillon
peut être parfaitement ancré et hors sujet. En cas de rejet : une réécriture,
puis un second contrôle. Un échec d'analyse du JSON vaut « conforme » : l'étape
ne bloque jamais.

## 10.3 Du brouillon au livrable

Le bouton **✦** du modal de résultat retire la ligne `Sources : [n]`, les
citations `[n]`, les marqueurs de travail (`[Proposition]`, `[À compléter…]`),
les sections `Référence documentaire` et `Note de méthode`, les règles
horizontales et le suffixe « — Proposition à partir de… ». Il masque aussi les
sources et la trace, et **met à jour la charge utile de l'export**.

Règle de sûreté : **on retire le marqueur, jamais la phrase qu'il introduisait**.
Un second clic restaure l'état initial.

Le nettoyage travaille sur le **texte** et non sur le DOM : un titre enveloppé
dans `<strong>` échappait aux motifs ligne par ligne.

## 11. Phases d'exécution

| Phase | Contenu | Statut |
|---|---|---|
| **0 — Cadrage** | Dépôt, README, licence, `.env`, migrations | ✅ |
| **1 — Socle** | Router, View, Session, CSRF, Auth, PDO | ✅ |
| **2 — Gouvernance** | Utilisateurs, espaces, RBAC, journal | ✅ |
| **3 — Documents** | Dépôt, SHA-256, versions, indexation | ✅ |
| **4 — RAG** | Chunking, embeddings, recherche hybride | ✅ |
| **5 — Assistant** | `answer()` cité, `task()` agentique, `summarize()` | ✅ |
| **6 — Multimodal** | OCR (vision) + transcription audio | ✅ |
| **7 — Export** | PDF (dompdf) + DOCX (OOXML natif) | ✅ |
| **8 — UI** | 5 pages, modaux, DataTables, page publique | ✅ |
| **9 — Qualité** | 100 tests, CSP, audit détaillé | ✅ |
| **10 — Livraison** | Vidéo ≤ 3 min, soumission | ⏳ |

---

## 12. Feuille de route — **pour le futur**

> Ces fonctionnalités **ne font pas partie du MVP soumis** : elles prolongent la plateforme et s'appuient sur ses fondations existantes.

**Collaboration avancée** — mentions · commentaires · notifications · discussions contextualisées.

**Pilotage organisationnel** — tableaux de bord décisionnels · indicateurs de performance · suivi de projets.

**IA avancée** — recommandations documentaires · détection des connaissances obsolètes · assistants spécialisés par métier.

---

## 13. Auteur

**Anatole KABORE**

**Consultant en systèmes d'information · Développeur Full Stack · Spécialiste en cybersécurité**

Basé à Ouagadougou (Burkina Faso), je suis un professionnel des technologies de l'information spécialisé dans la conception, l'architecture et le développement de solutions numériques sécurisées. J'accompagne les administrations, entreprises, ONG et organisations confessionnelles dans la transformation digitale de leurs processus métier à travers la mise en œuvre de plateformes web robustes, évolutives et centrées sur les besoins des utilisateurs.

- LinkedIn : <https://www.linkedin.com/in/anatole-kabore-73b076342>
- GitHub : <https://github.com/AnKBORE>

## 14. Vérifications et gouvernance — ajouts du 2 novembre

### 14.1 Les trois commandes de vérification

| Commande | Quand l'utiliser | Durée |
|---|---|---|
| `php tools/smoke_admin_endpoints.php` | après toute modification d'un endpoint d'administration | 3 s |
| `php tools/smoke_endpoints_anonymous.php` | avant chaque commit touchant `public/api/` | 5 s |
| `php tools/lint_docs.php` | avant chaque commit touchant `README*.md` ou `docs/` | 1 s |

Les deux balayages exigent que l'application tourne (`php -S 127.0.0.1:8080 -t public`) et lisent des identifiants par variables d'environnement (`SMOKE_ADMIN_EMAIL`, `SMOKE_ADMIN_PASSWORD`, `SMOKE_MEMBER_EMAIL`, `SMOKE_MEMBER_PASSWORD`).

### 14.2 Créer et gouverner un espace

1. `/administration` → onglet **Espaces** → carte « Créer un espace collaboratif ».
2. Choisir un nom et une visibilité (`restreint` ou `public_interne`).
3. Le créateur devient **responsable** de l'espace (adhésion automatique).
4. Onglet **Adhésions** → ajouter un membre avec un rôle local (`lecteur`, `contributeur`, `animateur`, `responsable`).
5. Pour clore un espace : bouton dans la colonne *Cycle de vie*. Clore n'efface rien, interdit seulement les nouveaux dépôts.

### 14.3 Protéger un compte (Super Admin)

Plusieurs comptes peuvent être protégés.

| Action | Où | Effet |
|---|---|---|
| Protéger | `/administration`, icône couronne sur la ligne du compte | le compte devient Super Admin, ne peut plus être supprimé/suspendu/rétrogradé |
| Retirer | icône couronne barrée sur la ligne protégée | redevient administrateur technique ordinaire |
| Désactiver entièrement | retirer la dernière protection | plus aucun compte protégé ; un bandeau l'indique |
| Restaurer le minimum | `php tools/seed_admin.php` | réapplique une protection |
| Remplacer le mot de passe | `php tools/seed_admin.php --reset` | régénère et affiche un mot de passe |

Chaque changement est journalisé (`compte.super_ajout`, `compte.super_retrait`, `compte.super_desactivation`).

### 14.4 Classer un document dans plusieurs espaces

- **Au dépôt** : le champ « Espaces de travail » accepte plusieurs sélections.
- **Après dépôt** : icône cadenas sur la ligne du document → choisir les espaces et la confidentialité, confirmer par le mot de passe administrateur.
- **Effet** : l'accès suit l'union des espaces ; retirer un espace ne retire pas les autres.

### 14.5 Avant la démonstration

```powershell
php tools/lint_docs.php
php vendor/phpunit/phpunit/phpunit
php tools/smoke_admin_endpoints.php
php tools/smoke_endpoints_anonymous.php
```

Puis vérifier à l'écran : menu filtré (membre), `/journal` en 403 pour un membre, bouton supprimer fonctionnel, onglets d'administration, filtre par espace dans la liste des documents.
