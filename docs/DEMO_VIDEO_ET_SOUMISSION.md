# Demo video (3 min) & submission

> Final roadmap — product state as of **2 November 2026**.
> Recording: **3 November**. Submission: **before 4 November 2026, 9 pm ET**.

> **Decision (2 November 2026):** the narration is in **English**. The script below is
> the reference to record; the French version keeps the same timings and serves as a
> translation, for subtitles if they are ever needed.

🇫🇷 French version: [DEMO_VIDEO_ET_SOUMISSION.fr.md](DEMO_VIDEO_ET_SOUMISSION.fr.md)

---

### Requirements confirmed by the organizers (2 November, 15:09)

| Rule | Source |
|---|---|
| **Editing is allowed** — the video does not have to be one continuous take | Cerebral Valley, `#office-hours` |
| **3:00 or less**, and judges stop at 3:00 | idem |
| **Understandable in English** | idem |
| **The live Meta model calls must be visible** | idem |
| **Speed up long-running steps; do not cut the core flow** | idem |
| Put **provenance in the repository** (recorded outputs, logs, captures) | idem |
| **No hosted version needed** — runnable from the setup instructions, shown in the video | Cerebral Valley, 28 October |
| **The Model API key stays on the server, never in client code** | idem |
| **Keep the repository public** | idem |
| **Submit at** `https://cerebralvalley.ai/e/meta-ai-developer-hackathon/hackathon/submit` **before Wed 4 Nov, 9:00 PM ET** | idem |
| **Open-weight models on your own compute are eligible; not using the Model API does not count against you** (Credit Terms §3.8) | Cerebral Valley, 2 November |

That last rule is why every model call now writes a line to
`storage/logs/model_calls.log`, and why the recording shows it.

---

### Judging weights — announced 2 November, 15:03

| Criterion | Weight | What the video must therefore carry |
|---|---|---|
| **Impact** | **30 %** | the problem, the people, and why it changes their work |
| **Use of Meta AI Tooling** | **25 %** | where the Meta models sit in the flow, shown live |
| **Technical Depth and Execution** | **20 %** | the chain holds under real conditions |
| **Demo** | **25 %** | the product, end to end, no slides |

Consequences for the script below: the first line states **what existed before the
event and what was built this week**, the Meta model calls are shown live at 2:40,
and the closing sentence returns to the impact rather than to the stack.

---

## 1. What the demo must prove (and nothing more)

Five ideas, in this order. Everything else is decoration.

| # | Idea to land | Proof on screen |
|---|---|---|
| **1** | Answers are **grounded**, never invented | The numbered sources under every answer |
| **2** | The system **constrains itself** | The six-step agent trace, including *Scope* and *Conformity* |
| **3** | It **learns from humans** | A corrected version that becomes the reference, and can be downloaded |
| **4** | **The interface and the server agree** | The member session: six menu entries, zero documents, `/journal` answering 403 |
| **5** | **The recording is the original, not a by-product** | The audio, its SHA-256 and its transcript side by side; a correction keeps the source text next to the corrected one |

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

Expected: `OK (124 tests, 295 assertions)` — **without** warnings.

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

## 3. Timed script — 2:50

Nine sequences. The **live capture** opens the video: the thesis is that knowledge
is spoken before it is written, so the demo starts by recording a voice and asking
a question about it three minutes later. Editing is allowed, so a slow transcription
is accelerated in the edit — not cut.

| Time | Action on screen | Narration |
|---|---|---|
| **0:00–0:08** | Landing page `/` | "Before this week, this organisation had no way to search its own recordings. In nine days I built one. Watch what happens to a voice in the next three minutes." |
| **0:08–0:48** | `/ajouter` → **record 12 seconds**, stop, upload | "I record a sentence nobody has written anywhere: *the Koudougou site starts on 12 January, budget four million.* The audio is kept as the original, its fingerprint computed, then transcribed and indexed." |
| **0:48–1:10** | `/` → ask **"When does the Koudougou site start?"** | "A fact that exists only as sound, thirty seconds ago. The answer comes back with the source: the recording. That is the whole point — a report nobody can hear does not exist." |
| **1:10–1:30** | Ask **"What does Meeting6 say in Mooré?"** | "Same engine, another language. The reports are written in French, the agents work in Mooré. Mooré is low-resource, so generic models fail here: the platform builds the resource and refuses to invent." |
| **1:30–1:58** | `/tache` → select two documents → instruction → **✦ Clean** → **DOCX** | "Now a task: plan, retrieve, scope, draft, verify, conformity. One click cleans the scaffolding, one exports the document with its citations." |
| **1:58–2:28** | `/documents` → filter → lock modal → `/administration` tabs + crown | "Nine documents, each with its workspaces; this private workspace returns zero. Administration re-files a document and changes who reads it, with its password. The crown protects an account that can never be deleted or demoted." |
| **2:28–2:40** | Sign in as `membre.demo` → `/documents` → `/journal` | "Same application, a member: zero documents, and the audit journal answers 403. The interface and the server agree." |
| **2:40–2:47** | Terminal beside the browser: the call log | "Muse Spark Open is Meta's open-weight model, on Baseten — for the answers, the agentic task, the translation, the vision and the audio. Every call is written down: endpoint, model, status, duration. Live, and kept in the repository." |
| **2:47–2:50** | Back to `/` | "124 tests, an audit trail, and knowledge that used to disappear now answers questions. Built solo, in nine days." |

### Before the take — the four things that make the opening work

| # | Preparation |
|---|---|
| 1 | **Microphone allowed** in Chrome for `127.0.0.1` (one dry run, then reload) |
| 2 | A **sentence you have never written anywhere**, with a date and a number: it is what proves the answer comes from the recording |
| 3 | **Rehearse the upload twice**: record → stop → send → wait for *indexed*. Note the real duration; if it exceeds 20 s, plan the time-lapse |
| 4 | **Fallback** if the microphone fails on camera: upload a pre-recorded 12-second WAV through the same form — the chain stays identical, only the recording is pre-made |

```powershell
# the log shot (2:40)
Get-Content "C:\wamp64\www\projet hackaton\ai-knowledge-workspace\storage\logs\model_calls.log" -Wait -Tail 5
```


> **Why this sentence matters.** *Use of Meta AI Tooling* weighs 25 %. One model
> used across five capabilities — answers, agentic task, translation, vision, audio —
> is a stronger claim than one model used once, and the organizers' own advice to a
> SAM 3D builder says the same thing: pair models rather than using a single one.
> The Baseten hackathon key only serves `meta-models/muse-spark-open-1026`, so the
> breadth of use is the honest way to score here.


> **What not to show.** The public landing page carries a roadmap section, labelled
> *“These features are not part of the submitted MVP”* with a *For the future* badge.
> It is honest, but a judge skimming a video can read it as a claim: **keep the
> landing page out of the recording** and start on the authenticated workspace, as
> the script does. The disabled audio-export buttons on the documents page are not
> unbuilt features either — they enable themselves as soon as a translation exists,
> and the handlers are wired.

**Two rules for the voice-over.** Do not read the table; speak these sentences at
a natural pace and let the screen do the work. And never apologise for an accent.

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
| **Automated tests** | **124 tests · 295 assertions** (in-memory SQLite, no server needed) |
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
- [ ] **Submission form**: `https://cerebralvalley.ai/e/meta-ai-developer-hackathon/hackathon/submit` — before Wed 4 Nov, 9:00 PM ET
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

Expected: migrations applied **and** `OK (124 tests, 295 assertions)`.

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

**Only two artefacts are judged**: the public repository and the 3-minute video.
The organizers confirmed on 2 November that **nothing else** is expected — no live
demo, no Q&A, no presentation deck.

| Date | What happens |
|---|---|
| **Wed 4 Nov, 9:00 PM ET** | submission closes |
| **Mon 9 Nov, 12:00 PM ET** | closing ceremony: the **top 10** (5 per track) are announced, with live final-round judging — `https://cerebralvalley.ai/e/meta-ai-developer-hackathon/live` |
| **Mon 16 Nov** | community vote result announced in Discord (one vote per verified account) |

So: nothing to rehearse after submission, and no environment to keep warm.

- [ ] Receive the **confirmation e-mail**
- [ ] **Do not modify** the repository during judging
- [ ] Keep the repository **public**
- [ ] Watch `#announcements` — official updates only come from there

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
