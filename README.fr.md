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

- **RBAC à deux niveaux.** Rôles globaux (membre, administrateur fonctionnel, administrateur technique) et rôles locaux par espace (lecteur, contributeur, animateur, responsable) ; la résolution remonte la hiérarchie des espaces.
- **Un document, plusieurs espaces.** Une table de liaison `document_espace` porte l'affectation : l'accès suit l'**union** des espaces et le propriétaire garde toujours l'accès (migration 025).
- **Espace et confidentialité au dépôt.** Le déposant choisit l'espace et la confidentialité ; `public_interne` est réservé aux administrateurs ; le contexte survit à la file des dépôts différés (migration 026).
- **Rôles de contrôle modifiables par l'administration seule.** Depuis la liste des documents, un administrateur change l'espace et la confidentialité ; son mot de passe est exigé et les valeurs **avant/après** sont journalisées.
- **Il refuse plutôt que de deviner.** Quand le meilleur extrait tombe sous 0,25 — mesuré : une correspondance pertinente est à 0,53 et plus, un hors-sujet à 0,17 et moins — la réponse nomme l'espace et réclame le document manquant, plutôt que de produire une phrase confiante sur le mauvais sujet.
- **Il vérifie le document, pas seulement le droit.** Chaque route qui prend un identifiant de document appelle `peutConsulterDocument()` : la réformulation, la traduction et les corrections sont refusées pour un document hors des espaces de l'utilisateur, refus journalisé.
- **L'espace actif limite l'assistant.** L'espace affiché en haut de page n'est pas décoratif : une question n'est répondue qu'à partir des documents de cet espace. Change d'espace, et la même question renvoie ce que cet espace contient — c'est ainsi qu'un projet reste un projet.
- **Liste des documents cloisonnée.** La page et l'API ne renvoient que les documents visibles ; un document inaccessible répond **404**, sans révéler son existence.
- **Badges et filtre par espace.** La liste affiche les espaces de chaque document et se filtre par espace, y compris combiné à la recherche.
- **Espaces de travail.** Création, clôture (plus aucun dépôt, documents toujours lisibles), réouverture ; adhésions datées avec révocation immédiate ; la page `/espaces` permet de choisir l'espace actif.
- **Comptes protégés (Super Admin).** Plusieurs comptes peuvent être protégés : ni supprimables, ni suspendables, ni rétrogradables. La protection s'accorde et se retire depuis l'interface (mot de passe exigé), peut être **désactivée entièrement**, et `php tools/seed_admin.php` restaure le minimum.
- **Menu filtré par les droits.** Un membre ne voit que les entrées qu'il peut utiliser ; `/journal` et `/administration` répondent **403** sans la permission, `/categories` exige de pouvoir déposer ou supprimer.
- **Administration en onglets.** Comptes, espaces et adhésions dans trois onglets DataTables, chaque action confirmée par le mot de passe administrateur.
- **CSP par nonce.** `script-src` n'accepte plus `'unsafe-inline'` : chaque script inline porte un nonce unique par requête.

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

### Boucle d'agent — planifier → rechercher → cadrer → rédiger → vérifier → conformité

Tu choisis les **documents sources** dans le formulaire ; la boucle planifie
ensuite ses propres requêtes, ne récupère que dans ce périmètre, rédige, vérifie
que chaque affirmation est soutenue par son extrait, puis contrôle que la sortie
**répond réellement à l'instruction** — un brouillon ancré peut rester hors sujet.

| Instruction | Sortie |
|---|---|
| « Rédige un rapport sur… », « fais une note… » | un **document** avec la structure de son genre (note, rapport, compte rendu, courrier, leçon APC/PI) |
| « Analyse les risques de… », « quels sont les obstacles ? » | une **réponse ouverte** : synthèse courte, 3 à 6 sections titrées et citées, une conclusion opérationnelle |

Nommer un document dans l'instruction (« à base de Meeting6 ») borne toute la
tâche à ce document, même sans sélection. Les six étapes sont exposées dans
l'interface : le raisonnement est auditable.
Le mode tâche n'est pas une simple recherche suivie d'une génération. `RagService::task()` exécute une boucle d'agent, et chaque étape est montrée à l'utilisateur (panneau « Étapes de l'agent » dans la fenêtre de résultat) :

| Étape | Ce que fait l'agent |
|---|---|
| **1. Planifier** | Muse Spark Open découpe la consigne en 2 à 4 recherches ciblées, en connaissant **les titres des documents que l'utilisateur a le droit de consulter** (il traduit les recherches dans la langue des documents : consigne en anglais → recherches en français) |
| **2. Rechercher** | Chaque recherche passe par le moteur filtré par permissions ; les résultats sont fusionnés et dédoublonnés par extrait |
| **3. Rédiger** | Le document est rédigé à partir des extraits fusionnés et numérotés, avec une structure adaptée au genre demandé (note, rapport, compte rendu, courrier, leçon…) |
| **4. Vérifier** | Une passe de vérification contrôle chaque affirmation sourcée (chiffre, date, nom, citation) contre l'extrait cité |
| **5. Corriger** | Les affirmations non étayées sont corrigées ou retirées automatiquement (une passe) ; la trace indique combien ont été corrigées |

Mesuré sur la vraie base : consigne en anglais → 4 recherches planifiées (3 en français, dont une ciblant le bon titre de document) → 12 extraits issus de 2 documents → rédaction → **14 affirmations contrôlées, 1 non étayée, corrigée automatiquement** (~50 s de bout en bout). Si la planification ou la vérification échoue, l'agent revient au comportement à recherche unique : la tâche n'échoue jamais à cause d'elles. Couvert par `AgentTaskTest` avec un modèle scripté.

*Limite connue :* la vérification contrôle que les affirmations correspondent aux extraits ; elle ne peut pas garantir la qualité d'une traduction depuis une langue peu dotée comme le mooré — ces passages traduits doivent être relus par un locuteur.

### Ingestion en arrière-plan — uploads non bloquants
Les fichiers lents demandent plusieurs minutes de traitement IA (ASR pour l'audio, OCR pour les images et PDF scannés). Au lieu de garder la requête HTTP ouverte, ils sont mis en file d'attente :

```
upload.php ──(texte / PDF texte)──► extraction → stockage → index → 200        (synchrone, inchangé)
     │
     └─(audio / image / PDF scanné)──► ligne ingestion_jobs + worker détaché → 202 {job_id}   (~1 s)
                                            │
                tools/ingest_worker.php ◄───┘  claim (atomique) → ASR/OCR → stockage → index → audit
                                            │
     upload_status.php?job_id ◄── l'interface interroge toutes les 3 s (temps écoulé, puis « Indexé : N segments » ou une erreur traduite)
```

- **L'application reste fluide** : le worker est un processus séparé, donc le serveur `php -S` (mono-thread) continue de servir les pages (mesuré : 20–80 ms par page pendant un OCR de 105 s)
- **Pas de demi-documents** : la ligne `documents` n'est créée qu'après une extraction réussie — le retrieval et les listes n'exposent jamais un contenu non traité
- **Sûr par conception** : `claim()` atomique (une tâche n'est jamais traitée deux fois), statut lisible uniquement par l'auteur de l'upload, fichiers en file supprimés après traitement, tâches bloquées (> 30 min) marquées en échec, succès et échecs journalisés
- **Suivi repris** : les tâches en cours sont gardées dans `sessionStorage` — le suivi reprend si l'utilisateur quitte puis revient sur la page d'upload
- `IngestionService` partagé par les deux chemins (extraction, stockage, indexation, messages d'erreur traduits) — couvert par des tests unitaires

### Documents scannés — pipeline OCR
- Les **images** (png/jpg/tiff/webp) et les **PDF scannés** (sans couche texte) sont lus par la vision de Muse Spark Open. Un PDF scanné est rastérisé page par page : les flux JPEG sont utilisés tels quels, et les bitmaps Flate en 8 bits gris ou RVB sont encapsulés en PNG avant l'appel. Les encodages inhabituels — 1 bit noir et blanc, CMJN, palettes indexées — sont refusés avec un message qui nomme le cas, plutôt qu'indexés comme vides.
- Un PDF dont l'extraction de texte est vide est détecté automatiquement : ses images de pages JPEG sont extraites et passées à l'OCR, puis concaténées avec des marqueurs `[Page n / N]` — vérifié sur un recueil de chants scanné de 4 pages en mooré
- Les pages sont envoyées **en parallèle** (`curl_multi`) : le temps total est à peu près celui de la page la plus lente ; une page en échec est relancée seule sans perdre les autres
- Les scans volumineux sont réduits à 1600 px (GD) avant envoi : le texte reste lisible et on évite les délais dépassés ; `META_VISION_TIMEOUT` (300 s par défaut) couvre les pages denses
- **Goulot mesuré :** sur une page dense, 82 % des tokens générés étaient le raisonnement interne du modèle (2 466 sur 2 996). Les appels OCR utilisent donc `reasoning_effort=low` (`META_VISION_REASONING_EFFORT`) : −64 % de tokens générés pour une transcription équivalente, donc moins de crédits

### Mooré — la langue n'est pas un détail, c'est la condition d'usage

Au Burkina Faso, le savoir institutionnel se **produit** souvent en français — rapports, comptes rendus, procédures — mais il est **utilisé** par des agents de terrain, des animateurs communautaires, des membres d'associations qui travaillent en mooré. Un rapport que personne ne peut lire est un rapport qui n'existe pas. La barrière n'est donc pas technique : elle est **d'accès au savoir**.

Le mooré est une langue **peu dotée** : peu de textes écrits, presque aucun corpus aligné, et des moteurs de transcription qui le déforment (whisper a entendu *« Ibe kibali kye mame »* pour une phrase banale). C'est précisément là que les modèles génériques échouent — et c'est pourquoi la plateforme **construit la ressource** au lieu de la supposer :

- un lexique et un corpus mooré versionnés (`moore_corpus`, `appellations_moore_*.pdf`) ;
- des **corrections humaines** qui deviennent la référence et alimentent l'apprentissage ;
- un **refus explicite d'inventer** quand l'extrait manque — la limite est dite, pas masquée.

**L'oral est la source, pas le brouillon.** Un savoir **dit** — un témoignage d'ancien, une réunion de village, un chant de cérémonie — n'est pas une version dégradée d'un texte : c'est souvent sa **forme d'origine**. L'enregistrement audio est donc conservé **octet pour octet** (empreinte SHA-256, versions immuables), et la transcription, la traduction, la correction ne sont que des **lectures successives** de cette source. Chaque dérivé cite l'original ; la correction conserve côte à côte le texte corrigé **et** le texte source.

C'est ce qui donne à la plateforme sa valeur de **preuve** : on peut toujours remonter à la voix, pas seulement à ce que quelqu'un en a écrit. Et cela vaut pour **toute langue** — y compris celles qui n'ont aucun corpus écrit : le point d'entrée est la voix, pas le texte.

**Effet de terrain.** Une correction faite par un agent profite à tous les suivants : la ressource rare devient un **actif partagé**. C'est le contraire d'un gadget de traduction — c'est la condition pour que la mémoire d'une organisation serve réellement à ceux qui la font vivre.


**Rien ne se perd, et c'est vérifiable.** Chaque dérivé — chunk transcrit, traduction, correction — porte l'identifiant du document **et** celui de la version dont il est une lecture ; la version porte elle-même l'empreinte SHA-256 du fichier d'origine. La chaîne remonte donc dans les deux sens, et une commande la contrôle au lieu de l'affirmer :

```powershell
php tools/prove_provenance.php 30
```

Elle recalcule l'empreinte du fichier sur disque, la compare à celle enregistrée, et vérifie que chaque traduction et chaque correction pointe bien la version source. Sortie réelle sur le document audio de démonstration :

```
v1 empreinte identique        a9a48cd651b10113… 969 644 o
transcription indexée         1 chunk
traductions en/fr/mo          liées à une version
correction #8                 liée à la version 26 · source 373 car. · corrigé 234 car.
Chaîne intacte : tout dérivé remonte à l'enregistrement d'origine.
```

**Et sans compromis.** Le même moteur sert le français, l'anglais et le mooré : ajouter une langue est un travail de **données**, pas une réécriture.

- **De bout en bout** — une réunion en mooré enregistrée dans le navigateur est transcrite, traduite automatiquement (les deux versions indexées), retrouvée par une question en mooré et répondue **en mooré avec citations** ; les tâches agentiques en mooré produisent des documents en mooré — tout est vérifié en réel
- **Référence linguistique dédiée** (`services/Ai/MooreReference.php`) — un glossaire compact injecté dans les prompts, compilé depuis le dictionnaire mooré-français-anglais du SIL (U. Niggli, 10 650 entrées), le manuel Peace Corps et un lexique de terrain : orthographe officielle (ɩ ʋ, nasales ãẽĩõũ, voyelles longues doublées, suffixe défini -ã, marqueur déclaratif -me), pronoms, règles SVO/négation, salutations et ~80 termes de base
- **Reconstruction phonétique ASR** — les moteurs de transcription déforment les langues peu dotées (whisper a entendu *« Ibe kibali kye mame zakalamba »* pour *« Yibeoog kibare, f zak ramba »*). Le traducteur reçoit l'ordre de reconstituer d'abord le mooré voulu phonétiquement, **puis** de traduire : ce transcript déformé donne désormais *« Comment s'est passée ta matinée ? Et les gens de la maison ? »* — le bon sens (un salut demandant des nouvelles de la matinée et de la famille)
- **Ancrage few-shot** — jusqu'à 3 vrais extraits mooré issus des documents indexés de l'utilisateur sont injectés comme référence de style et d'orthographe dès que le mooré est en jeu
- **Page Traduction** (`/traduire`) — FR ⇄ EN ⇄ MO dans les deux sens, copie en un clic, chaque traduction journalisée
- **Honnêteté assumée** — la sortie mooré utilise l'orthographe mossi standard et est fiable sur le vocabulaire
- **Le mooré scanné est lu par le modèle de vision.** Une page scannée dont le corps est en mooré est transcrite avec ses diacritiques (ɛ ɔ ã ẽ ĩ õ ũ) — mesuré sur un scan réel : 1 093 caractères en 8,6 s. La qualité dépend encore du scan ; le texte saisi reste la voie la plus fiable pour le mooré.

### Transcription audio — double pipeline
- **Enregistreur de réunion** — la page Ajouter enregistre directement le micro (WAV dans le navigateur), joint le fichier au formulaire et laisse le pipeline en arrière-plan transcrire puis indexer comme un document classique. Un vu-mètre et une détection de silence empêchent d'indexer un enregistrement vide
- **Traduction automatique des transcripts** — `META_ASR_TRANSLATE_TO=French` ajoute une version traduite à chaque transcription : la langue originale et la traduction sont indexées côte à côte (transcript mooré + version française)
- **Baseten — Muse Spark Open (par défaut, en premier)** — l'audio est envoyé à `meta-models/muse-spark-open-1026` via `/v1/chat/completions` sous forme de partie standard `input_audio` (base64 brut + format). Mesuré sur un compte rendu de réunion en français : **transcription exacte (chiffres, noms propres) en 8 à 21 s, 5 essais sur 5**, contre ~2 min pour whisper.cpp sur CPU. L'ancien format `audio_url` (data URL) prenait 56 s et renvoyait des messages vides
- **Déploiement Baseten Whisper Large V3 Turbo (optionnel)** — déploiement GPU dédié appelé via l'API predict de Baseten (`whisper_input.audio.audio_b64` → `segments[].text`) ; verbatim, sans garde-fous LLM (paroles chantées comprises). Activé via `META_BASETEN_WHISPER_URL` (+ `META_BASETEN_WHISPER_KEY`, `META_BASETEN_WHISPER_LANGUAGE`) ; facturé à la minute GPU sur l'organisation propriétaire
- **Endpoint ASR façon OpenAI (optionnel)** — `/v1/audio/transcriptions` façon Whisper (multipart `file` + `model`), activé via `META_TRANSCRIPTION_MODEL`/`META_TRANSCRIPTION_ENDPOINT`
- **whisper.cpp local (optionnel)** — `whisper-cli.exe` + un modèle GGML, entièrement hors ligne et verbatim (chants compris) ; utilisé en secours dès que `META_WHISPER_BIN`/`META_WHISPER_MODEL` sont définis
- Les audios longs sont découpés automatiquement en segments calibrés fournisseur (`AudioSplitter`, MP3 aligné sur les frames / WAV à en-tête régénéré), chacun transcrit puis concaténé avec des marqueurs `[Segment n/N]`
- **Cascade résiliente :** Muse Spark Open → whisper.cpp local → déploiement Whisper Baseten → endpoint façon OpenAI (`META_ASR_PRIORITY=local` inverse l'ordre). Un backend en échec (refus, binaire absent, crash, sortie vide) est journalisé et le suivant est essayé. Le modèle n'est pas déterministe (1 réponse de type assistant sur 3 appels identiques, mesuré) : ces réponses sont relancées jusqu'à `META_AUDIO_ATTEMPTS` (3) fois à température 0 — sauf les refus pour droit d'auteur, délibérés, transmis directement à whisper.cpp (les chants restent transcrits)
- *Historique :* whisper.cpp local a servi pendant le développement, faute de crédits Baseten gratuits ; avec les crédits hackathon (100 $ sur Muse Spark Open), la transcription repasse sur Baseten et le backend local reste disponible comme option hors ligne
- *Sécurité garde-fous :* le modèle peut refuser de transcrire mot à mot des paroles chantées protégées. Les refus sont détectés et rejetés avec un message clair et traduit — ils ne sont **jamais indexés** comme contenu du document (les enregistrements parlés ne sont pas concernés)

### Moteur de récupération de précision (RAG++)
- **Retrieval filtré par permissions** — chaque chunk scoré appartient à un document que l'acteur est autorisé à lire (tenant, adhésion d'espace, confidentialité, propriété, suppression logique), résolu en une passe par requête
- **Scoring hybride** — `0,6 × similarité cosinus + 0,4 × recouvrement lexical` conserve un bon rappel même avec les embeddings locaux
- **Découpage à la limite de mot** — les coupes à taille fixe reculent au dernier espace : aucun extrait ne se termine en plein mot
- **Expansion de contexte** — chaque extrait retenu est enrichi de ses chunks voisins ±1 **et du premier chunk du document**, là où vivent titres et en-têtes ; une question sur un titre est donc toujours répondable
- **Détection d'intention catalogue** — « liste tous les documents » contourne le retrieval de chunks et alimente le modèle avec l'inventaire réel filtré par permissions (titre · catégorie · date d'indexation · fichier)
- **Citations vérifiables** — le modèle cite `[n]` ; seuls les extraits **cités** sont affichés comme sources, avec leur numéro exact de contexte (`Sources : [3]` ⇢ entrée *3*). Pas de citation, pas de liste de sources
- **Pipeline multilingue complet** — la réponse suit la **langue de la question** ; les citations littérales sont traduites avec l'original entre parenthèses ; les **extraits de sources sont traduits** aussi via une passe dédiée à basse température
- **Téléchargement direct** — chaque source citée porte un lien `document_id` vers sa dernière version
- **Surlignage des termes** — les mots de la question sont surlignés dans les extraits de sources affichés

### Corrections humaines — la boucle d'apprentissage

Un modèle laisse toujours passer du vocabulaire ou des tournures. Un locuteur
natif — ou l'auteur du document — dépose une **version corrigée** (PDF, DOCX ou
texte) en précisant sa langue. Cette correction devient immédiatement la
**référence fiable** de ce document :

- **La recherche la privilégie.** Pour toute question ou tâche agentique, le
  texte corrigé remplace les segments bruts de ce document, et le contexte est
  étiqueté `VERSION CORRIGÉE <langue> <date>` pour que le modèle sache qu'il
  fait foi.
- **Les sources la citent.** La réponse liste la source avec un badge
  *Version corrigée* (langue + date) et un lien pour télécharger exactement le
  fichier utilisé.
- **Le fichier d'origine est conservé.** Le DOCX ou PDF déposé est stocké et
  servi **à l'octet près** (SHA-256 identique) — jamais converti.
- **Elle apprend au traducteur.** Les formulations validées sont injectées comme
  exemples de style dans les traductions suivantes (`Apprentissage` :
  pondération IDF, un exemple par document, budget de 2 600 caractères).
- **Elle est réversible.** Les corrections sont listées dans le modal du document
  et supprimables par leur auteur ou un gestionnaire ; la suppression cesse
  immédiatement d'alimenter le prompt.
- **Elle reste honnête sur sa portée.** C'est de l'apprentissage par
  récupération, pas un fine-tuning : le modèle est guidé, jamais réentraîné.

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

PDF (dompdf) et DOCX (OOXML natif) : titre, styles, pagination et texte justifié.

Une bascule **✦ nettoyer** dans le modal de résultat transforme une réponse
ancrée en livrable : elle retire la ligne `Sources : [n]`, les citations dans le
corps, les marqueurs de travail, la note de méthode et le bloc de référence
documentaire, masque les sources et la trace, et met à jour la charge utile de
l'export — le fichier ne contient plus que la réponse. Un second clic restaure
tout.

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
| **Tests** | **124 tests automatisés · 295 assertions** |
| **Sécurité** | Argon2 · CSP par nonce · CSRF · journal d'audit |
| **Périmètre** | **10 pages** · **31 endpoints** · **25 migrations** · **3 outils de vérification** |
| **UI** | Bilingue EN/FR (EN par défaut) — mooré géré pour le contenu (réponses, tâches, traduction), Bootstrap + DataTables + Select2 |

---

## Feuille de route — **pour le futur**

> Ces fonctionnalités **ne font pas partie du MVP soumis** : elles prolongent la plateforme et s'appuient sur ses fondations existantes.

**Collaboration avancée** — mentions · commentaires · notifications · discussions contextualisées.

**Pilotage organisationnel** — tableaux de bord décisionnels · indicateurs de performance · suivi de projets.

**IA avancée** — recommandations documentaires · détection des connaissances obsolètes · assistants spécialisés par métier.

---

## Installation

**Prérequis :** PHP 8.4 (extensions `pdo_mysql`, `curl`, `zip`, `mbstring`), MySQL 8 démarré, [Composer](https://getcomposer.org/).

**En un clic (Windows/WAMP) :** `setup.bat` exécute toutes les étapes ci-dessous (il télécharge `composer.phar` si Composer n'est pas installé).

```powershell
# 1. Dépendances (composer.phar n'est pas versionné : utiliser un Composer global)
composer install

# 2. Configuration locale — puis renseigner DB_* et META_MODEL_API_KEY
Copy-Item .env.example .env

# 3. Base de données — créée automatiquement si absente, puis toutes les migrations
php tools/migrate.php migrate

# 4. Premier administrateur + espace par défaut (idempotent)
php tools/seed_admin.php

# 5. Lancer en local
.\serve.bat
```

L'administrateur est créé à partir de `ADMIN_EMAIL` / `ADMIN_PASSWORD` (variables d'environnement optionnelles, sinon les valeurs par défaut de `tools/seed_admin.php`) — **changer le mot de passe après la première connexion**.

*Vérifié sur un `git clone` neuf avec une base vide : dépendances, migrations, seed, toute la suite de tests et connexion OK.*

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
php vendor/phpunit/phpunit/phpunit                  # 124 tests, 295 assertions
php tools/smoke_admin_endpoints.php                 # 27 vérifications sur 9 endpoints
php tools/smoke_endpoints_anonymous.php             # 31 endpoints × 2 méthodes, sans session
php tools/lint_docs.php                             # structure de la documentation
  php tools/prove_provenance.php 30                   # chaîne de provenance, octets vérifiés
```

**124 tests, 295 assertions** — couvrant l'authentification, le RBAC, les versions de documents, l'audit, le retrieval RAG filtré par permissions, les exports, les services multimodaux, le pipeline d'ingestion en arrière-plan, la boucle d'agent, le limiteur de débit, le modèle de correction et le moteur d'apprentissage (isolation par langue, pondération IDF, effet réel de la suppression). Aucun serveur de base de données ni API requis (SQLite en mémoire + doubles déterministes).

---

## Sécurité

Requêtes SQL préparées partout · CSRF sur chaque POST (formulaire **et** en-tête `X-CSRF-Token` ajouté par un wrapper `fetch`) · **CSP par nonce** (aucun script inline sans nonce) · hachage Argon2id · verrouillage progressif des connexions anti-force brute · RBAC résolu centralement (`PermissionService`) · refus par défaut · fichiers stockés hors racine web · aucun secret dans le code ou les logs · journal d'audit (ajout seul ; purge légale restreinte, re-authentifiée et elle-même journalisée) · en-têtes de sécurité (CSP).

---

## Documentation

| Document | 🇫🇷 Français | 🇬🇧 English |
|---|---|---|
| **Journal de construction — ce qui a réellement été fait, jour par jour** | [`JOURNAL_DE_CONSTRUCTION.fr.md`](docs/JOURNAL_DE_CONSTRUCTION.fr.md) | [`JOURNAL_DE_CONSTRUCTION.md`](docs/JOURNAL_DE_CONSTRUCTION.md) |
| **Référence technique** | [`ARCHITECTURE.fr.md`](docs/ARCHITECTURE.fr.md) | [`ARCHITECTURE.md`](docs/ARCHITECTURE.md) |
| **Guide de réalisation** | [`GUIDE_DE_REALISATION.fr.md`](docs/GUIDE_DE_REALISATION.fr.md) | [`GUIDE_DE_REALISATION.md`](docs/GUIDE_DE_REALISATION.md) |
| **Sécurité et confidentialité des données** | [`SECURITE_DONNEES_API.fr.md`](docs/SECURITE_DONNEES_API.fr.md) | [`SECURITE_DONNEES_API.md`](docs/SECURITE_DONNEES_API.md) |
| **Registre des problèmes (65 entrées : 22 résolues, 12 ouvertes, 31 incidents)** | [`REGISTRE_PROBLEMES.fr.md`](docs/REGISTRE_PROBLEMES.fr.md) | [`REGISTRE_PROBLEMES.md`](docs/REGISTRE_PROBLEMES.md) |
| **Démo vidéo & soumission** | [`DEMO_VIDEO_ET_SOUMISSION.fr.md`](docs/DEMO_VIDEO_ET_SOUMISSION.fr.md) | [`DEMO_VIDEO_ET_SOUMISSION.md`](docs/DEMO_VIDEO_ET_SOUMISSION.md) |
| **État de démonstration — comptes, espaces, documents, scénarios** | [`ETAT_DE_DEMO.fr.md`](docs/ETAT_DE_DEMO.fr.md) | [`ETAT_DE_DEMO.md`](docs/ETAT_DE_DEMO.md) |
| **Recette — exécution réelle sur base neuve** | [`RECETTE_AZ.fr.md`](docs/RECETTE_AZ.fr.md) | [`RECETTE_AZ.md`](docs/RECETTE_AZ.md) |
| **Registre des recommandations — ce qui est fait, ce qui reste** | [`RECOMMANDATIONS.fr.md`](docs/RECOMMANDATIONS.fr.md) | [`RECOMMANDATIONS.md`](docs/RECOMMANDATIONS.md) |
| **Marque et propriété intellectuelle** | [`MARQUE_ET_PROPRIETE.fr.md`](docs/MARQUE_ET_PROPRIETE.fr.md) | [`MARQUE_ET_PROPRIETE.md`](docs/MARQUE_ET_PROPRIETE.md) |


Chaque document existe **dans les deux langues**, l'anglais en nom de base et le français en `.fr.md`.

## Quels appels sont des appels au modèle Meta

Les organisateurs demandent à chaque projet de préciser quelles parties sont des
**appels au modèle Meta** et lesquelles sont des **fonctions du système** ou du
traitement local. Voici la liste complète ; rien n'y est déduit.

| Fonction | Ce qui s'exécute à l'exécution | Appel au modèle Meta ? |
|---|---|---|
| Répondre à une question | `meta-models/muse-spark-open-1026`, servi par Baseten | **oui** |
| Tâche agentique — plan, rédaction, vérification, conformité | le même modèle, plusieurs appels | **oui** |
| Traduction FR / EN / MO | le même modèle | **oui** |
| Compréhension d'images et de scans (repli OCR) | le même modèle, entrée visuelle | **oui** |
| Transcription audio — **chemin par défaut** | le même modèle, entrée audio | **oui** |
| Transcription audio — **repli seulement** | binaire local `whisper.cpp` | **non** — un programme sur la même machine, utilisé seulement si le modèle est injoignable |
| Score de récupération (cosinus + lexical) | arithmétique en PHP sur des vecteurs stockés | **non** |
| Enregistrement audio, analyse de fichiers, extraction de texte PDF | navigateur et PHP | **non** — fonctions du système et de bibliothèques |
| Synthèse vocale | non utilisée | — |

Deux conséquences, dites clairement :

- **Aucun logo Meta, aucune mascotte, aucun élément de marque** n'apparaît dans
  l'application, la page de présentation, ce dépôt ou la vidéo de démonstration. Les
  modèles sont nommés en texte simple, ce que les organisateurs autorisent.
- **Aucune synthèse vocale** : le projet ne parle jamais, donc la règle sur la voix
  de sortie ne le concerne pas.

### Modèles Meta ouverts utilisés, et où ils s'exécutent

Les organisateurs demandent aussi quels **modèles Meta ouverts** un projet utilise
et **où ils tournent** — un modèle Meta auto-hébergé compte, où qu'il tourne.

| Modèle | Nature | Où il s'exécute |
|---|---|---|
| `meta-models/muse-spark-open-1026` (Muse Spark Open) | poids ouverts de Meta | **Baseten**, un point d'entrée partenaire, via l'API compatible OpenAI |
| — | — | **aucun modèle Meta ne s'exécute sur la machine de l'auteur** |

Le binaire local `whisper.cpp` n'est **pas** un modèle Meta. C'est un repli qui ne
s'exécute que si le modèle est injoignable, et il est déclaré comme tel dans le
tableau ci-dessus. Les vecteurs de récupération sont calculés par hachage en PHP,
ce qui est de l'arithmétique, pas un modèle.

Cette configuration est explicitement éligible : les organisateurs ont confirmé le
2 novembre qu'un projet construit sur les modèles à poids ouverts de Meta **sur une
machine que vous fournissez** est éligible, et que **ne pas utiliser l'API Model ne
pénalise pas** (Hackathon Credit Terms, section 3.8). Aucun crédit Meta n'était
nécessaire, et aucun n'a été demandé.

---

## Ressources tierces et droits

Le règlement du hackathon exige de détenir les droits sur **chaque élément** d'un
projet et de sa vidéo de démonstration. Cette section indique, élément par
élément, à quel titre chacun est redistribué.

| Élément | Emplacement | Droits |
|---|---|---|
| **Bootstrap 5.3.3** | `public/assets/vendor/bootstrap` | MIT — la mention est conservée dans les fichiers distribués |
| **jQuery 3.7.1** | `public/assets/vendor/jquery` | MIT — la mention est conservée dans les fichiers distribués |
| **DataTables** (intégration Bootstrap 5) | `public/assets/vendor/datatables` | MIT — la mention est conservée dans les fichiers distribués |
| **Select2 4.1.0** | `public/assets/vendor/select2` | MIT — la mention est conservée dans les fichiers distribués |
| **Dépendances PHP** — dompdf, PHPMailer, pdfparser | `vendor/` (installé par Composer, non versionné) | chaque paquet fournit sa licence |
| **Ressources mooré** — le *Dictionnaire mooré–français–anglais* édité par Urs Niggli, et ses index de mots français/anglais | non diffusées : téléchargées localement dans `storage/moore_sources/`, exclues de ce dépôt | **© 2016 SIL International®**, publiées sur [Webonary](https://www.webonary.org/moore/) et reprises par [Mooré Burkina Faso](https://mooreburkina.com/fr/livres-en-moor%C3%A9/t%C3%A9l%C3%A9charger-pdf). À citer : *Niggli, Urs (éd.). 2016. Dictionnaire mooré – français. SIL International.* Utilisées par les outils d'import, **non redistribuées** |
| **Code de l'application** | le reste du dépôt | MIT, voir [LICENSE](LICENSE) |

**Lexique mooré.** Les outils d'import (`tools/import_moore_lexicon*.php`) peuvent lire le
dictionnaire SIL placé localement dans `storage/moore_sources/`. Les listes de mots produites
sont des données dérivées d'une œuvre citée ; les PDF d'origine restent sur la machine de
l'opérateur et sont exclus du dépôt par `.gitignore`.

**Vidéo de démonstration.** Aucune musique, aucune image de banque, aucune capture
ni logo tiers. Tout ce qui apparaît à l'écran est produit par l'application, à
partir de documents que les comptes de démonstration ont le droit de lire. Si une
piste sous licence est ajoutée un jour, sa source et sa licence figureront dans ce
tableau.

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
