# Démo vidéo (3 min) & soumission

🇬🇧 English version: [DEMO_VIDEO_ET_SOUMISSION.md](DEMO_VIDEO_ET_SOUMISSION.fr.md)

> Feuille de route finale — état du produit au **1ᵉʳ novembre 2026**.
> Enregistrement : **3 novembre**. Soumission : **avant le 4 novembre 2026, 21 h ET**.

---

## 1. Ce que la démo doit prouver (et rien de plus)

Trois idées, dans cet ordre. Tout le reste est du décor.

| # | Idée à faire passer | Preuve à l'écran |
|---|---|---|
| **1** | Les réponses sont **ancrées**, jamais inventées | Les sources numérotées sous chaque réponse |
| **2** | Le système **sait se contraindre** | La trace agentique en 6 étapes, dont *Cadrage* et *Conformité* |
| **3** | Il **apprend des humains** | Une version corrigée qui devient la référence, et qu'on peut télécharger |

> Un jury retient **une** chose. Fais en sorte que ce soit : *« il cite ses sources, et il refuse d'inventer. »*

---

## 2. Pré-vol — à faire la veille, pas le jour J

### 2.1 Vérifier que tout est poussé

```powershell
cd "C:\wamp64\www\projet hackaton\ai-knowledge-workspace"
git fetch origin
git status -sb
```

Attendu : `## main...origin/main` **sans** `[ahead N]`.
Si `[ahead N]` apparaît → `git push origin main`.

### 2.2 Vérifier que la suite est verte

```powershell
cd "C:\wamp64\www\projet hackaton\ai-knowledge-workspace"
C:\wamp64\bin\php\php8.4.27\php.exe -d memory_limit=1G vendor\phpunit\phpunit\phpunit --configuration phpunit.xml --no-coverage
```

Attendu : `OK (100 tests, 230 assertions)` — **sans** avertissement.

### 2.3 Vérifier l'état de la base de démonstration

```powershell
C:\wamp64\bin\mysql\mysql8.4.13\bin\mysql.exe -h 127.0.0.1 -P 3306 -u root -D ai_knowledge_workspace --default-character-set=utf8mb4 -e "SELECT id, title, langue, filename FROM documents WHERE supprime_le IS NULL ORDER BY id; SELECT id, document_id, langue, nom_fichier FROM correction_document;"
```

**Règle d'or** : **une seule** correction en base, celle de Meeting6.
Toute correction de test devient **prioritaire** dans les réponses et fausse la démo.

### 2.4 Vérifier la clé API et le serveur

```powershell
cd "C:\wamp64\www\projet hackaton\ai-knowledge-workspace"
.\serve.bat
```

Puis ouvrir **http://127.0.0.1:8080/** et faire **`Ctrl + F5`**.
⚠️ **Obligatoire à chaque prise** : un onglet ouvert avant un correctif exécute l'ancien JavaScript.

### 2.5 Réglages d'enregistrement

| Réglage | Valeur |
|---|---|
| Résolution | **1080p**, navigateur plein écran |
| Zoom navigateur | **110 %** (125 % si la police paraît petite) |
| Curseur | visible, mouvements lents |
| Pièce | calme, micro-casque |
| Onglets | **un seul** onglet du navigateur |

> ⚠️ **Ne jamais montrer** : la clé API, `.env`, un terminal contenant la clé, les données personnelles d'un tiers.

---

## 3. Scénario minuté — 3 min 00

### Version française (recommandée)

| Temps | Action à l'écran | Narration |
|---|---|---|
| **0:00–0:10** | Page d'accueil `/` | « Bonjour, je suis Anatole KABORE, développeur indépendant au Burkina Faso. Voici l'Agentic Knowledge Workspace. » |
| **0:10–0:25** | Scroll rapide sur la page de présentation | « Une organisation accumule des rapports, des procès-verbaux, des enregistrements. Le savoir est là — mais il est inexploitable. Les chatbots génériques, eux, inventent. » |
| **0:25–0:55** | Poser la question : **« Parle-moi de Meeting6 »** | « Je pose une question sur un document réel. Regardez la réponse : elle décrit **un enregistrement audio transcrit en français**, et signale qu'il existe une **traduction corrigée en mooré**. » <br> « Et sous la réponse, les sources exactes, numérotées, avec un badge quand il s'agit d'une version corrigée. » |
| **0:55–1:20** | Poser : **« Que dit Meeting6 en mooré ? »** | « La même question, en précisant la langue. La langue demandée l'emporte : il me donne le texte mooré, en citant la version corrigée du 31 octobre 2026. » |
| **1:20–1:50** | Page **Tâche agentique** → cocher **Meeting6** + **Formation** → instruction : *« À base de Meeting6 et du dossier projet, propose un discours de lancement officiel »* | « Maintenant, une tâche, pas une question. Je **choisis mes documents sources**. Il planifie, cherche, **borne le périmètre** à mes deux documents, rédige, **vérifie chaque affirmation**, puis **contrôle que sa sortie répond bien à ma demande**. » <br> « Six étapes, toutes visibles. » |
| **1:50–2:10** | Cliquer **✦ Nettoyer**, puis **DOCX** | « La proposition me convient. Un clic : les citations, les sources et les annotations de travail disparaissent. J'exporte en Word — le fichier ne contient que le discours, prêt à être prononcé. » |
| **2:10–2:35** | Page **Documents indexés** → ouvrir le modal de correction de Meeting6 | « Et il apprend. Une version corrigée a été déposée par un locuteur natif. Elle devient la référence : le système la substitue au texte brut, l'annonce dans ses sources, et permet de **retélécharger le fichier d'origine à l'octet près**. » |
| **2:35–3:00** | Retour à la page de présentation | « PHP 8.4, MySQL 8.4, cent tests automatisés, journal d'audit en ajout seul. Une connaissance métier réelle, amplifiée par les modèles de Meta. Merci. » |

### Version anglaise (si le jury est international)

| Time | On screen | Narration (EN) |
|---|---|---|
| **0:00–0:10** | Landing | "Hi, I'm Anatole Kabore, an independent developer from Burkina Faso. This is the Agentic Knowledge Workspace." |
| **0:10–0:25** | Landing scroll | "An organisation piles up reports, minutes, recordings. The knowledge is there — but unusable. Generic chatbots invent." |
| **0:25–0:55** | Ask **"Tell me about Meeting6"** | "A real document. The answer says what it actually is: **a French audio recording**, and mentions a **corrected Mooré translation**. Below it, the exact numbered sources." |
| **0:55–1:20** | Ask **"What does Meeting6 say in Mooré?"** | "Same question, language specified. The named language wins: it gives me the Mooré text, citing the corrected version." |
| **1:20–1:50** | **Agentic task** → select **Meeting6** + **Formation** → *"Based on Meeting6 and the project file, draft an official launch speech"* | "Now a task. I **pick the source documents**. It plans, retrieves, **restricts itself to my two documents**, drafts, **verifies every claim**, then **checks that its output answers my request**. Six steps, all visible." |
| **1:50–2:10** | **✦ Clean**, then **DOCX** | "The proposal works. One click removes citations, sources and working notes. Exported to Word — a speech, ready to deliver." |
| **2:10–2:35** | **Indexed documents** → Meeting6 correction modal | "And it learns. A corrected version was uploaded by a native speaker. It becomes the reference: substituted for the raw text, announced in the sources, and downloadable **byte for byte**." |
| **2:35–3:00** | Landing | "PHP 8.4, MySQL 8.4, one hundred automated tests, append-only audit journal. Real domain knowledge, amplified by Meta's models. Thank you." |

---

## 4. Parcours de secours

Si une séquence échoue pendant l'enregistrement, **ne t'arrête pas** — enchaîne sur le suivant.

| Problème | Repli |
|---|---|
| La tâche dépasse 30 s | Coupe au moment du `Draft`, dis « six étapes » et passe à la suite |
| La réponse est hors sujet | Reformule en une phrase plus courte, ou utilise une question déjà testée |
| Le mooré ne sort pas | Montre la **traduction** FR→MO sur `/traduire` (2,5 s, vérifiée) |
| Le modal de correction est vide | Montre le **badge** dans les sources de la réponse (même preuve, 3 s) |
| Plus aucune connexion API | Montre les **100 tests verts** + la page de présentation : le produit tient sans démo live |

> **Filet de sécurité** : enregistre **deux** prises. Garde la meilleure. Une vidéo un peu moins fluide vaut mieux qu'une vidéo absente.

---

## 5. Pièges connus — appris à nos dépens cette semaine

| Piège | Parade |
|---|---|
| **Ancien JavaScript en cache** → un correctif invisible | **`Ctrl + F5`** avant chaque prise. Les pages sont en `no-store`, mais un onglet ouvert avant un correctif garde l'ancien script |
| **Correction de test oubliée en base** → elle devient prioritaire et fausse les réponses | Vérifier `correction_document` avant de filmer (§2.3) |
| **Sélection de documents vide** → la tâche interroge tout le corpus | Cocher explicitement les documents avant de lancer la tâche |
| **Question trop ouverte** → réponse en 1 500 mots, illisible à l'écran | Utiliser les formulations testées du §3 |
| **Date en format ISO** dans une phrase | Corrigé : les dates s'écrivent « 31 octobre 2026 » / « 31 October 2026 » |
| **Bruit de notification Windows** | Mode « Ne pas déranger » activé |

---

## 6. Chiffres à citer (2 ou 3, pas plus)

| Métrique | Valeur |
|---|---|
| **Tests automatisés** | **100 tests · 230 assertions** (SQLite en mémoire, aucun serveur requis) |
| **Traçabilité** | **21 types d'actions** journalisées, refus compris, journal **en ajout seul** |
| **Pipeline agentique** | **6 étapes** : plan → retrieve → scope → draft → verify → conformity |
| **Retrieval** | Score hybride `0,6 sémantique + 0,4 lexical`, expansion aux voisins, **priorité aux corrections** |
| **Mooré** | Lexique SIL **1 349 entrées** vérifiées, **40 proverbes**, glossaire métier 20 termes, **6 directions** de traduction |
| **Latence traduction** | **≈ 2,5 s** (mesurée, sortie stable sur 3 exécutions) |
| **Sécurité** | Argon2id · CSRF · RBAC à 2 niveaux · rate limiting · journal append-only |
| **Origine terrain** | Né de **GES-SACREMENTS-KTO-BF**, déployé dans plusieurs paroisses du Burkina Faso |

> Pour la description écrite de la soumission : cite-les **tous**.
> Dans la vidéo : **deux ou trois**, au moment de la clôture.

---

## 7. Après l'enregistrement

- [ ] Durée **≤ 3 min 00** (couper si nécessaire — jamais de dépassement)
- [ ] Son audible, pas de pic de saturation
- [ ] Upload sur **YouTube (non répertorié)** ou **Loom**
- [ ] Vérifier la lecture **en navigation privée** (lien réellement partageable)
- [ ] Copier le lien

---

## 8. Checklist de soumission

### Dépôt

```powershell
cd "C:\wamp64\www\projet hackaton\ai-knowledge-workspace"
git fetch origin
git status -sb          # doit afficher : ## main...origin/main
git log --oneline -1    # noter le hash affiché
```

- [ ] Dépôt **public** : `https://github.com/AnKBORE/ai-knowledge-workspace`
- [ ] `README.md` + `README.fr.md` à jour
- [ ] `docs/ARCHITECTURE.md` + `docs/ARCHITECTURE.fr.md` présents
- [ ] `LICENSE` (MIT)
- [ ] **Aucun secret** versionné (`git ls-files | Select-String -Pattern '\.env$'` → vide)
- [ ] `composer install` + `php tools/migrate.php migrate` fonctionnent depuis un clone propre (§9)

### Vidéo

- [ ] ≤ 3 min, lien copié, lecture vérifiée en navigation privée

### Formulaire

- [ ] Nom : **Agentic Knowledge Workspace**
- [ ] Piste : **Indie Developer**
- [ ] Thème : **Build for Your World**
- [ ] Description courte (2–3 phrases, voir §6)
- [ ] URL du dépôt + URL de la vidéo
- [ ] Équipe : toi seul
- [ ] Identifiants de démo : compte de test, **jamais** la clé API

---

## 9. Test « clone propre » — à faire avant de soumettre

```powershell
cd C:\wamp64\www
git clone https://github.com/AnKBORE/ai-knowledge-workspace.git test-clone
cd test-clone
Copy-Item .env.example .env
# renseigner META_MODEL_API_KEY, META_MODEL_API_BASE_URL, META_API_STYLE, META_MODEL_NAME
C:\wamp64\bin\php\php8.4.27\php.exe tools\composer.phar install
C:\wamp64\bin\php\php8.4.27\php.exe tools\migrate.php migrate
C:\wamp64\bin\php\php8.4.27\php.exe -d memory_limit=1G vendor\phpunit\phpunit\phpunit --configuration phpunit.xml --no-coverage
```

Attendu : migrations appliquées **et** `OK (100 tests, 230 assertions)`.

> Si ça passe, le dépôt est **prêt pour le jury**.

---

## 10. Push — commandes exactes

```powershell
cd "C:\wamp64\www\projet hackaton\ai-knowledge-workspace"
git status -sb
git push origin main
git fetch origin
git status -sb
```

Attendu après le push :

```
Enumerating objects: …
To https://github.com/AnKBORE/ai-knowledge-workspace.git
   xxxxxxx..yyyyyyy  main -> main
## main...origin/main
```

Si le push échoue avec `schannel: AcquireCredentialsHandle failed`, c'est que les identifiants GitHub ne sont pas disponibles dans cette session : exécute la commande **dans ton propre PowerShell**, pas depuis un environnement sandboxé.

---

## 11. Après la soumission

- [ ] Recevoir la **confirmation par e-mail**
- [ ] **Ne plus modifier** le dépôt pendant la période de jugement
- [ ] Garder le dépôt **public**
- [ ] Surveiller les annonces du hackathon

---

## 12. Critères du jury (Indie Developer)

| Critère | Ce que la vidéo doit montrer |
|---|---|
| **Creativity** | L'angle « agentique + multimodal + mooré ». Le différenciateur n'est pas le chatbot : c'est le **pipeline low-resource** et la **boucle de correction humaine** |
| **Technical ambition** | PHP/MySQL sans framework, RAG hybride, pipeline agentique à 6 étapes, journal d'audit |
| **Functionality** | Une démo qui **fonctionne de bout en bout**, sans temps mort |

> La vidéo est **l'élément central** : montre le produit **réel**. Les slides ne sont qu'un emballage.
