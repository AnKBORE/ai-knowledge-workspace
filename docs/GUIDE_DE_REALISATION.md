# Guide de réalisation — Agentic Knowledge Workspace

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

**86 tests, 198 assertions** — couvrant l'authentification, le RBAC, les versions de documents, l'audit, le RAG, les exports et les services multimodaux. Aucun serveur de base de données ni API requis (SQLite en mémoire + doubles déterministes).

---

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
| **9 — Qualité** | 79 tests, CSP, audit détaillé | ✅ |
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
