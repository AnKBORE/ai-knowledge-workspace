# Demo video (3 min) & submission

> Final roadmap — product state as of **1 November 2026**.
> Recording: **3 November**. Submission: **before 4 November 2026, 9 pm ET**.

🇫🇷 French version: [DEMO_VIDEO_ET_SOUMISSION.fr.md](DEMO_VIDEO_ET_SOUMISSION.fr.md)

---

## 1. What the demo must prove (and nothing more)

Four ideas, in this order. Everything else is decoration.

| # | Idea to land | Proof on screen |
|---|---|---|
| **1** | Answers are **grounded**, never invented | The numbered sources under every answer |
| **2** | The system **constrains itself** | The six-step agent trace, including *Scope* and *Conformity* |
| **3** | It **learns from humans** | A corrected version that becomes the reference, and can be downloaded |
| **4** | **The interface and the server agree** | The member session: six menu entries, zero documents, `/journal` answering 403 |

> A jury remembers **one** thing. Make it: *"it cites its sources, and it refuses to invent."*

---

## 2. Pre-flight — done the day before, not on the day

### 2.1 Verify everything is pushed

```powershell
cd "C:\wamp64\www\projet hackaton\ai-knowledge-workspace"
git fetch origin
git status -sb
```

Expected: `## main...origin/main` **without** `[ahead N]`.
If `[ahead N]` appears → `git push origin main`.

### 2.2 Verify the suite is green

```powershell
cd "C:\wamp64\www\projet hackaton\ai-knowledge-workspace"
C:\wamp64\bin\php\php8.4.27\php.exe -d memory_limit=1G vendor\phpunit\phpunit\phpunit --configuration phpunit.xml --no-coverage
```

Expected: `OK (121 tests, 281 assertions)` — **without** warnings.

### 2.3 Verify the demo database

```powershell
C:\wamp64\bin\mysql\mysql8.4.13\bin\mysql.exe -h 127.0.0.1 -P 3306 -u root -D ai_knowledge_workspace --default-character-set=utf8mb4 -e "SELECT id, title, langue, filename FROM documents WHERE supprime_le IS NULL ORDER BY id; SELECT id, document_id, langue, nom_fichier FROM correction_document;"
```

**Golden rule**: **one** correction in the database, the Meeting6 one.
Any test correction becomes **authoritative** in the answers and ruins the demo.

### 2.4 Verify the API key and the server

```powershell
cd "C:\wamp64\www\projet hackaton\ai-knowledge-workspace"
.\serve.bat
```

Then open **http://127.0.0.1:8080/** and press **`Ctrl + F5`**.
⚠️ **Mandatory on every take**: a tab opened before a fix runs the old JavaScript.

### 2.5 Recording settings

| Setting | Value |
|---|---|
| Resolution | **1080p**, browser full screen |
| Browser zoom | **110 %** (125 % if text looks small) |
| Cursor | visible, slow movements |
| Room | quiet, headset |
| Tabs | **one** browser tab only |

> ⚠️ **Never show**: the API key, `.env`, a terminal containing the key, a third party's personal data.

---

## 3. Timed script — 3:00

Ten sequences, 15 to 30 seconds each. The narration is written to be spoken, not
read: about 240 words, which leaves room for pauses and for the interface to load.

| Time | Action on screen | Narration |
|---|---|---|
| **0:00–0:15** | Landing page `/` | "I'm Anatole, a developer from Burkina Faso. Organisations there lose their knowledge: reports on paper, minutes in mailboxes, songs on phones. This is where I started." |
| **0:15–0:40** | Ask: **"Tell me about Meeting6"** | "One question. The answer is built only from documents this account may read, and every claim carries a numbered source. If the extract is missing, it says so instead of inventing." |
| **0:40–1:00** | Ask: **"What does Meeting6 say in Mooré?"** | "Same document, another language. The reports are written in French, but the agents who use them work in Mooré — and a report nobody can read is a report that does not exist. Mooré is low-resource, so generic models fail: here the platform builds the resource, and refuses to invent." |
| **1:00–1:30** | `/tache` → select **Meeting6** + **Formation** → instruction | "Now a task, not a question: plan, retrieve, scope, draft, verify, conformity. The result is a structured document with its sources." |
| **1:30–1:45** | Click **✦ Clean**, then **DOCX** | "One click removes the scaffolding, one exports it. The document leaves the browser with its citations." |
| **1:45–2:00** | `/documents` → filter **"Espace demo"** | "Nine documents, each showing its workspaces. This filter is a private workspace: zero results. Access is computed per document, not per screen." |
| **2:00–2:15** | Lock icon on **Meeting6** → modal | "Administration can re-file a document and change who reads it — with its own password, and the before and after are written to the journal." |
| **2:15–2:30** | `/administration` → the three tabs → crown again | "Three tabs: accounts, workspaces, memberships. The crown protects an account: a protected Super Admin can never be deleted, suspended or demoted." |
| **2:30–2:45** | Sign in as `membre.demo` → `/documents` → `/journal` | "Same application, a member: six menu entries instead of ten, zero documents, and the audit journal answers 403. That is the whole claim — the interface and the server agree." |
| **2:45–3:00** | Back to `/` | "PHP 8.4, MySQL 8.4, 121 automated tests, append-only audit. Built solo in nine days. The repository is public, the link is right there." |

**Two rules for the voice-over.** Do not read the table; speak these sentences at
a natural pace and let the screen do the work. And never apologise for an accent:
the words matter more than the music of them.

## 4. Fallback paths

If a sequence fails while recording, **do not stop** — move to the next one.

| Problem | Fallback |
|---|---|
| The task takes longer than 30 s | Cut at the `Draft` step, say "six steps" and move on |
| The answer is off-topic | Rephrase in one shorter sentence, or use an already-tested question |
| Mooré does not come out | Show the FR→MO **translation** on `/traduire` (2.5 s, verified) |
| The correction modal is empty | Show the **badge** in the answer sources (same proof, 3 s) |
| No API connection left | Show the **100 green tests** + the landing page: the product stands without a live demo |

> **Safety net**: record **two** takes. Keep the better one. A slightly less fluid video is better than no video.

---

## 5. Known pitfalls — learned the hard way this week

| Pitfall | Guard |
|---|---|
| **Stale JavaScript in cache** → an invisible fix | **`Ctrl + F5`** before every take. Pages are `no-store`, but a tab opened before a fix keeps the old script |
| **Forgotten test correction** → it becomes authoritative and skews answers | Check `correction_document` before recording (§2.3) |
| **Empty document selection** → the task queries the whole corpus | Explicitly check the documents before launching a task |
| **Question too open** → a 1,500-word answer, unreadable on screen | Use the tested phrasings from §3 |
| **ISO date** inside a sentence | Fixed: dates are written "31 October 2026" / "31 octobre 2026" |
| **Windows notification noise** | "Do not disturb" enabled |

---

## 6. Numbers to quote (two or three, not more)

| Metric | Value |
|---|---|
| **Automated tests** | **121 tests · 281 assertions** (in-memory SQLite, no server needed) |
| **Traceability** | **21 action types** journaled, refusals included, **append-only** journal |
| **Agentic pipeline** | **6 steps**: plan → retrieve → scope → draft → verify → conformity |
| **Retrieval** | Hybrid score `0.6 semantic + 0.4 lexical`, neighbour expansion, **priority to corrections** |
| **Mooré** | SIL lexicon **1,349 entries**, **40 proverbs**, 20-term business glossary, **6 translation directions** |
| **Translation latency** | **≈ 2.5 s** (measured, stable across 3 runs) |
| **Security** | Argon2id · CSRF · two-level RBAC · rate limiting · append-only journal |
| **Field origin** | Born from **GES-SACREMENTS-KTO-BF**, deployed in several parishes in Burkina Faso |

> For the written submission description: quote them **all**.
> In the video: **two or three**, at the closing moment.

---

## 7. After recording

- [ ] Duration **≤ 3:00** (cut if necessary — never overrun)
- [ ] Audible sound, no clipping
- [ ] Upload to **YouTube (unlisted)** or **Loom**
- [ ] Verify playback **in a private window** (the link is really shareable)
- [ ] Copy the link

---

## 8. Submission checklist

### Repository

```powershell
cd "C:\wamp64\www\projet hackaton\ai-knowledge-workspace"
git fetch origin
git status -sb          # must show: ## main...origin/main
git log --oneline -1    # note the hash
```

- [ ] Public repository: `https://github.com/AnKBORE/ai-knowledge-workspace`
- [ ] `README.md` + `README.fr.md` up to date
- [ ] `docs/ARCHITECTURE.md` + `docs/ARCHITECTURE.fr.md` present
- [ ] `docs/JOURNAL_DE_CONSTRUCTION.md` + `.fr.md` present
- [ ] `LICENSE` (MIT)
- [ ] **No secret** committed (`git ls-files | Select-String -Pattern '\.env$'` → empty)
- [ ] `composer install` + `php tools/migrate.php migrate` work from a clean clone (§9)

### Video

- [ ] ≤ 3 min, link copied, playback verified in a private window

### Form

- [ ] Name: **Agentic Knowledge Workspace**
- [ ] Track: **Indie Developer**
- [ ] Theme: **Build for Your World**
- [ ] Short description (2–3 sentences, see §6)
- [ ] Repository URL + video URL
- [ ] Team: you alone
- [ ] Demo credentials: test account, **never** the API key

---

## 9. Clean-clone test — before submitting

```powershell
cd C:\wamp64\www
git clone https://github.com/AnKBORE/ai-knowledge-workspace.git test-clone
cd test-clone
Copy-Item .env.example .env
# fill in META_MODEL_API_KEY, META_MODEL_API_BASE_URL, META_API_STYLE, META_MODEL_NAME
C:\wamp64\bin\php\php8.4.27\php.exe tools\composer.phar install
C:\wamp64\bin\php\php8.4.27\php.exe tools\migrate.php migrate
C:\wamp64\bin\php\php8.4.27\php.exe -d memory_limit=1G vendor\phpunit\phpunit\phpunit --configuration phpunit.xml --no-coverage
```

Expected: migrations applied **and** `OK (121 tests, 281 assertions)`.

> If that passes, the repository is **ready for the jury**.

---

## 10. Push — exact commands

```powershell
cd "C:\wamp64\www\projet hackaton\ai-knowledge-workspace"
git status -sb
git push origin main
git fetch origin
git status -sb
```

Expected after the push:

```
Enumerating objects: …
To https://github.com/AnKBORE/ai-knowledge-workspace.git
   xxxxxxx..yyyyyyy  main -> main
## main...origin/main
```

If the push fails with `schannel: AcquireCredentialsHandle failed`, GitHub
credentials are not available in this session: run the command **in your own
PowerShell**, not from a sandboxed environment.

---

## 11. After submission

- [ ] Receive the **confirmation e-mail**
- [ ] **Do not modify** the repository during judging
- [ ] Keep the repository **public**
- [ ] Watch the hackathon announcements

---

## 12. Jury criteria (Indie Developer)

| Criterion | What the video must show |
|---|---|
| **Creativity** | The "agentic + multimodal + Mooré" angle. The differentiator is not the chatbot: it is the **low-resource pipeline** and the **human correction loop** |
| **Technical ambition** | PHP/MySQL without a framework, hybrid RAG, six-step agentic pipeline, audit journal |
| **Functionality** | A demo that **works end to end**, without dead time |

> The video is **the central element**: show the **real** product. Slides are only packaging.

## 13. What changed on 2 November — now integrated into section 3

The five gestures added on 2 November (workspace badges and filter, control modal,
administration tabs, account protection, member session) are part of the script in
**section 3**. Nothing else needs to be fitted into three minutes.
