# Démo vidéo (3 min) & soumission

> Feuille de route finale : préparer l'environnement, enregistrer la vidéo, vérifier le dépôt et **soumettre avant le 4 novembre 2026, 21 h ET**.

---

## 1. Avant d'enregistrer (préparation)

### Environnement
- [ ] `serve.bat` lancé → UI ouverte sur **http://127.0.0.1:8080/** (interface en **anglais** par défaut — le sélecteur FR/EN est visible en haut)
- [ ] Base nettoyée : garder 2-3 documents variés (un rapport PDF, un audio, un doc simple)
- [ ] Questions de démo prêtes :
  - **« What are the main risks? »** → réponse + sources numérotées
  - **« List all documents »** → démontre le catalogue intent
  - Une question sur l'**audio transcrit** → montre l'ASR local
- [ ] Tâche de démo prête : **« Prepare a note for the director on this quarter's activities »**
- [ ] Clé API configurée et **testée** (Baseten) — whisper.cpp local actif (zéro crédit pour l'audio)
- [ ] Préparer l'export **PDF + DOCX** pour montrer la mise en forme pro (titre, pagination, justifié)

### Matériel
- Enregistreur : **OBS Studio**, **Loom**, ou Game Bar (`Win + G`)
- Micro-casque, pièce calme, pas de bruit de fond
- Résolution **1080p**, navigateur en plein écran, zoom 110–125 %
- Police lisible, curseur visible

> ⚠️ **Ne jamais montrer** : ta clé API, `.env`, le document client (OFFRE TECHNIQUE), ni un onglet contenant des données personnelles.

---

## 2. Script (3 min) — narration anglaise (jury international)

| Temps | Plan | À l'écran | Narration (EN) |
|---|---|---|---|
| **0:00–0:15** | Intro | Logo + titre du projet | *"Hi, I'm Anatole Kabore, an independent developer from Burkina Faso. This is the Agentic Multimodal Knowledge Workspace — it turns an organization's documents into answers, with sources."* |
| **0:15–0:45** | Problème | Slide « The problem » | *"Every organization drowns in documents: reports, policies, meeting notes. Finding the right answer takes hours, and generic chatbots hallucinate. The knowledge is there — but it's unusable."* |
| **0:45–1:15** | Solution | Slide « The solution » | *"The Agentic Multimodal Knowledge Workspace indexes your documents, retrieves the relevant passages and generates grounded answers — with the exact sources cited. And it goes beyond Q&A: it drafts notes and reports from your own knowledge base."* |
| **1:15–2:15** | **Démo** | Écran réel | 1) Upload → *"Indexed."* 2) Question → **réponse + sources numérotées** : *"See the answer — and the exact extracts it cites, numbered."* 3) Tâche → **brouillon** : *"A full draft, generated from the same knowledge base."* 4) Export PDF : *"…exported with professional formatting."* 5) (si le temps) audio transcrit ou bascule FR/EN |
| **2:15–2:45** | Technique | Slide « Under the hood » | *"PHP 8.4.27, MySQL 8.4.13, strict MVC, permission-scoped retrieval, numbered verifiable citations — and audio transcription running locally on whisper.cpp, with the Baseten API pipeline wired as an interchangeable backend."* |
| **2:45–3:00** | Clôture | Slide final | *"This is Build for Your World: real domain knowledge, amplified by Meta's models. Thank you."* |

### Version française (si tu préfères enregistrer en français)

| Temps | Narration (FR) |
|---|---|
| 0:00–0:15 | « Bonjour, je suis Anatole KABORE, développeur indépendant au Burkina Faso. Voici l'Agentic Multimodal Knowledge Workspace : il transforme les documents d'une organisation en réponses, avec leurs sources. » |
| 0:15–0:45 | « Chaque organisation croule sous les documents : rapports, procédures, comptes rendus. Trouver la bonne réponse prend des heures, et les chatbots génériques hallucinent. Le savoir est là, mais inexploitable. » |
| 0:45–1:15 | « La plateforme indexe vos documents, retrouve les passages pertinents et génère des réponses ancrées, avec les sources citées. Et elle va plus loin : elle rédige des notes et des rapports à partir de votre base de connaissances. » |
| 1:15–2:15 | Démo : téléverser → « Indexé. » ; question → « La réponse, et les extraits exacts cités, numérotés. » ; tâche → « Un brouillon complet. » ; export PDF → « …exporté avec une mise en page professionnelle. » |
| 2:15–2:45 | « PHP 8.4.27, MySQL 8.4.13, architecture MVC stricte, et les modèles open-weight de Meta — Muse Spark Open via Baseten, avec Muse Wonder en option. Les embeddings tournent en local. » |
| 2:45–3:00 | « C'est Build for Your World : une connaissance métier réelle, amplifiée par les modèles de Meta. Merci. » |

---

## 3. Après l'enregistrement

- [ ] Durée **≤ 3 min** (vérifier)
- [ ] Upload sur **YouTube (non répertorié)** ou **Loom**
- [ ] Vérifier que la vidéo se lit **sans connexion** et que le lien est **public/partageable**
- [ ] Copier le **lien** pour la soumission

---

## 4. Checklist de soumission

### Dépôt
- [ ] Dépôt **public** → `https://github.com/AnKBORE/ai-knowledge-workspace` ✅
- [ ] `README.md` + `README.fr.md` à jour ✅
- [ ] `LICENSE` (MIT) ✅
- [ ] **Aucun secret** dans le dépôt (`.env` ignoré) ✅
- [ ] `composer install` + `php tools/migrate.php migrate` fonctionnent depuis un **clone propre**

### Vidéo
- [ ] ≤ 3 min, son audible, démo réelle
- [ ] Lien copié

### Formulaire
- [ ] Nom du projet : **Agentic Knowledge Workspace**
- [ ] Piste : **Indie Developer**
- [ ] Thème : **Build for Your World**
- [ ] Description courte (2–3 phrases)
- [ ] URL du dépôt
- [ ] URL de la vidéo
- [ ] Membres de l'équipe (toi seul)
- [ ] Identifiants de démo (si demandé — **pas** de vraie clé)

---

## 5. Chiffres à citer (description / vidéo)

Des mesures concrètes pèsent plus que les adjectifs :

| Métrique | Valeur à citer |
|---|---|
| **Tests automatisés** | **79 tests, 179 assertions** (SQLite en mémoire + doubles déterministes) |
| **Couverture sécurité** | Argon2 · CSRF · RBAC deny-by-default (permissions globales + par espace) · confidentialité `prive` · soft-delete · journalisation de chaque action |
| **Retrieval** | Scoring hybride `0.6 sémantique + 0.4 lexical + boost titre` · expansion voisins ±1 + chunk 0 · détection d'intention catalogue · retry « not found » (topK ×2) |
| **Citations** | Seuls les extraits **réellement cités** sont affichés, avec leur numéro exact de contexte — traçabilité 1:1 |
| **Multilingue** | FR/EN complet : 7 pages, erreurs API, fallbacks RAG, extraits de sources traduits (passe dédiée t°0.1) |
| **Audio** | whisper.cpp local (verbatim, 0 crédit) **+** pipeline Baseten interchangeable — audios > 10 min auto-découpés |
| **Exports** | PDF (dompdf) + DOCX OOXML natif : titre, styles, pagination, texte justifié |
| **Origine terrain** | Né de **GES-SACREMENTS-KTO-BF**, déjà déployé dans plusieurs paroisses du Burkina Faso |

> Astuce vidéo : cite **2–3** de ces chiffres au moment « Under the hood » (0:15 dans le script), pas tous — le reste va dans la description de la soumission.

---

## 5. Test « clone propre » (à faire avant de soumettre)

Sur un dossier vide :

```powershell
git clone https://github.com/AnKBORE/ai-knowledge-workspace.git test-clone
cd test-clone
Copy-Item .env.example .env
# renseigner META_MODEL_API_KEY + META_MODEL_API_BASE_URL + META_API_STYLE + META_MODEL_NAME
C:\wamp64\bin\php\php8.4.27\php.exe tools\composer.phar install
C:\wamp64\bin\php\php8.4.27\php.exe tools\migrate.php migrate
```

> Si ça fonctionne, le dépôt est **prêt pour le jury**.

---

## 6. Après la soumission

- [ ] Recevoir la **confirmation** par e-mail
- [ ] **Ne pas modifier** le dépôt pendant la période de jugement (5–8 novembre)
- [ ] Garder le dépôt **public**
- [ ] Surveiller les **#office-hours** / #questions pour les annonces

---

## 7. Rappel des critères (Indie Developer)

| Critère | Ce que la vidéo doit montrer |
|---|---|
| **Creativity** | L'angle « agentique + multimodal », pas un simple chatbot |
| **Technical ambition** | PHP/MySQL robuste + RAG + tâches + modèles Meta |
| **Functionality** | Une démo qui **fonctionne** de bout en bout |

> La vidéo est **l'élément central** : montre la démo **réelle**, pas des slides seuls.
