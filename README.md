# Agentic Knowledge Workspace™

> **Collaborative knowledge management, reinvented by AI.**
>
> For **public and private administrations, enterprises and development organizations**: centralize your documents, preserve your institutional memory and turn your knowledge into a **strategic asset**, queryable in natural language thanks to artificial intelligence.
>
> Built for the **Meta Global AI Developer Hackathon** — Theme: **Build for Your World** · Track: **Indie Developer** · Model: **Muse Spark Open** · License: **MIT**.

---

## The problem

In many organizations, critical information is scattered across **paper files, emails, file shares and personal computers**.

**Consequences:**

- Knowledge is lost when people leave.
- New members struggle to get up to speed.
- Considerable time is spent searching for information.
- Risks of duplication, errors and lack of traceability.

**The knowledge exists, but remains hard to exploit.**

---

## The solution

**Agentic Knowledge Workspace** is an AI-assisted knowledge management platform that lets organizations **centralize, secure and exploit** their documentary heritage.

Instead of manually searching hundreds of documents, users simply ask a question:

> *"Can you present the 2025 activity reports about capacity building for the project monitoring and evaluation team?"*

The AI assistant analyses the **authorized** documents, produces a **synthetic answer** and **cites its sources precisely**.

Then, to go further, the user requests an **agentic task**:

> *"Generate a synthesis with the dates, objectives, participants, results obtained and recommendations for those reports."*

The result is a **structured document**, exportable to **PDF or DOCX**, with its sources.

**Every document, every answer and every action remain traceable and verifiable.**

---

## More than a simple RAG application

The hackathon rules **explicitly exclude basic RAG applications**. Our approach goes beyond the *question → search → answer* pattern: the platform adopts an **agentic logic oriented towards business tasks**.

| Basic RAG | Agentic Knowledge Workspace |
|---|---|
| Answer to a question | Execution of complete document tasks |
| Text PDFs only | PDFs, scanned images and multimedia content |
| Single answer | Reports, briefs, summaries and structured notes |
| Weak traceability | Numbered citations bound to exact retrieved extracts |
| Top-K only context | Neighbour + first-chunk expansion, catalogue intent |
| Single-language output | Question-language answers, translated cited sources |
| No organizational control | RBAC, versions and audit |
| Generic chatbot | Business solution adapted to the African context |

---

## Key features — **MVP**

### Secure authentication
- Argon2 password hashing
- CSRF protection
- Secure session management
- Progressive lockout against attacks

### Governance and access control

- **Two-level RBAC.** Global roles (member, functional administrator, technical administrator) and per-workspace local roles (reader, contributor, animator, manager); resolution walks up the workspace hierarchy.
- **One document, several workspaces.** A `document_espace` link table carries the filing: access follows the **union** of the workspaces and the owner always keeps access (migration 025).
- **Workspace and confidentiality at deposit time.** The depositor chooses the workspace and the confidentiality; `public_interne` is reserved to administrators; the context survives the deferred-upload queue (migration 026).
- **Control roles changed by administrators only.** From the document list, an administrator changes the workspace and the confidentiality; their password is required and the **before/after** values are journaled.
- **Scoped document list.** The page and the API only return visible documents; an inaccessible document answers **404**, without revealing its existence.
- **Workspace badges and filter.** The list shows each document's workspaces and filters by workspace, including combined with the search.
- **Workspaces.** Creation, closure (no further deposits, documents stay readable), reopening; dated memberships with immediate revocation; the `/espaces` page lets a member choose the active workspace.
- **Protected accounts (Super Admin).** Several accounts may be protected: they can be neither deleted, nor suspended, nor demoted. Protection is granted and removed from the interface (password required), can be **switched off entirely**, and `php tools/seed_admin.php` restores the minimum.
- **Permission-driven menu.** A member only sees the entries they can use; `/journal` and `/administration` answer **403** without the permission, `/categories` requires being allowed to deposit or delete.
- **Tabbed administration.** Accounts, workspaces and memberships in three DataTables tabs, every action confirmed by the administrator password.
- **Nonce-based CSP.** `script-src` no longer accepts `'unsafe-inline'`: every inline script carries a per-request nonce.

### Advanced document management
- Storage outside the web root
- SHA-256 fingerprints
- Immutable versions
- Secure soft delete

### Contextual AI assistant
- Natural-language answers
- Grounded exclusively in authorized documents
- Automatically cited sources

### Smart search
- Hybrid vector and lexical search
- Understanding of user context
- Semantic search beyond keywords

### Agentic task loop — plan → retrieve → scope → draft → verify → conformity

You pick the **source documents** in the form; the loop then plans its own
search queries, retrieves only within that scope, drafts, verifies that every
claim is supported by its extract, and finally checks that the output actually
**answers the instruction** — a grounded draft can still be off-topic.

| Instruction | Output |
|---|---|
| "Write a report on…", "draft a note…" | a **document** with the structure of its genre (memo, report, minutes, letter, APC/PI lesson plan) |
| "Analyse the risks of…", "what are the obstacles?" | an **open answer**: a short synthesis, 3 to 6 titled and cited sections, one operational conclusion |

Naming a document in the instruction ("based on Meeting6") restricts the whole
task to it even without a selection. The six steps are exposed in the UI, so the
reasoning is auditable.

The task mode is not a single search followed by a generation. `RagService::task()` runs an agent loop, and every step is shown to the user ("Agent steps" panel in the result modal):

| Step | What the agent does |
|---|---|
| **1. Plan** | Muse Spark Open decomposes the instruction into 2–4 targeted search queries, knowing the **titles of the documents the user may access** (it translates queries into the documents' language, e.g. English instruction → French queries) |
| **2. Retrieve** | Each query runs through the permission-scoped retriever; results are merged and de-duplicated per chunk |
| **3. Draft** | The document is written from the merged, numbered extracts, with a structure adapted to the requested genre (note, report, minutes, letter, lesson…) |
| **4. Verify** | A fact-checking pass checks every sourced statement (figure, date, name, quotation) against the extract it cites |
| **5. Revise** | Unsupported statements are corrected or removed automatically (one pass); the trace reports how many were fixed |

Measured on the real knowledge base: English instruction → 4 planned queries (3 in French, one targeting the right document title) → 12 extracts from 2 documents → draft → **14 statements checked, 1 unsupported, corrected automatically** (~50 s end to end). Planner or verifier failures degrade gracefully to the single-search behaviour — the task never fails because of them. Covered by `AgentTaskTest` with a scripted model.

*Known limit:* the verifier checks that statements match the extracts; it cannot guarantee the quality of a translation from a low-resource language such as Mooré — translations of such passages should be reviewed by a speaker.

### Background ingestion — non-blocking uploads
Slow files need minutes of AI processing (ASR for audio, OCR for images and scanned PDFs). Instead of holding the HTTP request open, they are queued:

```
upload.php ──(text / text PDF)──► extract → store → index → 200        (synchronous, unchanged)
     │
     └─(audio / image / scanned PDF)──► ingestion_jobs row + detached worker → 202 {job_id}   (~1 s)
                                            │
                tools/ingest_worker.php ◄───┘  claim (atomic) → ASR/OCR → store → index → audit
                                            │
     upload_status.php?job_id ◄── UI polls every 3 s (elapsed time, then "Indexed: N chunks" or a localized error)
```

- **The app stays responsive**: the worker is a separate process, so the single-threaded `php -S` server keeps serving pages (measured 20–80 ms per page during a 105 s OCR job)
- **No half-documents**: the `documents` row is created only after extraction succeeds, so retrieval and listings never expose unprocessed content
- **Safe by design**: atomic `claim()` (a job is never processed twice), status readable only by the uploader, queued files deleted after processing, stalled jobs (> 30 min) marked failed, success and failure both journaled
- **Resumable UX**: pending jobs are kept in `sessionStorage`, so tracking resumes if the user leaves and comes back to the upload page
- Shared `IngestionService` for both paths (extraction, storage, indexing, localized error mapping) — covered by unit tests

### Scanned documents — OCR pipeline
- **Images** (png/jpg/tiff/webp) and **scanned PDFs** (no text layer) are read by Muse Spark Open vision
- A PDF whose text extraction is empty is detected automatically: its embedded JPEG page images are extracted and OCR'd, then concatenated with `[Page n / N]` markers — verified on a 4-page scanned hymn booklet in Mooré
- Pages are sent **in parallel** (`curl_multi`), so the total time is roughly that of the slowest page; a failed page is retried on its own without losing the others
- Large scans are downscaled to 1600 px (GD) before sending: text stays legible while avoiding provider timeouts; `META_VISION_TIMEOUT` (default 300 s) covers dense pages
- **Measured bottleneck:** on a dense page, 82 % of the generated tokens were the model's internal reasoning (2 466 of 2 996). OCR calls therefore use `reasoning_effort=low` (`META_VISION_REASONING_EFFORT`): −64 % completion tokens for an equivalent transcript, i.e. fewer credits

### Mooré — the language is not a detail, it is the condition of use

In Burkina Faso, institutional knowledge is usually **produced** in French — reports, minutes, procedures — but it is **used** by field agents, community facilitators and association members who work in Mooré. A report nobody can read is a report that does not exist. The barrier is therefore not technical: it is **access to knowledge**.

Mooré is a **low-resource** language: few written texts, almost no aligned corpus, and speech engines that distort it (whisper heard *"Ibe kibali kye mame"* for an ordinary sentence). That is exactly where generic models fail — and why this platform **builds the resource** instead of assuming it:

- a versioned Mooré lexicon and corpus (`moore_corpus`, `appellations_moore_*.pdf`);
- **human corrections** that become the reference and feed the learning loop;
- an **explicit refusal to invent** when the extract is missing — the limit is stated, not hidden.

**The spoken word is the source, not the draft.** Knowledge that is **said** — an elder's testimony, a village meeting, a ceremonial song — is not a degraded version of a text: it is often its **original form**. The audio recording is therefore kept **byte for byte** (SHA-256 fingerprint, immutable versions), and the transcript, the translation and the correction are only **successive readings** of that source. Every derivative cites the original; a correction keeps the corrected text **and** the source text side by side.

This is what gives the platform its **evidentiary** value: you can always go back to the voice, not only to what somebody wrote down about it. And it holds for **any language** — including those with no written corpus at all: the entry point is the voice, not the text.

**Field effect.** One correction made by one agent benefits every agent after them: a scarce resource becomes a **shared asset**. This is the opposite of a translation gadget — it is what makes an organisation's memory actually serve the people who keep it alive.


**Nothing is lost, and it is verifiable.** Every derivative — transcribed chunk, translation, correction — carries the document id **and** the id of the version it is a reading of; the version itself carries the SHA-256 fingerprint of the original file. The chain therefore runs both ways, and a command checks it instead of asserting it:

```powershell
php tools/prove_provenance.php 30
```

It recomputes the file fingerprint on disk, compares it to the stored one, and verifies that every translation and every correction points at the source version. Real output on the demo audio document:

```
v1 fingerprint identical      a9a48cd651b10113… 969,644 B
indexed transcript            1 chunk
translations en/fr/mo         linked to a version
correction #8                 linked to version 26 · source 373 chars. · corrected 234 chars.
Chain intact: every derivative traces back to the original recording.
```

**With no trade-off.** The same engine serves French, English and Mooré: adding a language is **data** work, not a rewrite.

- **End to end** — a Mooré meeting recorded in the browser is transcribed, auto-translated (both versions indexed), searchable by a Mooré question and answered **in Mooré with citations**; agentic tasks in Mooré produce Mooré documents — all verified live
- **Curated linguistic reference** (`services/Ai/MooreReference.php`) — a compact prompt-injected grammar/lexicon compiled from the SIL Mooré–French–English dictionary (U. Niggli, 10,650 entries), the Peace Corps Mooré manual and a field lexicon: official orthography (ɩ ʋ, nasal vowels ãẽĩõũ, doubled long vowels, -ã definite suffix, -me declarative marker), pronouns, SVO/negation rules, greetings and ~80 core terms
- **ASR phonetic reconstruction** — ASR engines garble low-resource languages (whisper heard *"Ibe kibali kye mame zakalamba"* for the actual *"Yibeoog kibare, f zak ramba"*). The translator is instructed to first reconstruct the intended Mooré phonetically, **then** translate: that garbled transcript now yields *« Comment s'est passée ta matinée ? Et les gens de la maison ? »* — the correct meaning (a greeting asking about one's morning and family)
- **Few-shot grounding** — up to 3 real Mooré extracts from the user's own indexed documents are injected as style/orthography references whenever Mooré is involved
- **Translation page** (`/traduire`) — FR ⇄ EN ⇄ MO both ways, one-click copy, every translation journaled
- **Honest scope** — Mooré output uses standard Mossi orthography and is reliable for everyday vocabulary; the UI states that official texts should still be reviewed by a speaker

### Audio transcription — dual pipeline
- **Meeting recorder** — the Add page records audio straight from the microphone (WAV in the browser), attaches it to the upload form and lets the background pipeline transcribe and index it as a regular document. A level meter and silence detection prevent indexing empty recordings
- **Transcript auto-translation** — `META_ASR_TRANSLATE_TO=French` appends a translated version to each transcript, so the original language and the translation are indexed side by side (Mooré transcript + French version)
- **Baseten — Muse Spark Open (default, first in line)** — the audio is sent to `meta-models/muse-spark-open-1026` through `/v1/chat/completions` as a standard `input_audio` part (raw base64 + format). Measured on a French meeting recording: **exact transcript (figures, proper names) in 8–21 s, 5/5 runs**, vs ~2 min for whisper.cpp on CPU. The earlier `audio_url` data-URL format took 56 s and returned empty messages
- **Optional Baseten Whisper Large V3 Turbo deployment** — dedicated GPU deployment called through the Baseten predict API (`whisper_input.audio.audio_b64` → `segments[].text`); verbatim, no LLM guardrails (sung lyrics included). Enabled via `META_BASETEN_WHISPER_URL` (+ `META_BASETEN_WHISPER_KEY`, `META_BASETEN_WHISPER_LANGUAGE`); billed per GPU minute to the owning organisation
- **Optional OpenAI-style ASR endpoint** — Whisper-style `/v1/audio/transcriptions` (multipart `file` + `model`), enabled via `META_TRANSCRIPTION_MODEL`/`META_TRANSCRIPTION_ENDPOINT`
- **Optional local whisper.cpp** — `whisper-cli.exe` + a GGML model, entirely offline and verbatim (sung lyrics included); used as fallback when `META_WHISPER_BIN`/`META_WHISPER_MODEL` are set
- Long audio is auto-split into provider-sized segments (`AudioSplitter`, frame-aligned MP3 / header-correct WAV), each segment transcribed and concatenated with `[Segment n/N]` markers
- **Resilient cascade:** Muse Spark Open → local whisper.cpp → Baseten Whisper deployment → OpenAI-style endpoint (`META_ASR_PRIORITY=local` reverses it). A backend that fails (refusal, missing binary, crash, empty output) is logged and the next one is tried. The model is non-deterministic (1 assistant-style reply out of 3 identical calls was measured), so such replies are retried up to `META_AUDIO_ATTEMPTS` (3) at temperature 0 — except copyright refusals, which are deliberate and handed straight to whisper.cpp (sung lyrics still get transcribed)
- *History:* local whisper.cpp was used during development while the free-tier Baseten credits were exhausted; with the hackathon credits ($100 on Muse Spark Open) transcription is back on Baseten, and the local backend stays available as an offline option
- *Guardrail safety:* the model may decline verbatim transcription of copyrighted sung lyrics. Refusals are detected and rejected with a clear, localized message — they are **never indexed** as document content (speech recordings are unaffected)

### Precision retrieval engine (RAG++)
- **Permission-aware retrieval** — every scored chunk belongs to a document the actor is allowed to read (tenant, workspace membership, confidentiality, ownership, soft-delete), resolved once per query
- **Hybrid scoring** — `0.6 × semantic cosine + 0.4 × lexical overlap` keeps recall strong even with local hashing embeddings
- **Word-boundary chunking** — fixed-size splits backtrack to the last space, so excerpts never end mid-word
- **Context expansion** — each hit is enriched with its ±1 neighbour chunks **and the document's first chunk**, where titles and headings live; a question about a title/heading is therefore always answerable
- **Catalogue intent detection** — "list all documents" bypasses chunk retrieval and feeds the real, permission-scoped document inventory (title · category · indexed date · file) to the model
- **Verifiable citations** — the model cites `[n]`; only the **cited** extracts are shown as sources, with their exact context number (`Sources: [3]` ⇢ entry *3*). No citation, no source list
- **Full multilingual pipeline** — the answer follows the **question's language**; literal quotes are translated with the original kept in parentheses; cited **source excerpts are translated** too via a dedicated low-temperature pass
- **Direct download** — every cited source carries a `document_id` download link to its latest version
- **Query-term highlighting** — words from the question are highlighted in the displayed source excerpts

### Human corrections — the learning loop

A model always lets some vocabulary or phrasing through. A native speaker — or
the document's own author — uploads a **corrected version** (PDF, DOCX or plain
text) tagged with its language. That correction immediately becomes the
**reliable reference** for that document:

- **Retrieval prefers it.** For any question or agentic task, the corrected text
  replaces the raw chunks of that document, and the context is labelled
  `VERSION CORRIGÉE <lang> <date>` so the model knows it is authoritative.
- **Sources cite it.** The answer lists the source with a *Corrected version*
  badge (language + date) and a link to download the exact file that was used.
- **The original file is preserved.** The uploaded DOCX or PDF is stored and
  served back **byte for byte** (SHA-256 identical) — never converted.
- **It teaches the translator.** Validated phrasings are injected as style
  examples into later translations (`Apprentissage`: IDF-weighted retrieval, one
  example per document, 2 600-character budget).
- **It is reversible.** Corrections are listed in the document modal and can be
  deleted by their author or a document manager; deletion stops feeding the
  prompt immediately.
- **It is honest about its scope.** This is retrieval-based learning, not
  fine-tuning: the model is guided by approved wording, never retrained.

### Content generation
- Automatic summaries
- Synthesis notes
- Structured reports
- Integrated citations and references

### Audit and compliance
- Complete action logging
- Systematic timestamping
- Immutable operation history

### Professional export

PDF (dompdf) and DOCX (native OOXML) with title, styles, pagination and
justified text.

A **✦ clean** toggle in the result modal turns a grounded answer into a
deliverable: it removes the `Sources : [n]` line, the inline citations, the
bracketed working markers, the method note and the documentary-reference block,
hides the source and agent panels, and updates the export payload — so the file
contains **the answer only**. A second click restores everything.

- PDF
- DOCX
- Simplified result sharing

---

## An innovation rooted in the field

The idea of **Agentic Knowledge Workspace** was born from the experience gained with **GES-SACREMENTS-KTO-BF**, a platform already deployed in several parishes of Burkina Faso for managing **training**, **monitoring/evaluating catechumens** and **administering the sacraments of the Christian faithful**.

Despite the digitalization of activities, documents, reports, **procedures** and institutional knowledge often remain **scattered across multiple media and systems**, making their access, use and valorization difficult.

The same observation applies to **administrations, NGOs and enterprises**, where institutional memory is fragmented across several systems and media.

**Agentic Knowledge Workspace** addresses this challenge by turning documents and organizational data into an **intelligent, centralized knowledge base** that can be queried in natural language.

---

## Use cases

| Audience | What it solves |
|---|---|
| **Public and private administrations** | Quickly find regulations, procedures, administrative archives and official reports, without depending on a key person. |
| **Enterprises** | Reduce document search time, capitalize know-how and improve knowledge sharing across teams. |
| **Development organizations** | Capitalize project knowledge, ease experience transfer and produce reliable, sourced reports. |
| **Faith-based organizations** | Preserve pastoral, administrative and training archives, as GES-SACREMENTS-KTO-BF already does in parishes. |

---

## Under the hood

A deliberately lean, robust and controlled architecture.

| Component | Version |
|---|---|
| **PHP** | **8.4.27** — strict MVC (PSR-4) |
| **MySQL** | **8.4.13** — InnoDB / UTF8MB4 |
| **AI model** | **Muse Spark Open** (Meta, served by a partner inference provider) |
| **Tests** | **121 automated tests · 281 assertions** |
| **UI** | Bilingual EN/FR (EN default) — Mooré supported for content (answers, tasks, translation), Bootstrap + DataTables + Select2 |
| **Security** | Argon2 · nonce-based CSP · CSRF · audit journal |
| **Scope** | **10 pages** · **31 endpoints** · **25 migrations** · **3 verification tools** |

---

## Roadmap — **after the hackathon**

> These features are **not part of the submitted MVP**: they extend the platform and build on its existing foundations.

**Advanced collaboration** — mentions · comments · notifications · contextual discussions.

**Organizational steering** — decision dashboards · performance indicators · project tracking.

**Advanced AI** — document recommendations · stale-knowledge detection · domain-specific assistants.

---

## Installation

**Prerequisites:** PHP 8.4 (extensions `pdo_mysql`, `curl`, `zip`, `mbstring`), MySQL 8 running, [Composer](https://getcomposer.org/).

**One-click (Windows/WAMP):** `setup.bat` runs every step below (it downloads `composer.phar` if Composer is not installed).

```powershell
# 1. Dependencies (composer.phar is not versioned: use a global Composer)
composer install

# 2. Local configuration — then set DB_* and META_MODEL_API_KEY
Copy-Item .env.example .env

# 3. Database — created automatically if missing, then all migrations applied
php tools/migrate.php migrate

# 4. First administrator + default space (idempotent)
php tools/seed_admin.php

# 5. Run locally
.\serve.bat
```

The administrator is created from `ADMIN_EMAIL` / `ADMIN_PASSWORD` (optional env variables, otherwise the defaults defined in `tools/seed_admin.php`) — **change the password after the first login**.

*Verified on a fresh `git clone` against an empty database: dependencies, migrations, seeding, the full test suite and login all pass.*

Then open <http://127.0.0.1:8080/presentation?lang=en> (public presentation) or <http://127.0.0.1:8080/login>.

In production the document root must point to `public/`.

---

## Web UI

The workspace is split into **seven navigable pages**, driven by the vertical menu — all bilingual EN/FR (EN default, per-session switch):

| Page | URL | What it does |
|---|---|---|
| **Question** | `/` | Ask a question, get a grounded answer with numbered cited sources (downloadable) |
| **Agentic task** | `/tache` | Ask for a note / report / brief; the draft opens in a modal |
| **Documents** | `/documents` | List of indexed documents (version history, summary, download, password-gated delete) |
| **Upload** | `/ajouter` | Index a text, PDF, image or audio file (category required) |
| **Categories** | `/categories` | Create and manage document categories |
| **Audit journal** | `/journal` | Traceability of every action (DataTables, CSV export, password-gated period purge ≥10 days) |
| **My profile** | `/mon-profil` | Identity, memberships; password-confirmed profile & password changes |

Every result opens in a **modal** with **PDF** and **DOCX** download buttons.

API endpoints (session-protected):

- `POST /api/upload.php` — index a file (multipart)
- `POST /api/ask.php` — `{"question": "..."}` → answer + sources; `{"instruction": "...", "mode": "task"}` → draft + sources
- `POST /api/summarize.php` — `{"document_id": 3}` → summary + sources
- `POST /api/export.php` — `{"title", "content", "format": "pdf|docx"}` → file
- `GET /api/documents.php` — recent documents (JSON)
- `GET /api/download_document.php` — download a document version (logged)
- `GET /api/journal_export.php` — audit journal as CSV
- `POST /api/journal_purge.php` — password-confirmed purge of journal entries older than 10 days (the purge itself is logged)

---

## Tests

```powershell
php vendor/phpunit/phpunit/phpunit                  # 121 tests, 281 assertions
php tools/smoke_admin_endpoints.php                 # 27 checks over 9 endpoints
php tools/smoke_endpoints_anonymous.php             # 31 endpoints x 2 methods, no session
php tools/lint_docs.php                             # documentation structure
  php tools/prove_provenance.php 30                   # provenance chain, bytes verified
```

**121 tests, 281 assertions** — covering authentication, RBAC, document versions, audit, permission-scoped RAG retrieval, exports, the multimodal services, the background ingestion pipeline, the agent loop, the rate limiter, the correction model and the learning engine (language isolation, IDF weighting, deletion taking effect). No database server or API required (SQLite in memory + deterministic fakes).

---

## Security

Prepared SQL statements · CSRF protection on every POST · password hashing (Argon2/bcrypt) · progressive brute-force lockout · RBAC resolved centrally (`PermissionService`) · deny-by-default · files stored outside the web root · no secrets in code or logs · audit journal (append-only; restricted legal purge requires password re-auth and is itself journaled) · security headers (CSP).

---

## Documentation

| Document | 🇬🇧 English | 🇫🇷 Français |
|---|---|---|
| **Build journal — what was really done, day by day** | [`JOURNAL_DE_CONSTRUCTION.md`](docs/JOURNAL_DE_CONSTRUCTION.md) | [`JOURNAL_DE_CONSTRUCTION.fr.md`](docs/JOURNAL_DE_CONSTRUCTION.fr.md) |
| **Technical reference** | [`ARCHITECTURE.md`](docs/ARCHITECTURE.md) | [`ARCHITECTURE.fr.md`](docs/ARCHITECTURE.fr.md) |
| **Delivery guide** | [`GUIDE_DE_REALISATION.md`](docs/GUIDE_DE_REALISATION.md) | [`GUIDE_DE_REALISATION.fr.md`](docs/GUIDE_DE_REALISATION.fr.md) |
| **Data security and privacy** | [`SECURITE_DONNEES_API.md`](docs/SECURITE_DONNEES_API.md) | [`SECURITE_DONNEES_API.fr.md`](docs/SECURITE_DONNEES_API.fr.md) |
| **Issue register (65 entries: 22 resolved, 12 open, 31 incidents)** | [`REGISTRE_PROBLEMES.md`](docs/REGISTRE_PROBLEMES.md) | [`REGISTRE_PROBLEMES.fr.md`](docs/REGISTRE_PROBLEMES.fr.md) |
| **Demo video & submission** | [`DEMO_VIDEO_ET_SOUMISSION.md`](docs/DEMO_VIDEO_ET_SOUMISSION.md) | [`DEMO_VIDEO_ET_SOUMISSION.fr.md`](docs/DEMO_VIDEO_ET_SOUMISSION.fr.md) |
| **Demo state — accounts, workspaces, documents, scenarios** | [`ETAT_DE_DEMO.md`](docs/ETAT_DE_DEMO.md) | [`ETAT_DE_DEMO.fr.md`](docs/ETAT_DE_DEMO.fr.md) |
| **Acceptance test — real execution on a fresh database** | [`RECETTE_AZ.fr.md`](docs/RECETTE_AZ.fr.md) | [`RECETTE_AZ.md`](docs/RECETTE_AZ.md) |
| **Recommendations register — what is done, what remains** | [`RECOMMANDATIONS.fr.md`](docs/RECOMMANDATIONS.fr.md) | [`RECOMMANDATIONS.md`](docs/RECOMMANDATIONS.md) |
| **Trademark and intellectual property** | [`MARQUE_ET_PROPRIETE.md`](docs/MARQUE_ET_PROPRIETE.md) | [`MARQUE_ET_PROPRIETE.fr.md`](docs/MARQUE_ET_PROPRIETE.fr.md) |


Every document exists in **both languages**, with the English as the base name and the French as `.fr.md`.

## Which calls are Meta model calls

The organizers ask every project to say which parts are **Meta model calls** and
which are **OS features** or local processing. This is the complete list; nothing
here is inferred.

| Feature | What runs at runtime | Meta model call? |
|---|---|---|
| Answering a question | `meta-models/muse-spark-open-1026`, served by Baseten | **yes** |
| Agentic task — plan, draft, verify, conformity | same model, several calls | **yes** |
| Translation FR / EN / MO | same model | **yes** |
| Image and scan understanding (OCR fallback) | same model, vision input | **yes** |
| Speech-to-text — **default path** | same model, audio input | **yes** |
| Speech-to-text — **fallback only** | local `whisper.cpp` binary | **no** — a program on the same machine, used only when the model is unreachable |
| Retrieval scoring (cosine + lexical) | arithmetic in PHP over stored vectors | **no** |
| Audio recording, file parsing, PDF text extraction | browser and PHP | **no** — OS and library features |
| Text-to-speech | not used | — |

Two consequences, stated plainly:

- **No Meta logo, no mascot, no brand asset** appears in the application, the
  landing page, this repository or the demo video. Models are named in plain text,
  which the organizers allow.
- **No text-to-speech**: the project never speaks, so the rule on voice output does
  not apply to it.

### Open Meta models used, and where they run

The organizers also ask which **open Meta models** a project uses and **where they
run** — a self-hosted Meta model counts, wherever it runs.

| Model | Nature | Where it runs |
|---|---|---|
| `meta-models/muse-spark-open-1026` (Muse Spark Open) | Meta open weights | **Baseten**, a partner endpoint, over the OpenAI-compatible API |
| — | — | **no Meta model runs on the author's machine** |

The local `whisper.cpp` binary is **not** a Meta model. It is a fallback that only
runs when the model is unreachable, and it is declared as such in the table above.
The retrieval vectors are computed by hashing in PHP, which is arithmetic, not a
model.

This setup is explicitly eligible: the organizers confirmed on 2 November that a
project built on Meta's open-weight models **on compute you provide** is eligible,
and that **not using the Model API does not count against it** (Hackathon Credit
Terms, section 3.8). No Meta credits were needed, and none were claimed.

---

## Third-party assets and rights

The hackathon rules require the rights to **every asset** used by a project and
by its demo video. This section states, asset by asset, on what basis each one is
redistributed.

| Asset | Location | Rights |
|---|---|---|
| **Bootstrap 5.3.3** | `public/assets/vendor/bootstrap` | MIT — the notice is kept inside the distributed files |
| **jQuery 3.7.1** | `public/assets/vendor/jquery` | MIT — the notice is kept inside the distributed files |
| **DataTables** (Bootstrap 5 integration) | `public/assets/vendor/datatables` | MIT — the notice is kept inside the distributed files |
| **Select2 4.1.0** | `public/assets/vendor/select2` | MIT — the notice is kept inside the distributed files |
| **PHP dependencies** — dompdf, PHPMailer, pdfparser | `vendor/` (installed by Composer, not committed) | each package ships its own licence |
| **Mooré reference material** — the *Dictionnaire mooré–français–anglais* edited by Urs Niggli, and its French/English word indexes | not distributed: downloaded locally into `storage/moore_sources/`, excluded from this repository | **© 2016 SIL International®**, published on [Webonary](https://www.webonary.org/moore/) and mirrored by [Mooré Burkina Faso](https://mooreburkina.com/fr/livres-en-moor%C3%A9/t%C3%A9l%C3%A9charger-pdf). Cited as: *Niggli, Urs (éd.). 2016. Dictionnaire mooré – français. SIL International.* Used through the import tools, **not redistributed** |
| **Application source** | the rest of the repository | MIT, see [LICENSE](LICENSE) |

**Mooré lexicon.** The import tools (`tools/import_moore_lexicon*.php`) can read the
SIL dictionary placed locally in `storage/moore_sources/`. The resulting word lists are
data derived from a cited work; the original PDFs stay on the operator's machine and
are excluded from this repository by `.gitignore`.

**Demo video.** No music, no stock footage, no third-party screenshot or logo.
Everything shown on screen is produced by this application, from documents the
demo accounts are allowed to read. If a licensed track is ever added, its source
and licence will be listed in this table.

---

## Author

**Anatole KABORE**

**Information systems consultant · Full Stack developer · Cybersecurity specialist**

Based in Ouagadougou (Burkina Faso), I am an information technology professional specialized in the design, architecture and development of secure digital solutions. I support administrations, enterprises, NGOs and faith-based organizations in the digital transformation of their business processes through robust, scalable and user-centred web platforms.

My expertise covers Full Stack development, software architecture, cybersecurity, data management and the integration of artificial intelligence solutions applied to concrete problems. I pay particular attention to security, data governance, action traceability and the sustainability of organizational knowledge.

Through my work, I strive to design tools that address the real challenges encountered in the field, including information management, collaboration, monitoring and evaluation, and the valorization of the documentary heritage of African organizations.

- LinkedIn: <https://www.linkedin.com/in/anatole-kabore-73b076342>
- GitHub: <https://github.com/AnKBORE>

---

## Meta Global AI Developer Hackathon

- **Theme:** Build for Your World
- **Track:** Indie Developer
- **AI model:** Muse Spark Open
- **License:** MIT

---

## License

**MIT** — see [LICENSE](LICENSE).
