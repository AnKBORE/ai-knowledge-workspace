# Démo vidéo (3 min) & soumission

🇬🇧 English version: [DEMO_VIDEO_ET_SOUMISSION.md](DEMO_VIDEO_ET_SOUMISSION.md)

> Feuille de route finale — état du produit au **2 novembre 2026**.
> Enregistrement : **3 novembre**. Soumission : **avant le 4 novembre 2026, 21 h ET**.

> **Décision (2 novembre 2026) :** la voix off est en **anglais**. Le scénario anglais
> fait foi pour l'enregistrement ; cette version française conserve les mêmes minutages et
> sert de référence de traduction, pour un sous-titrage éventuel.

---

### Exigences confirmées par les organisateurs (2 novembre, 15:09)

| Règle | Source |
|---|---|
| **Le montage est autorisé** — la vidéo n'a pas à être une prise continue | Cerebral Valley, `#office-hours` |
| **3 min 00 maximum**, et les juges arrêtent à 3:00 | idem |
| **Compréhensible en anglais** | idem |
| **Les appels au modèle Meta doivent être visibles** | idem |
| **Accélérer les étapes longues, sans couper le cœur du parcours** | idem |
| Mettre des **preuves dans le dépôt** (sorties enregistrées, journaux, captures) | idem |
| **Aucune version hébergée exigée** — exécutable depuis les instructions d'installation, montré en vidéo | Cerebral Valley, 28 octobre |
| **La clé de l'API Model reste sur le serveur, jamais dans le code client** | idem |
| **Le dépôt doit rester public** | idem |
| **Soumettre sur** `https://cerebralvalley.ai/e/meta-ai-developer-hackathon/hackathon/submit` **avant mer. 4 nov., 21 h ET** | idem |
| **Les modèles à poids ouverts sur votre propre machine sont éligibles ; ne pas utiliser l'API Model ne pénalise pas** (Credit Terms §3.8) | Cerebral Valley, 2 novembre |

C'est pour cette dernière règle que chaque appel au modèle écrit désormais une
ligne dans `storage/logs/model_calls.log`, et que l'enregistrement la montre.

---

### Pondération du jury — annoncée le 2 novembre, 15:03

| Critère | Poids | Ce que la vidéo doit donc porter |
|---|---|---|
| **Impact** | **30 %** | le problème, les personnes, et ce que ça change dans leur travail |
| **Usage de l'outillage Meta** | **25 %** | où se situent les modèles Meta dans le parcours, montré en direct |
| **Profondeur technique et exécution** | **20 %** | la chaîne tient dans des conditions réelles |
| **Démo** | **25 %** | le produit, de bout en bout, sans diapositives |

Conséquences pour le scénario ci-dessous : la première phrase dit **ce qui existait
avant l'événement et ce qui a été construit cette semaine**, les appels au modèle Meta
sont montrés en direct à 2:40, et la phrase de clôture revient sur l'impact plutôt
que sur la pile technique.

---

## 1. Ce que la démo doit prouver (et rien de plus)

Trois idées, dans cet ordre. Tout le reste est du décor.

| # | Idée à faire passer | Preuve à l'écran |
|---|---|---|
| **1** | Les réponses sont **ancrées**, jamais inventées | Les sources numérotées sous chaque réponse |
| **2** | Le système **sait se contraindre** | La trace agentique en 6 étapes, dont *Cadrage* et *Conformité* |
| **3** | Il **apprend des humains** | Une version corrigée qui devient la référence, et qu'on peut télécharger |
| **4** | **L'interface et le serveur disent la même chose** | La session membre : six entrées de menu, zéro document, `/journal` en 403 |
| **5** | **L'enregistrement est l'original, pas un sous-produit** | Le fichier audio, son SHA-256 et sa transcription côte à côte ; une correction conserve le texte source à côté du corrigé |

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

Attendu : `OK (121 tests, 281 assertions)` — **sans** avertissement.

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

## 3. Scénario minuté — 2 min 50

Neuf séquences. La **capture en direct** ouvre la vidéo : la thèse est que le savoir
se dit avant de s'écrire, donc la démonstration commence par enregistrer une voix et
poser une question dessus trois minutes plus tard. Le montage est autorisé : une
transcription lente est **accélérée au montage**, jamais coupée.

| Temps | Action à l'écran | Narration |
|---|---|---|
| **0:00–0:08** | Page d'accueil `/` | « Avant cette semaine, cette organisation n'avait aucun moyen de chercher dans ses propres enregistrements. En neuf jours, j'en ai construit un. Regardez ce qui arrive à une voix dans les trois prochaines minutes. » |
| **0:08–0:48** | `/ajouter` → **enregistrer 12 secondes**, arrêter, envoyer | « J'enregistre une phrase que personne n'a écrite nulle part : *le chantier de Koudougou démarre le 12 janvier, budget quatre millions.* L'audio est conservé comme original, son empreinte calculée, puis transcrit et indexé. » |
| **0:48–1:10** | `/` → poser **« Quand démarre le chantier de Koudougou ? »** | « Un fait qui n'existe que sous forme de son, il y a trente secondes. La réponse revient avec sa source : l'enregistrement. Tout est là — un rapport que personne ne peut entendre n'existe pas. » |
| **1:10–1:30** | Poser **« Que dit Meeting6 en mooré ? »** | « Même moteur, autre langue. Les rapports sont écrits en français, les agents travaillent en mooré. Le mooré est peu doté, donc les modèles génériques échouent : la plateforme construit la ressource et refuse d'inventer. » |
| **1:30–1:58** | `/tache` → deux documents → instruction → **✦ Nettoyer** → **DOCX** | « Maintenant une tâche : planifier, récupérer, cadrer, rédiger, vérifier, conformité. Un clic retire l'échafaudage, un autre exporte le document avec ses citations. » |
| **1:58–2:28** | `/documents` → filtre → modale cadenas → `/administration` onglets + couronne | « Neuf documents, chacun avec ses espaces ; cet espace privé renvoie zéro. L'administration reclasse un document et change qui le lit, avec son mot de passe. La couronne protège un compte qui ne peut être ni supprimé ni rétrogradé. » |
| **2:28–2:40** | Connexion `membre.demo` → `/documents` → `/journal` | « Même application, un membre : zéro document, et le journal d'audit répond 403. L'interface et le serveur disent la même chose. » |
| **2:40–2:47** | Terminal à côté du navigateur : le journal des appels | « Muse Spark Open est un modèle à poids ouverts de Meta, sur Baseten — pour les réponses, la tâche agentique, la traduction, la vision et l'audio. Chaque appel est écrit : point d'entrée, modèle, statut, durée. En direct, et conservé dans le dépôt. » |
| **2:47–2:50** | Retour à `/` | « 121 tests, un journal d'audit, et un savoir qui disparaissait répond désormais aux questions. Construit seul, en neuf jours. » |

### Avant la prise — les quatre choses qui font marcher l'ouverture

| # | Préparation |
|---|---|
| 1 | **Micro autorisé** dans Chrome pour `127.0.0.1` (un essai à blanc, puis recharger) |
| 2 | Une **phrase que tu n'as écrite nulle part**, avec une date et un nombre : c'est elle qui prouve que la réponse vient de l'enregistrement |
| 3 | **Répéter l'envoi deux fois** : enregistrer → arrêter → envoyer → attendre *indexé*. Noter la durée réelle ; si elle dépasse 20 s, prévoir l'accéléré |
| 4 | **Repli** si le micro lâche devant la caméra : envoyer un WAV de 12 secondes par le même formulaire — la chaîne reste identique, seul l'enregistrement est préexistant |

```powershell
# le plan sur le journal (2:40)
Get-Content "C:\wamp64\www\projet hackaton\ai-knowledge-workspace\storage\logs\model_calls.log" -Wait -Tail 5
```


> **Pourquoi cette phrase compte.** *Usage de l'outillage Meta* pèse 25 %. Un modèle
> employé sur cinq capacités — réponses, tâche agentique, traduction, vision, audio —
> est un argument plus fort qu'un modèle employé une fois, et le conseil donné par les
> organisateurs à un participant SAM 3D dit la même chose : associer des modèles plutôt
> qu'en utiliser un seul. La clé hackathon Baseten ne sert que
> `meta-models/muse-spark-open-1026` ; l'étendue de l'usage est donc la façon honnête
> de marquer ce critère.

**Deux règles pour la voix.** Ne pas lire le tableau : dire ces phrases à un rythme
naturel et laisser l'écran travailler. Et ne jamais s'excuser d'un accent.

## 4. Parcours de secours

Si une séquence échoue pendant l'enregistrement, **ne t'arrête pas** — enchaîne sur le suivant.

| Problème | Repli |
|---|---|
| La tâche dépasse 30 s | Coupe au moment du `Draft`, dis « six étapes » et passe à la suite |
| La réponse est hors sujet | Reformule en une phrase plus courte, ou utilise une question déjà testée |
| Le mooré ne sort pas | Montre la **traduction** FR→MO sur `/traduire` (2,5 s, vérifiée) |
| Le modal de correction est vide | Montre le **badge** dans les sources de la réponse (même preuve, 3 s) |
| Plus aucune connexion API | Montre les **121 tests verts** + la page de présentation : le produit tient sans démo live |

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
| **Tests automatisés** | **121 tests · 281 assertions** (SQLite en mémoire, aucun serveur requis) |
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
- [ ] **Formulaire de soumission** : `https://cerebralvalley.ai/e/meta-ai-developer-hackathon/hackathon/submit` — avant mer. 4 nov., 21 h ET
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

Attendu : migrations appliquées **et** `OK (121 tests, 281 assertions)`.

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

**Deux artefacts seulement sont jugés** : le dépôt public et la vidéo de 3 minutes.
Les organisateurs ont confirmé le 2 novembre que **rien d'autre** n'est attendu —
pas de démo en direct, pas de questions-réponses, pas de présentation.

| Date | Ce qui se passe |
|---|---|
| **mer. 4 nov., 21 h ET** | clôture des soumissions |
| **lun. 9 nov., 12 h ET** | cérémonie de clôture : le **top 10** (5 par piste) est annoncé, avec le jugement final en direct — `https://cerebralvalley.ai/e/meta-ai-developer-hackathon/live` |
| **lun. 16 nov.** | résultat du vote de la communauté, annoncé sur Discord (une voix par compte vérifié) |

Donc : rien à répéter après la soumission, et aucun environnement à maintenir allumé.

- [ ] Recevoir l'**e-mail de confirmation**
- [ ] **Ne pas modifier** le dépôt pendant le jugement
- [ ] Garder le dépôt **public**
- [ ] Suivre `#announcements` — les mises à jour officielles ne viennent que de là

---

## 12. Critères du jury (Indie Developer)

| Critère | Ce que la vidéo doit montrer |
|---|---|
| **Creativity** | L'angle « agentique + multimodal + mooré ». Le différenciateur n'est pas le chatbot : c'est le **pipeline low-resource** et la **boucle de correction humaine** |
| **Technical ambition** | PHP/MySQL sans framework, RAG hybride, pipeline agentique à 6 étapes, journal d'audit |
| **Functionality** | Une démo qui **fonctionne de bout en bout**, sans temps mort |

> La vidéo est **l'élément central** : montre le produit **réel**. Les slides ne sont qu'un emballage.

## 13. Ce qui a changé le 2 novembre — désormais intégré au §3

Les cinq gestes ajoutés le 2 novembre (badges et filtre d'espaces, modale de contrôle,
onglets d'administration, protection d'un compte, session membre) font partie du
scénario du **§3**. Rien d'autre n'a à tenir dans trois minutes.
