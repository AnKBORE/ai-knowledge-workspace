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
| **4** | **L'interface et le serveur disent la même chose** | La session membre : six entrées de menu, zéro document, `/journal` en 403 |

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

## 3. Scénario minuté — 3 min 00

Dix séquences de 15 à 30 secondes. La narration est écrite pour être **dite**, pas
lue : environ 240 mots, ce qui laisse la place aux pauses et aux chargements.

| Temps | Action à l'écran | Narration |
|---|---|---|
| **0:00–0:15** | Page d'accueil `/` | « Je suis Anatole, développeur au Burkina Faso. Ici, les organisations perdent leur savoir : rapports sur papier, comptes rendus dans les boîtes mail, chants sur des téléphones. C'est de là que je suis parti. » |
| **0:15–0:40** | Poser : **« Parle-moi de Meeting6 »** | « Une question. La réponse n'est construite qu'avec les documents que ce compte a le droit de lire, et chaque affirmation porte une source numérotée. Si l'extrait manque, elle le dit au lieu d'inventer. » |
| **0:40–1:00** | Poser : **« Que dit Meeting6 en mooré ? »** | « Même document, autre langue. Les rapports sont écrits en français, mais les agents qui les utilisent travaillent en mooré — et un rapport que personne ne peut lire est un rapport qui n'existe pas. Le mooré est peu doté, donc les modèles génériques échouent : ici, la plateforme construit la ressource, et refuse d'inventer. » |
| **1:00–1:30** | `/tache` → cocher **Meeting6** + **Formation** → instruction | « Maintenant une tâche, pas une question : planifier, récupérer, cadrer, rédiger, vérifier, conformité. Le résultat est un document structuré avec ses sources. » |
| **1:30–1:45** | Cliquer **✦ Nettoyer**, puis **DOCX** | « Un clic retire l'échafaudage, un autre exporte. Le document quitte le navigateur avec ses citations. » |
| **1:45–2:00** | `/documents` → filtrer **« Espace demo »** | « Neuf documents, chacun montre ses espaces. Ce filtre est un espace privé : zéro résultat. L'accès se calcule par document, pas par écran. » |
| **2:00–2:15** | Icône cadenas sur **Meeting6** → modale | « L'administration reclasse un document et change qui le lit — avec son propre mot de passe, et l'avant comme l'après sont inscrits au journal. » |
| **2:15–2:30** | `/administration` → les trois onglets → couronne | « Trois onglets : comptes, espaces, adhésions. La couronne protège un compte : un Super Admin protégé ne peut être ni supprimé, ni suspendu, ni rétrogradé. » |
| **2:30–2:45** | Se connecter en `membre.demo` → `/documents` → `/journal` | « Même application, un membre : six entrées de menu au lieu de dix, zéro document, et le journal d'audit répond 403. Tout est là — l'interface et le serveur disent la même chose. » |
| **2:45–3:00** | Retour à `/` | « PHP 8.4, MySQL 8.4, 121 tests automatisés, journal d'audit append-only. Construit seul en neuf jours. Le dépôt est public, le lien est juste là. » |

**Deux règles pour la voix.** Ne pas lire le tableau : dire ces phrases à un rythme
naturel et laisser l'écran travailler. Et ne jamais s'excuser d'un accent — les
mots comptent plus que leur musique.

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

## 13. Ce qui a changé le 2 novembre — désormais intégré au §3

Les cinq gestes ajoutés le 2 novembre (badges et filtre d'espaces, modale de contrôle,
onglets d'administration, protection d'un compte, session membre) font partie du
scénario du **§3**. Rien d'autre n'a à tenir dans trois minutes.
