# Build Journal — Agentic Knowledge Workspace™

> **Traceability document for the work actually performed**, from 26 October 2026
> (hackathon opening) to submission.
>
> This is not a pitch: it is **evidence**. Every line points to real commits that
> anyone can verify with `git log`.

🇫🇷 French version: [JOURNAL_DE_CONSTRUCTION.fr.md](JOURNAL_DE_CONSTRUCTION.fr.md)

---

## 0. How this document is kept

| Rule | Application |
|---|---|
| **One entry per working day** | appended at the end of the day, never rewritten afterwards |
| **Numbers, not adjectives** | commits, lines, tests, files |
| **Mistakes are written down** | a resolved incident is worth more than a hidden one |
| **Verifiable** | every claim points to a hash, a file or a command |

**To extend this journal:** add a dated section to the chronology (§3), then a
row to the tracking table (§8). Never modify a past entry — corrections go into a
new entry.

---

## 1. State as of 2 November 2026

### Volume produced

| Metric | Value | Verification |
|---|---|---|
| **Commits** | **264** (**254** since the opening) | `git rev-list --count HEAD` |
| **Lines added** | **+56,241** (+52651 since 26/10) | `git log --shortstat` |
| **Lines deleted** | **−7,624** | idem |
| **Period covered** | 24 Oct → 2 Nov (9 working days) | `git log --date=short` |

### The product

| Area | Volume |
|---|---|
| Web pages | **10** routes |
| API endpoints | **31** |
| SQL migrations | **25** |
| Business services | **22** (10 in `Ai/`) |
| Models | **10** |
| Views | **19** |
| Test files | **25** |
| **Automated tests** | **124 tests · 295 assertions** |
| Documentation | **7 EN/FR pairs + README · ≈ 300 KB** |

### The stack

```
PHP 8.4.27 · MySQL 8.4.13 · strict MVC · PSR-4 · no framework
Hybrid RAG (0.6 vector + 0.4 lexical) · local embeddings
Model: Muse Spark Open (Baseten) · ASR: Baseten cascade + local whisper.cpp
PDF: smalot/pdfparser · DOCX: ZipArchive (native OOXML) · PDF: dompdf
```

---

## 2. The nine days in one table

| Date | Commits | Lines | What was built |
|---|---|---|---|
| **24/10** | 10 | +3,590 / −33 | *Before the opening* — foundation: MVC, AI layer, RAG demo, PDF extraction |
| **26/10** | 53 | +15,828 / −1,184 | Repositioned as an "agentic multimodal workspace", web UI, first multimodal services |
| **27/10** | 51 | +4,079 / −1,984 | Governance: audit journal, CSP, pagination, versions, CSV export |
| **28/10** | 3 | +3,187 / −519 | RAG++ engine and bilingual EN/FR interface |
| **29/10** | 8 | +1,677 / −299 | Hackathon model wired in, robust ASR, OCR for scanned PDFs |
| **30/10** | 6 | +2,137 / −61 | Mooré: translation page, linguistic reference, data bank |
| **31/10** | 71 | +15,676 / −1,326 | Security, lexicon, human corrections, complete agentic loop |
| **01/11** | 18 | +1,706 / −739 | Clean deliverable, localised dates, documentation, UI polish |
| **02/11** | 22 | +3,712 / −416 | Full audit, N-N document↔workspaces, protected accounts, nonce CSP, bilingual documentation |

---

## 3. Detailed chronology

### 24 October — the foundation (before the opening)

**10 commits · +3,590 / −33**

The hackathon is not open yet. Work starts with foundations: `Initial commit`
(PHP 8.4 / MySQL 8.4 scaffold, MVC structure), `README.fr.md`, pinned versions
(**PHP 8.4.27**, **MySQL 8.4.13**), then the AI layer: `MetaClient`, embedding
service, RAG service, assistant controller.

The day ends with an **end-to-end RAG demo**: `.env` loader, `bootstrap.php`,
`ask.php` endpoint, indexing CLI, migration runner, PDF extraction.

> **Why this is recorded here**: these 10 commits predate the opening. They are
> listed for the honesty of the count, not to inflate the total.

---

### 26 October — repositioning (opening day)

**53 commits · +15,828 / −1,184**

The initial positioning ("RAG over documents") is **abandoned on day one**: the
rules explicitly exclude basic RAG applications.

Decision: become an **agentic multimodal workspace**.

| Representative commit | What it changes |
|---|---|
| `Reposition as agentic multimodal workspace` | the product changes nature |
| `Add Muse Voice Transcribe + SAM 3.1 services` | first audio and vision bricks |
| `Add web UI reusing the foundation design system` | working upload + chat |
| `Add serve.bat` | reproducible dev server |
| `Align MetaClient with live /v1/responses API` | the client speaks the real API |
| `Add detailed phased execution guide` | the roadmap is written before the code |

**Volume**: 15,828 lines in a single day — the heaviest day of the project,
before any work on language.

---

### 27 October — governance

**51 commits · +4,079 / −1,984**

An application that answers questions about an organisation's documents must
**prove** what it does. This day builds the trust layer.

| Representative commit | What it changes |
|---|---|
| `Audit journal page (/journal), CSP and security headers` | the audit trail becomes visible |
| `Documents pagination (20 per page) with total count` | the base fits in the UI |
| `Performance indexes migration + Document model tests` | **the first business tests** |
| `Document version history` (date, size, SHA-256) | every upload is immutable and traceable |
| `Journal CSV export (journal.lire permission)` | the audit becomes exportable |

The negative line balance (−1,984) signals a **first cleanup**, not an addition.

---

### 28 October — precision

**3 commits · +3,187 / −519**

Few commits, large volume: the whole day goes into one subject — the
**retrieval engine**.

| Commit | What it brings |
|---|---|
| `Precision RAG engine + bilingual EN/FR UI (68 tests)` | hybrid score, bilingual UI, **68 tests** |
| `RAG++ hardening: vector cache, not-found retry, title boost, whisper.cpp` | retrieval becomes robust |
| `Stats widget, query-term highlighting` | the answer becomes readable |

This is the shift from "it answers" to "it answers **and you can check**".

---

### 29 October — the real model and ASR

**8 commits · +1,677 / −299**

| Commit | What it brings |
|---|---|
| `Connect Baseten hackathon model muse-spark-open-1026` | the hackathon model is actually used |
| `Audio transcription back on Baseten Muse Spark Open; reject guardrail refusals` | audio enters the scope |
| `Local whisper.cpp re-enabled with auto language, resilient ASR fallback cascade` | **two** ASR engines, with fallback |
| `OCR for scanned PDFs: page image extraction, downscaling, parallel vision calls` | scans become readable |
| `Fresh-clone install fixes: auto-create database, portable composer` | the repo becomes installable by a third party |

That last point matters: **a project the jury cannot install does not exist.**

---

### 30 October — Mooré

**6 commits · +2,137 / −61**

The most decisive day for the differentiator.

| Commit | What it brings |
|---|---|
| `Mooré translation page with SIL/Peace Corps linguistic reference` | a dedicated translation page with a **sourced** linguistic base |
| `Live karaoke audio translation in documents modal` | audio becomes explorable segment by segment |
| `Mooré data bank: corpus+lexicon tables, corpus-aware prompts` | the language becomes **data**, not a prompt |
| `LAN-accessible server (0.0.0.0)` | testable from a phone |

**The structuring choice**: stop hoping a general model knows Mooré, and **build a
data bank** to inject instead.

---

### 31 October — security, then learning

**71 commits · +15,676 / −1,326**

The longest day: **two workstreams in parallel**.

**Morning — hardening**

| Commit | What it brings |
|---|---|
| `Security, tests and Mooré enrichment: CSRF on state-changing endpoints, MIT license` | CSRF everywhere, licence |
| `Rate limit: fixed-window limiter (table, service, 3 tests)` | abuse protection |
| `Mooré lexicon: merge the recent full SIL dictionary (+734 entries)` | **1,349** verified entries |
| `Mooré proverbs: ship 40 proverbs (KABORÉ/MooreBurkina)` | cultural reference |
| `Landing: transparency note on the low-resource limit` | **the limit is written inside the product** |

**Afternoon — the human correction module**

The observation: a model always lets errors through. Rather than hoping for a
perfect model, **capture the human correction**.

| Feature delivered | Effect |
|---|---|
| Upload a corrected version (PDF, DOCX, text) | a native speaker improves the system |
| Priority in retrieval | the correction replaces the raw text |
| Badge + download of the original file | the source is verifiable |
| Learning engine (`Apprentissage`) | IDF weighting, one example per document |
| Listing and deletion | the correction is reversible |

**Evening — the complete agentic loop**

| Step added | The question it answers |
|---|---|
| `scope` | "on **which** documents?" |
| `conformity` | "does the output **answer** the request?" |
| Open-answer mode | "an analysis is not a report" |

---

### 1 November — the deliverable

**18 commits · +1,706 / −739**

The product could answer; it could not yet **deliver**.

| Commit | What it brings |
|---|---|
| `Result modal gains a clean toggle` | an answer becomes a readable text |
| `The clean toggle strips the TEXT, not the DOM` | a real defect fixed (§5) |
| `Language-aware wording and dates` | "31 October 2026", never `2026-10-31` |
| `Documentation update across the board` | 8 documents aligned |
| `Task page: source-document picker` (5 commits) | explicit scope selection |
| `Translate page no longer exhausts its quota` | live translation respects the quota |

---

### 2 November 2026 — audits, workspace scoping, hardening

**Starting point.** The MVP worked, but a systematic audit (syntax, pages, data, headers, endpoints, documentation) revealed gaps between what the interface showed and what the server allowed.

**Access hole closed.** The document list and the download-by-id endpoint only checked the **global** `document.consulter` permission. A member could therefore list and download documents from another workspace. The visibility rule was extracted into `PermissionService::documentsVisibles()`, shared by the list, the search, the download and the retrieval engine. Verified with a real second account: 0 documents listed, download answered 404.

**Audit journal closed.** The `/journal` page was readable by any authenticated member. It now requires `journal.lire`: 403 and a journaled refusal.

**Governance made visible.** `/administration` page (accounts, workspaces, memberships), workspace lifecycle (close / reopen), dated memberships with immediate revocation, `/espaces` page to choose the active workspace, permission-filtered menu.

**Document ↔ workspaces as N-N.** `documents.espace_id` could only carry one workspace. Migrations 025 (`document_espace`) and 026 (`ingestion_jobs.espace_ids`): a document is filed in several workspaces, access follows the **union**, the owner always keeps access. Verified: deposit into two workspaces, detach one, a member of the other sees the document and then no longer does.

**Protected accounts.** The model moved from a single seat to a **set**: `accorderSuperAdmin()` / `retirerSuperAdmin()`, protection can be switched off, journaling (`compte.super_ajout`, `.retrait`, `.desactivation`), crown button in the interface, `--grant` / `--revoke` on the command line.

**Interface.** Administration inventory in three DataTables **tabs** (column widths recomputed on display), workspace badges and workspace filter in the document list, control modal (workspace + confidentiality) protected by password.

**Hardening.** **Nonce-based** CSP (no more `'unsafe-inline'` for scripts), content negotiation on the endpoints (401 JSON instead of a redirect), `/categories` guard, **delegated** binding of the delete button, independent of the initialisation order.

**Verification on 2 November.**

| Check | Result |
|---|---|
| `git rev-list --count HEAD` | **262** |
| `phpunit` | **124 tests · 295 assertions** |
| `tools/smoke_admin_endpoints.php` | **27 checks** (9 endpoints × 3 profiles) |
| `tools/smoke_endpoints_anonymous.php` | **31 endpoints × 2 methods**: everything refused, no data |
| `tools/lint_docs.php` | no defect |
| Demo database | 3 accounts (2 protected) · 3 workspaces · 6 memberships · 9 documents · 25 migrations |

**What this audit changed in the method.** Three of the defects found came from my own patch scripts (insertion inside a table cell, a comment containing literal `\n`, a substitution reported but not applied). Every correction is now followed by a **rendering check**: `<th>`/`<td>` counts, nonce per page, real HTTP status.

---
## 4. What was produced, by domain

### Ingestion

PDF, DOCX, CSV, Markdown, images, **scanned PDFs (page-by-page OCR)**, **audio
(ASR)**. Every upload creates an **immutable version** with a SHA-256 fingerprint.

### Retrieval

Hybrid score `0.6 × cosine + 0.4 × lexical`, neighbour expansion, "catalogue"
intent detection, widened search on failure, **priority to corrections**.

### Agentic

`plan → retrieve → scope → draft → verify → conformity`, with the trace exposed
in the UI. Output shape adapts to the instruction (document or open answer).

### Language

6 directions FR ↔ EN ↔ MO. Four injected layers: linguistic reference, business
glossary, SIL lexicon (1,349 entries), proverbs. **No fine-tuning**: learning by
retrieving validated examples.

### Audio

Built-in recorder (browser WAV, level meter, silence guard), transcription in one
call at stop, translation, DOCX/PDF export, rephrasing **from the audio**.

### Governance

**Append-only** journal (21 action types, refusals included), two-level RBAC,
CSRF, rate limiting, CSP, storage outside the web root, legal purge under strict
conditions.

### Deliverable

✦ toggle: removes citations, sources and working annotations, then exports
DOCX/PDF containing **the text alone**.

---

## 4bis. Detailed inventory — what was created, and when

This section does not quote intentions: it lists **the files that exist in the
repository today**, with the date of their first commit. Verifiable with
`git log --diff-filter=A --format=%ad -- <path>`.

### 24 October — technical foundation (9 files)

| Type | Files |
|---|---|
| API | `ask.php` |
| SQL | `001_ai_rag` |
| Services | `MetaClient`, `EmbeddingService`, `RagService` |
| Tests | `EmbeddingServiceTest`, `EnvTest`, `RagServiceTest`, `RagServiceIntegrationTest` |

### 26 October — opening: governance and multimodality (43 files)

| Type | Files created |
|---|---|
| **API (5)** | `upload`, `documents`, `delete_document`, `export`, `summarize` |
| **SQL (11)** | `utilisateur`, `jeton_compte`, `tentative_connexion`, `securite_compte`, `espace`, `adhesion`, `historique_adhesions`, `journal`, `document`, `version_document`, `document_chunks_version` |
| **Models (7)** | `Utilisateur`, `JetonCompte`, `TentativeConnexion`, `Espace`, `Adhesion`, `Document`, `VersionDocument` |
| **Services (8)** | `AuthentificationService`, `PermissionService`, `DocumentService`, `JournalService`, `ExportService`, `TranscriptionService`, `VisionService`, `AudioSplitter` |
| **Tests (11)** | `AuthentificationServiceTest`, `PermissionServiceTest`, `PolitiqueMotDePasseTest`, `DocumentServiceTest`, `ExportServiceTest`, `JournalServiceTest`, `MultiModalServicesTest`, `AudioSplitterTest`, `FakeMetaClient`… |

The densest day in terms of **structure**: the complete data model (users, spaces,
memberships, documents, versions, journal) laid down in a single day, with its
tests.

### 27 October — categories, exports, anonymisation (10 files)

| Type | Files |
|---|---|
| API | `categorie_save`, `categorie_delete`, `download_document`, `journal_export` |
| SQL | `014_indexes`, `015_categories` |
| Model | `Categorie` |
| Service | `Anonymiseur` |
| Tests | `AnonymiseurTest`, `DocumentTest` |

### 28 October — retrieval precision (2 files)

| Type | Files |
|---|---|
| API | `journal_purge` |
| Tests | `RagServiceScopeTest` |

Few new files: the day is spent **rewriting** the retrieval engine, not adding
surface. Hence 3,187 added lines for only 3 commits.

### 29 October — background ingestion and ASR (7 files)

| Type | Files |
|---|---|
| API | `upload_status` |
| SQL | `016_ingestion_jobs` |
| Model | `IngestionJob` |
| Services | `IngestionService`, `BackgroundWorker` |
| Tests | `AgentTaskTest`, `IngestionServiceTest` |

### 30 October — Mooré enters the product (6 files)

| Type | Files |
|---|---|
| API | `translate`, `translate_document` |
| SQL | `017_document_translations`, `018_moore_corpus` |
| Services | `DocumentTranslationService`, `MooreReference` |

### 31 October — security, corrections, learning (24 files)

| Type | Files |
|---|---|
| **API (6)** | `correction_save`, `correction_list`, `correction_delete`, `correction_download`, `reformulate`, `transcribe_chunk` |
| **SQL (4)** | `016_rate_limit`, `017_corrections`, `018_correction_filename`, `019_correction_file` |
| **Model (1)** | `Correction` |
| **Services (6)** | `Apprentissage`, `LexiqueMoore`, `Glossaire`, `Proverbes`, `RateLimiter`, `TexteExtracteur` |
| **Tests (5)** | `ApprentissageTest`, `MooreResourcesTest`, `RateLimiterTest`, `CategorieTest`, `AdhesionTest` |

### 1 November — no new surface (0 files)

Eighteen commits, zero new files: the day goes into **finishing** — clean
deliverable, localised dates, documentation, the source-document picker, the
translation quota. A project that stops adding files while still growing is a
project that is **fixing**, not sprawling.

### Recap by category

| Category | Total | Per day |
|---|---|---|
| **API endpoints** | 20 | 1 · 5 · 4 · 1 · 1 · 2 · 6 |
| **SQL migrations** | 21 | 1 · 11 · 2 · — · 1 · 2 · 4 |
| **Models** | 10 | — · 7 · 1 · — · 1 · — · 1 |
| **Services** | 22 | 3 · 8 · 1 · — · 2 · 2 · 6 |
| **Test files** | 23 | 4 · 11 · 2 · 1 · 2 · — · 5 |

> **Honest note on migrations**: three numbers are used twice (`016`, `017`,
> `018`), because two separate workstreams ran the same day. The runner applies
> files alphabetically: all 21 migrations pass, but the numbering is not clean.

### What the inventory does not say

A file created on 31 October was not born in one go: `RagService.php` was
modified **30+ times** between 24 October and 1 November. The creation date marks
the appearance of a surface, not its final state — `RagService` 31 commits,
`workspace.js` 49, `app.css` 46.

---

### Additions of 2 November

| File | Role |
|---|---|
| `public/api/admin_user_save.php` | account creation (`compte.inviter`) |
| `admin_user_statut.php`, `admin_user_delete.php`, `admin_user_role.php` | suspension, soft deletion, role change |
| `admin_user_super.php` | grant / revoke the Super Admin protection |
| `admin_espace_save.php`, `admin_espace_statut.php` | workspace creation, closure and reopening |
| `admin_adhesion_save.php`, `admin_adhesion_end.php` | membership, immediate revocation |
| `document_controle.php` | workspace and confidentiality of an existing document |
| `espace_actif.php` | choice of the active workspace |
| `views/ai/administration.php`, `views/ai/espaces.php` | tabbed administration, workspace selection |
| `migrations/023` → `026` | super admin, ingestion context, document‑workspace link |
| `tools/smoke_admin_endpoints.php`, `smoke_endpoints_anonymous.php`, `lint_docs.php` | executable checks |
| `tests/PermissionServiceDocumentsVisiblesTest.php`, `UtilisateurSuperTest.php` | union visibility, protected accounts |
## 4ter. MVP audit — 1 November 2026

**Method**: every route and endpoint was called for real with an authenticated
session, then a static matrix (HTTP method, CSRF, rate limit) was built for the
20 endpoints. Findings, not impressions.

### What passed

| Check | Result |
|---|---|
| **9 pages** (`/`, `/tache`, `/traduire`, `/ajouter`, `/documents`, `/journal`, `/mon-profil`, `/categories`, `/presentation`) | **200** |
| **6 read endpoints** (documents, correction list/download, upload status, download, journal CSV) | **200** |
| **Write endpoints** (ask, task, translate, reformulate, export, summarize) | **200** with real content |
| **Refusals** (bad password, oversized purge window, missing document_id) | **403 / 422**, never a crash |
| **CSRF** on every state-changing endpoint | present |
| **Journaling** of writes and refusals | present |
| **TODO / FIXME left in the code** | **none** |

### What was broken, and fixed the same day

| # | Finding | Risk | Fix |
|---|---|---|---|
| 1 | `categorie_save` / `categorie_delete` rejected a GET only through CSRF | fragile: the guard depended on another mechanism | explicit **405 POST-only** |
| 2 | `delete_document` required the password but had **no rate limit** | password brute force with a stolen session | 10 / 5 min |
| 3 | `export` had no rate limit | PDF generation is CPU-heavy | 30 / 5 min |
| 4 | `transcribe_chunk` had no rate limit | ASR cost abuse | 60 / 5 min |
| 5 | `translate_document` had no rate limit | translation cost abuse | 300 / 5 min |
| 6 | `upload` had no rate limit | disk exhaustion | 20 / 5 min |

Verified after the fix: `GET /api/categorie_save.php` → **405**; write endpoints
still **200**; the 124 tests stay green.

### Known limitations, measured not guessed

| Limitation | Reality |
|---|---|
| **Endpoint test coverage** | **14 of 20** endpoints are never mentioned in a test file. The 124 tests cover services and business rules, not the HTTP layer |
| **Background ingestion** | `forking is not supported on this platform` under Windows: a heavy upload is processed synchronously |
| **No quality metric** | no precision@k, no translation score — nothing measures answer quality on a benchmark |
| **JavaScript** | zero automated test; the clean toggle, the picker and the recorder were verified manually |
---

## 5. The mistakes, written down

A build journal without mistakes is a false journal. The most significant:

| Incident | Real cause | Fix |
|---|---|---|
| Corrections ignored in answers | `Illegal mix of collations` swallowed by a `catch` | language preference decided in PHP |
| Truncated answers, no sources | `max_tokens` ceiling at 1,200 | 3,000 + fallback to the first three extracts |
| Speech citing another project | planner searching the **whole** corpus | `documentScope()` + explicit selection |
| The correction **replaced** the document identity | destructive substitution | identity + original + corrected = **three** extracts |
| Cleaning left headings behind | processed the **HTML** instead of the **text** | cleaning runs on `currentResult.content` |
| Scrollbar inside the picker | `max-height` + `overflow-y: auto` | free height, tags wrap onto several lines |
| "Too many requests" on translation | translation on every keystroke, mismatched quota | 1.2 s of silence + unchanged text skipped + pause after 429 |
| Fix invisible after deployment | tab running the previous JavaScript | `no-store` pages, `Ctrl+F5` documented |

| Delete button inert on the documents page | binding done at the **end** of the script | delegation on `document` + early idempotent bind |
| Accounts table misaligned (DataTables error) | block inserted inside the **name** cell, swallowing e-mail/role/status | row rebuilt, 6 columns for 6 headers |
| Uploads failed with a `TypeError` | literal `\n` inside a comment commented out the `$result` assignment | comment restored, scan for literal `\n` |
| `/categories` readable by a reader | no guard on the page | 403 + message, journaled refusal |
| Three endpoints answering 302 | `$respond` only reading `X-Requested-With` | content negotiation, 401 JSON |
| Inline script blocked by the CSP | nonce missing on the journal script (substitution not applied) | nonce added, verified page by page |Full detail: `docs/REGISTRE_PROBLEMES.md` (65 entries: 22 resolved, 12 open, 31 phase incidents).

---

## 6. Proof the jury can verify

```powershell
# Volume and rhythm
git rev-list --count HEAD
git log --date=short --pretty=format:'%ad' | Group-Object | Sort-Object Name

# One specific day
git log --since="2026-10-31 00:00" --until="2026-11-01 00:00" --oneline

# The detail of a decision
git show <hash>

# State of the test suite
C:\wamp64\bin\php\php8.4.27\php.exe -d memory_limit=1G vendor\phpunit\phpunit\phpunit --configuration phpunit.xml --no-coverage
```

---

## 7. What this journal does not claim

| Claim | Reality |
|---|---|
| "8 days" | 8 **calendar** days, with days ranging from 3 to 71 commits; 28 and 30 October have few commits but large volumes |
| "124 tests" | they cover business logic, **not** the JavaScript nor the correction endpoints |
| "Mooré translation" | lexicon limited to 1,349 entries; complex constructions remain approximate |
| "Semantic RAG" | **hashing** embeddings, less semantic than a real model |
| "Production ready" | hackathon project: CSP to harden, partial pagination, no quality measurement |

---

## 8. Update log for this document

| Date | Event | Commit |
|---|---|---|
| 01/11 | Journal created (retrospective 24/10 → 01/11) | `8c453f6` |
| 01/11 | Detailed per-file inventory added | `38dd424` |
| 01/11 | English version produced, French renamed to `.fr.md` | `92407bd` |
| 01/11 | Issue register translated (40 incidents) | `e5629d0` |
| 01/11 | Data security note translated (18 sections) | `f554194` |
| 01/11 | Delivery guide translated (13 sections) | `ca57984` |
| 01/11 | Documentation link audit: FR docs point to FR counterparts | `dcb6d1f` |
| | | |
| | | |

---

## 9. The acceptance run of 2 November, in the evening

The database was wiped in the morning; the run rebuilt the state by hand and
found **seventeen defects** that no unit test had caught, because they only
appear when a person does the gesture.

What it cost, and what it bought:

| Finding | What it proved |
|---|---|
| P-54 — the OCR kept only JPEG streams | a mooré scan was refused all day; once raw bitmaps were wrapped into a PNG, the same file yielded **1 093 characters** in 8.6 s |
| P-58 and P-65 — the workspace did not scope the search, then the catalogue | the badge said one thing, the answer another |
| P-59 — a confident answer about the **wrong** site | a tool claiming grounding must refuse, not improvise; the threshold was measured at 0.25 |
| P-66 — three routes took a document id after checking only the global right | a member could read a document from a workspace they do not belong to |
| P-60, P-61 — two buttons that did nothing | a dead control is worse than a failing one |

The rule that came out of it, and that now holds everywhere: **the workspace
displayed at the top of the page decides what the assistant may see**, and
**every route taking a document id asks whether this user may read this
document**.

Measured on the final state: audio deposited and indexed in **9 s**, question
answered from the recording alone, the same question refused in another
workspace, the catalogue listing 4 documents then 2, **124 tests** green.

The defects are recorded in `REGISTRE_PROBLEMES.md`; the state they left is
described in `ETAT_DE_DEMO.md`.

---

---

## 10. To be filled until submission

| Step | Deadline | State |
|---|---|---|
| Timed rehearsal of the demonstration | 1 November | ☐ |
| Second rehearsal + backup take | 2 November | ☐ |
| **Video recording** | 3 November | ☐ |
| **Submission** | 4 November, 9 pm ET | ☐ |
| Confirmation received | — | ☐ |

---

*Last updated: 2 November 2026.*
