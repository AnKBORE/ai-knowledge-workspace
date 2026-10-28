# Agentic Knowledge Workspace™

> **Gestion collaborative des connaissances, réinventée par l'IA.**
>
> Pour les **administrations publiques et privées, les entreprises et les organisations de développement** : centralisez vos documents, préservez votre mémoire institutionnelle et transformez vos connaissances en un **actif stratégique**, interrogeable en langage naturel grâce à l'intelligence artificielle.
>
> Réalisé pour le **Meta Global AI Developer Hackathon** — Thème : **Build for Your World** · Piste : **Indie Developer** · Modèle : **Muse Spark Open** · Licence : **MIT**.

---

## La problématique

Dans de nombreuses organisations, l'information critique est **dispersée entre dossiers papier, e-mails, partages de fichiers et ordinateurs personnels**.

**Conséquences :**

- Perte de connaissances lors des départs de collaborateurs.
- Difficulté d'intégration des nouveaux membres.
- Temps considérable consacré à la recherche d'informations.
- Risques de duplication, d'erreurs et de manque de traçabilité.

**Les connaissances existent, mais restent difficiles à exploiter.**

---

## La solution intelligente

**Agentic Knowledge Workspace** est une plateforme de gestion des connaissances assistée par l'IA qui permet aux organisations de **centraliser, sécuriser et exploiter** leur patrimoine documentaire.

Au lieu de rechercher manuellement dans des centaines de documents, les utilisateurs peuvent simplement poser une question :

> *« Peux-tu me présenter les rapports d'activités de 2025 concernant le renforcement des capacités de l'équipe de suivi-évaluation des projets ? »*

L'assistant IA analyse les documents **autorisés**, produit une **réponse synthétique** et **cite précisément ses sources**.

Puis, pour aller plus loin, l'utilisateur demande une **tâche agentique** :

> *« Génère une synthèse avec les dates, objectifs, participants, résultats obtenus et recommandations concernant ces dits rapports. »*

Le résultat est un **document structuré**, exportable en **PDF ou DOCX**, avec ses sources.

**Chaque document, chaque réponse et chaque action restent traçables et vérifiables.**

---

## Plus qu'une simple application RAG

Le règlement du hackathon **exclut explicitement les applications RAG basiques**. Notre approche va au-delà du simple modèle *question → recherche → réponse* : la plateforme adopte une **logique agentique orientée tâches métier**.

| RAG basique | Agentic Knowledge Workspace |
|---|---|
| Réponse à une question | Exécution de tâches documentaires complètes |
| PDF textuels uniquement | PDF, images scannées et contenus multimédias |
| Réponse unique | Rapports, fiches, synthèses et notes structurées |
| Faible traçabilité | Citations numérotées liées aux extraits exacts |
| Contexte limité au top-K | Expansion voisins + premier chunk, intention catalogue |
| Sortie monolingue | Réponses dans la langue de la question, sources traduites |
| Aucun contrôle organisationnel | RBAC, versions et audit |
| Chatbot générique | Solution métier adaptée au terrain africain |

---

## Fonctionnalités clés — **MVP**

### Authentification sécurisée
- Argon2 pour le hachage des mots de passe
- Protection CSRF
- Gestion sécurisée des sessions
- Verrouillage progressif contre les attaques

### Gouvernance et contrôle d'accès
- RBAC multi-niveaux
- Permissions globales
- Permissions par espace de travail

### Gestion documentaire avancée
- Stockage hors racine web
- Empreintes SHA-256
- Versions immuables
- Suppression logique sécurisée

### Assistant IA contextuel
- Réponses en langage naturel
- Fondées exclusivement sur les documents autorisés
- Sources automatiquement citées

### Recherche intelligente
- Recherche hybride vectorielle et lexicale
- Compréhension du contexte utilisateur
- Recherche sémantique au-delà des mots-clés

### Moteur de récupération de précision (RAG++)
- **Retrieval filtré par permissions** — chaque chunk scoré appartient à un document que l'acteur est autorisé à lire (tenant, adhésion d'espace, confidentialité, propriété, suppression logique), résolu en une passe par requête
- **Scoring hybride** — `0,6 × similarité cosinus + 0,4 × recouvrement lexical` conserve un bon rappel même avec les embeddings locaux
- **Découpage à la limite de mot** — les coupes à taille fixe reculent au dernier espace : aucun extrait ne se termine en plein mot
- **Expansion de contexte** — chaque extrait retenu est enrichi de ses chunks voisins ±1 **et du premier chunk du document**, là où vivent titres et en-têtes ; une question sur un titre est donc toujours répondable
- **Détection d'intention catalogue** — « liste tous les documents » contourne le retrieval de chunks et alimente le modèle avec l'inventaire réel filtré par permissions (titre · catégorie · date d'indexation · fichier)
- **Citations vérifiables** — le modèle cite `[n]` ; seuls les extraits **cités** sont affichés comme sources, avec leur numéro exact de contexte (`Sources : [3]` ⇢ entrée *3*). Pas de citation, pas de liste de sources
- **Pipeline multilingue complet** — la réponse suit la **langue de la question** ; les citations littérales sont traduites avec l'original entre parenthèses ; les **extraits de sources sont traduits** aussi via une passe dédiée à basse température
- **Téléchargement direct** — chaque source citée porte un lien `document_id` vers sa dernière version

### Génération de contenu
- Résumés automatiques
- Notes de synthèse
- Rapports structurés
- Citations et références intégrées

### Audit et conformité
- Journalisation complète des actions
- Horodatage systématique
- Historique immuable des opérations

### Export professionnel
- PDF
- DOCX
- Partage simplifié des résultats

---

## Une innovation issue du terrain

L'idée d'**Agentic Knowledge Workspace** est née de l'expérience acquise avec **GES-SACREMENTS-KTO-BF**, une plateforme déjà déployée dans certaines paroisses du Burkina Faso pour la gestion de la **formation**, du **suivi-évaluation des catéchumènes** et des **sacrements des fidèles chrétiens**.

Malgré la digitalisation des activités, les documents, rapports, **procédures** et connaissances institutionnelles demeurent souvent **dispersés entre plusieurs supports et systèmes**, rendant leur accès, leur exploitation et leur valorisation difficiles.

Le même constat s'observe dans les **administrations, les ONG et les entreprises**, où la mémoire institutionnelle se trouve fragmentée entre plusieurs systèmes et supports.

**Agentic Knowledge Workspace** répond à ce défi en transformant les documents et les données organisationnelles en une **base de connaissances intelligente, centralisée et interrogeable en langage naturel**.

---

## Cas d'usage

| Public | Ce que ça résout |
|---|---|
| **Administrations publiques et privées** | Retrouver rapidement règlements, procédures, archives administratives et rapports officiels, sans dépendre d'une personne clé. |
| **Entreprises** | Réduire le temps de recherche documentaire, capitaliser les savoir-faire et améliorer le partage des connaissances entre équipes. |
| **Organisations de développement** | Capitaliser les connaissances des projets, faciliter le transfert d'expérience et produire des rapports fiables et sourcés. |
| **Organisations confessionnelles** | Préserver les archives pastorales, administratives et de formation, comme le fait déjà GES-SACREMENTS-KTO-BF dans les paroisses. |

---

## Sous le capot

Une architecture volontairement sobre, robuste et maîtrisée.

| Composant | Version |
|---|---|
| **PHP** | **8.4.27** — MVC strict (PSR-4) |
| **MySQL** | **8.4.13** — InnoDB / UTF8MB4 |
| **Modèle IA** | **Muse Spark Open** (Meta, servi par un partenaire d'inférence) |
| **Tests** | **68 tests automatisés · 143 assertions** |
| **Sécurité** | Argon2 · CSP · CSRF · journal d'audit |
| **UI** | Bilingue EN/FR (EN par défaut), Bootstrap + DataTables + Select2 |

---

## Feuille de route — **pour le futur**

> Ces fonctionnalités **ne font pas partie du MVP soumis** : elles prolongent la plateforme et s'appuient sur ses fondations existantes.

**Collaboration avancée** — mentions · commentaires · notifications · discussions contextualisées.

**Pilotage organisationnel** — tableaux de bord décisionnels · indicateurs de performance · suivi de projets.

**IA avancée** — recommandations documentaires · détection des connaissances obsolètes · assistants spécialisés par métier.

---

## Installation

```powershell
# 1. Dépendances
php tools/composer.phar install

# 2. Configuration locale
Copy-Item .env.example .env

# 3. Base de données
php tools/migrate.php migrate

# 4. Premier administrateur + espace par défaut
php tools/seed_admin.php

# 5. Lancer en local
.\serve.bat
```

Puis ouvrir <http://127.0.0.1:8080/presentation?lang=fr> (présentation publique) ou <http://127.0.0.1:8080/login>.

En production, la racine web doit pointer vers `public/`.

---

## Interface web

L'espace de travail est découpé en **sept pages navigables**, pilotées par le menu vertical — toutes bilingues EN/FR (EN par défaut, bascule par session) :

| Page | URL | Rôle |
|---|---|---|
| **Question** | `/` | Poser une question, obtenir une réponse sourcée à citations numérotées (téléchargeables) |
| **Tâche agentique** | `/tache` | Demander une note / un rapport / une fiche ; le brouillon s'ouvre en modal |
| **Documents** | `/documents` | Liste des documents indexés (versions, résumé, téléchargement, suppression par mot de passe) |
| **Ajouter un document** | `/ajouter` | Indexer un texte, un PDF, une image ou un audio (catégorie requise) |
| **Catégories** | `/categories` | Créer et gérer les catégories documentaires |
| **Journal d'audit** | `/journal` | Traçabilité (DataTables, export CSV, purge de période ≥10 jours par mot de passe) |
| **Mon profil** | `/mon-profil` | Identité, adhésions ; modifications confirmées par mot de passe |

Chaque résultat s'ouvre dans un **modal** avec téléchargement **PDF** et **DOCX**.

Endpoints API (protégés par session) :

- `POST /api/upload.php` — indexer un fichier (multipart)
- `POST /api/ask.php` — `{"question": "..."}` → réponse + sources ; `{"instruction": "...", "mode": "task"}` → brouillon + sources
- `POST /api/summarize.php` — `{"document_id": 3}` → résumé + sources
- `POST /api/export.php` — `{"title", "content", "format": "pdf|docx"}` → fichier
- `GET /api/documents.php` — documents récents (JSON)
- `GET /api/download_document.php` — télécharger une version (journalisé)
- `GET /api/journal_export.php` — journal d'audit en CSV
- `POST /api/journal_purge.php` — purge du journal confirmée par mot de passe (entrées de plus de 10 jours ; la purge est elle-même journalisée)

---

## Tests

```powershell
php vendor/phpunit/phpunit/phpunit
```

**68 tests, 143 assertions** — couvrant l'authentification, le RBAC, les versions de documents, l'audit, le retrieval RAG filtré par permissions, les exports et les services multimodaux. Aucun serveur de base de données ni API requis (SQLite en mémoire + doubles déterministes).

---

## Sécurité

Requêtes SQL préparées · protection CSRF sur chaque POST · hachage des mots de passe (Argon2/bcrypt) · verrouillage progressif anti-force brute · RBAC résolu centralement (`PermissionService`) · refus par défaut · fichiers stockés hors racine web · aucun secret dans le code ou les logs · journal d'audit (ajout seul) · en-têtes de sécurité (CSP).

---

## Documentation

- [docs/GUIDE_DE_REALISATION.md](docs/GUIDE_DE_REALISATION.md) — feuille de route d'exécution (phases, checklists, jalons)
- [docs/REGISTRE_PROBLEMES.md](docs/REGISTRE_PROBLEMES.md) — registre des problèmes et résolutions
- [docs/MARQUE_ET_PROPRIETE.md](docs/MARQUE_ET_PROPRIETE.md) — marque et propriété intellectuelle
- [docs/SECURITE_DONNEES_API.md](docs/SECURITE_DONNEES_API.md) — sécurité et confidentialité des données de l'API
- [docs/DEMO_VIDEO_ET_SOUMISSION.md](docs/DEMO_VIDEO_ET_SOUMISSION.md) — script de la vidéo et checklist de soumission

---

## Auteur

**Anatole KABORE**

**Consultant en systèmes d'information · Développeur Full Stack · Spécialiste en cybersécurité**

Basé à Ouagadougou (Burkina Faso), je suis un professionnel des technologies de l'information spécialisé dans la conception, l'architecture et le développement de solutions numériques sécurisées. J'accompagne les administrations, entreprises, ONG et organisations confessionnelles dans la transformation digitale de leurs processus métier à travers la mise en œuvre de plateformes web robustes, évolutives et centrées sur les besoins des utilisateurs.

Mon expertise couvre le développement Full Stack, l'architecture logicielle, la cybersécurité, la gestion des données et l'intégration de solutions d'intelligence artificielle appliquées à des problématiques concrètes. J'accorde une attention particulière à la sécurité, à la gouvernance des données, à la traçabilité des actions et à la pérennisation des connaissances organisationnelles.

À travers mes réalisations, je cherche à concevoir des outils capables de répondre aux défis réels rencontrés sur le terrain, notamment la gestion de l'information, la collaboration, le suivi-évaluation et la valorisation du patrimoine documentaire des organisations africaines.

- LinkedIn : <https://www.linkedin.com/in/anatole-kabore-73b076342>
- GitHub : <https://github.com/AnKBORE>

---

## Meta Global AI Developer Hackathon

- **Thème :** Build for Your World
- **Piste :** Indie Developer
- **Modèle IA :** Muse Spark Open
- **Licence :** MIT

---

## Licence

**MIT** — voir [LICENSE](LICENSE).
