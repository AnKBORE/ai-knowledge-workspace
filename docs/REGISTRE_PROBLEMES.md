# Issue register & resolutions

> **Living document**: tracking of every blocker and deviation in the project.
> Each issue gets an **ID**. When it is resolved:
> 1. set the **Status** to ✅,
> 2. fill in **Resolution** (date + note),
> 3. add a row to the **Resolution log**.
>
> This is the **source of truth** for "where do we stand".

🇫🇷 French version: [REGISTRE_PROBLEMES.fr.md](REGISTRE_PROBLEMES.fr.md)

## Legend

| Symbol | Meaning |
|---|---|
| 🔴 | Open (blocking or to do) |
| 🟡 | In progress |
| ✅ | Resolved |

---

## 1. Open issues (to resolve)

| ID | Issue | Impact | Priority | Status | Resolution |
|---|---|---|---|---|---|
| **O4** | `transcription` (voice) and `vision` (scan) endpoints not confirmed by Meta's docs | Voice/scan multimodality is untested | Medium | 🔴 | — |
| **O9** | **Demo video** (3 min) not recorded | Central scoring element | 🔥 Critical | 🟡 | Script + checklist ready (`docs/DEMO_VIDEO_ET_SOUMISSION.md`) |
| **O10** | **Submission** to the hackathon not done | No submission => no prize | 🔥 Critical | 🟡 | Checklist ready; form to fill in |
| **O11** | `php` missing from the Windows PATH | `php …` commands fail | Low | 🟡 | Workaround: `serve.bat` / full path |

---

## 2. Resolved issues (history)

| ID | Issue | Resolution | Date |
|---|---|---|---|
| **R1** | Imprecise PHP/MySQL versions | Pinned to **PHP 8.4.27 / MySQL 8.4.13** | 2026-10-24 |
| **R2** | No database or schema | `ai_knowledge_workspace` database + 5 tables via `tools/migrate.php` | 2026-10-26 |
| **R3** | No RAG pipeline | `RagService` (chunk, index, retrieve, query) implemented | 2026-10-26 |
| **R4** | Assumed API endpoint (`/v1/chat/completions`) | Aligned on **`POST /v1/responses`**, model `muse-wonder-1.0` | 2026-10-26 |
| **R5** | Dependency on an uncertain embeddings endpoint | **Local embedding (hashing)** by default, `META_EMBEDDING_MODE=api` optional | 2026-10-26 |
| **R6** | No interface | Web UI (foundation design system): upload + chat + list | 2026-10-26 |
| **R7** | Working PDF upload | `smalot/pdfparser` + chunking + storage (e.g. "Offre technique", 150 chunks) | 2026-10-26 |
| **R8** | No tests | **19 green PHPUnit tests** | 2026-10-26 |
| **R9** | Complex manual commands | `setup.ps1` / `setup.bat` / `serve.bat` | 2026-10-26 |
| **R10** | PSR-4 errors (`services/ai` vs `Ai`) | Folder renamed `services/Ai`, autoload regenerated | 2026-10-26 |
| **R11** | No roadmap | `docs/GUIDE_DE_REALISATION.md` (9 phases) | 2026-10-26 |
| **R12** | Opaque API errors | **`APP_DEBUG=1`** mode → real error message | 2026-10-26 |
| **O3** | Team ID / credits form | Credits form **submitted** (Team ID filled in) | 2026-10-26 |
| **O8** | Private GitHub repository | Repository switched to **public** | 2026-10-26 |
| **O2** | $150 credits not visible | **Credits active**: "Free credits in use: $150.00" | 2026-10-26 |
| **O1** | API key / chat | **Baseten** key configured (Plan B); response `[OK]` | 2026-10-26 |
| **O7** | Client document indexed | Removed from the database; demo on a neutral document | 2026-10-26 |
| **O12** | Real API test | **Full RAG pipeline validated** (retrieve + Muse Spark Open) | 2026-10-26 |
| **O5** | Cited sources missing | `RagService::answer()` returns the **sources**; displayed in the UI | 2026-10-26 |
| **O6** | Agentic tasks | `RagService::task()` + "Agentic task" section in the UI | 2026-10-26 |
| **R13** | Auth / RBAC / versions / audit | **Phase 1 delivered**: `utilisateur`, `Session`, `Csrf`, `Auth`, `AuthentificationService`, login/logout, 30 green tests | 2026-10-26 |
| **R14** | RBAC (spaces & memberships) | **Phase 2 delivered**: `espace`, `adhesion`, `historique_adhesions`, `journal`, `PermissionService` (global/local, inheritance, private spaces), 37 green tests | 2026-10-26 |
| **R15** | Documents & versions | **Phase 3 delivered**: enriched `document`, `version_document` (SHA-256, unique numbers), `DocumentService` (storage outside the web root), live upload validated, 41 green tests | 2026-10-26 |
| **R16** | Audit journaling | **Phase 7 delivered**: append-only `JournalService` + instrumentation (login, logout, upload, question), live journal validated, 45 green tests | 2026-10-26 |
| **R17** | Automatic summary | **Delivered**: `RagService::summarize()` + `/api/summarize.php` + "Summarise" button in the UI, live summary validated (sources included), 47 green tests | 2026-10-26 |
| **R18** | Baseten credits (partners) | **Application submitted** ($100 + 2M TPM); use-case selection, answer by e-mail | 2026-10-26 |
| **R19** | Effective RBAC, attached documents, versions, deletion | **Delivered**: RBAC applied to the 4 endpoints (200 responsable / 403 member without membership), `espace_id` filled, version badge, deletion + journal, 52 tests | 2026-10-26 |
| **R20** | Weak semantic search | **Delivered**: **hybrid** search (60 % cosine + 40 % lexical overlap), `search()` exposed, ranking test, 53 tests | 2026-10-26 |
| **R21** | Multimodality not demonstrated | **Delivered and tested**: **vision** via Muse Spark Open (`image_url`, OCR "BONJOUR LE MONDE") + **audio** via `audio_url` (JFK speech transcribed); end-to-end image upload validated | 2026-10-26 |
| **R22** | Audio limited to 5 min | **Delivered**: internal splitting into 5-minute chunks (WAV/MP3, **streaming**), app limit **10 min** with a clear message, increased timeout/memory, 55 tests | 2026-10-26 |

---

## 3. Resolution log

> One row per resolved issue (chronological order). This is where a resolution is "notified".

| Date | ID | Notification |
|---|---|---|
| 2026-10-24 | R1 | Secure versions PHP 8.4.27 / MySQL 8.4.13 integrated (docs + code). |
| 2026-10-26 | R2–R12 | Foundation, database, RAG, real API, UI, tests, scripts and guide delivered. |
| 2026-10-26 | — | **Finding**: upload works (150 chunks); chat fails because `META_MODEL_API_KEY` is empty (**O1**). |
| 2026-10-26 | O3 | Credits form **submitted** (Team ID filled in). |
| 2026-10-26 | O8 | GitHub repository switched to **public**. |
| 2026-10-26 | O2 | $150 credits activated ("Free credits in use: $150.00"). |
| 2026-10-26 | O1 | Baseten key configured (Plan B); test call `[OK]`. |
| 2026-10-26 | O7 | Client document deleted; demo on `docs/sample-report.txt`. |
| 2026-10-26 | O12 | Full RAG pipeline validated (sourced answer on the neutral report). |
| 2026-10-26 | O5 | Cited sources displayed under the answer. |
| 2026-10-26 | O6 | Agentic task ("Generate a draft") working. |
| 2026-10-26 | R13 | Phase 1 (auth, CSRF, anti-brute-force) delivered; 30 green tests. |
| 2026-10-26 | R14 | Phase 2 (spaces, memberships, PermissionService) delivered; 37 green tests. |
| 2026-10-26 | R15 | Phase 3 (versioned documents, SHA-256, storage outside the web root) delivered; 41 green tests. |
| 2026-10-26 | R16 | Phase 7 (append-only audit journal) delivered; 45 green tests. |
| 2026-10-26 | R17 | Automatic summary delivered (sources included); 47 green tests. |
| 2026-10-26 | R18 | Baseten credits application submitted ($100 + 2M TPM); answer by e-mail if selected. |
| 2026-10-26 | R19 | RBAC applied to the endpoints (403 verified); documents attached to a space; versions visible; document deletion. |
| 2026-10-26 | R20 | Hybrid search (semantic + lexical) delivered; retrieval noise removed; 53 green tests. |
| 2026-10-26 | R21 | Vision via Muse Spark Open validated live (OCR); audio (`audio_url`) validated live (JFK speech); end-to-end image upload. |
| 2026-10-26 | R22 | Audio up to 10 min (streaming splitting into 5-minute chunks); clear message beyond 10 min. |

---

## 4. Notification convention (to follow)

When an issue is resolved:

1. In the **"Open issues"** table: set the **Status** to **✅** and fill in **Resolution** (date + short note).
2. **Move** the row to **"Resolved issues"** (or mark it ✅).
3. Add a row to the **Resolution log**:
   ```
   | YYYY-MM-DD | O<num> | <what was done, in one sentence> |
   ```
4. Update the **final checklist** of the guide (`GUIDE_DE_REALISATION.md`).
5. If applicable, update the **README** (if the issue concerned documentation).

> Golden rule: **no issue disappears without a trace** — it is always resolved + journaled.

---

## 5. Phase 5 incidents — corrections and RAG

| # | Symptom observed | Real cause | Resolution | State |
|---|---|---|---|---|
| P-20 | Answer "Meeting6 contains no Mooré" while a correction exists | `Illegal mix of collations` between `correction_document.langue` (`utf8mb4_unicode_ci`) and `documents.langue` (`utf8mb4_0900_ai_ci`); the exception was swallowed by a `catch` | language preference decided **in PHP**, no SQL comparison left | ✅ |
| P-21 | Correction never applied in question / agentic task | same cause as P-20, but silent: `catch (Throwable) { return $chunks; }` | `catch` reduced to the strict minimum + query fixed | ✅ |
| P-22 | Answer truncated mid-sentence, without source | `max_tokens` ceiling at 1,200 (set for translation latency) | ceiling 3,000; translations stop on their own | ✅ |
| P-23 | Empty SOURCES panel despite a grounded answer | the truncated answer cited no `[n]`, and sources are derived from citations | `sourcesFor()`: fallback to the first three extracts | ✅ |
| P-24 | "Corrected version" badge invisible despite a deployed fix | HTML pages not versioned → old `?v=` → old JS cached | `Cache-Control: no-store` on every page | ✅ |
| P-25 | `.docx` correction rejected | allow-list limited to `.txt/.md/.csv` + English label not updated | `.pdf` and `.docx` accepted, PDF/DOCX extraction, labels fixed in FR **and** EN | ✅ |
| P-26 | Correction downloaded as `.txt` instead of its original format | the file was discarded, only the text kept | file copied into `storage`, served byte for byte (identical SHA-256) | ✅ |
| P-27 | Meeting recording silent for 47 s | deadlock: a rejected chunk consumed a sequence number that was never filled | sequence allocated **at send time** | ✅ |
| P-28 | Missing words in live transcription | threshold computed on the **peak**: a quiet voice fell below 0.012 and 6 s were discarded | **RMS** threshold 0.0015 | ✅ |
| P-29 | "Are you going today?" rendered "Do you want it today?" | the inflected form `rabda` did not match the `rabde` lexicon entry | search by **stem** (4 letters) | ✅ |
| P-30 | Test corrections left in the database, became authoritative | API checks performed without cleanup | test corrections deleted, the real correction kept | ✅ |

---

## 6. Phase 6 incidents — documentary precision and deliverable

| # | Symptom observed | Real cause | Resolution | State |
|---|---|---|---|---|
| P-31 | "Tell me about Meeting6" described a Mooré text while the document is a French audio | `preferCorrections()` **replaced** every extract with the correction: the document lost its identity | identity card (nature, original language, category, date, file) **plus** the corrected versions | ✅ |
| P-32 | "Translate it" impossible; the answer claimed the French transcript was missing | the original text was removed from the context | third extract "Original text (FR)" kept | ✅ |
| P-33 | "Translate it" produced a description instead of a translation | no detection of the translation intent | `wantsTranslation()` + rule 4h (produce the translation) | ✅ |
| P-34 | A speech "based on Meeting6" cited another project's matrix | the planner searched the **whole** corpus; no scope constraint | `documentScope()` + `document_ids` + `scope` step + `SCOPE` note | ✅ |
| P-35 | Sources of a listing question carried no "corrected" badge | the **catalogue** path bypassed `preferCorrections()` | `annotateCorrections()`: correction declared without overwriting the metadata | ✅ |
| P-36 | "in Mooré" answered **in** Mooré instead of presenting the Mooré content | confusion between content language and answer language | rule 4g + `requestedLanguage()` + the requested extract sorted first | ✅ |
| P-37 | Cleaning left "Référence documentaire", "Note de méthode", `[Proposition]` | cleaning worked on the **HTML**: headings in `<strong>` escaped the patterns | cleaning on the **text**, then the view is rebuilt | ✅ |
| P-38 | The "Sources removed…" notice appeared in the text and went into the DOCX | the notice was added **inside** `#result-content` | inserted as a **sibling** of the content | ✅ |
| P-39 | ISO dates inside sentences and labels | labels and dates built in hard code, without language | `t()` + `formatDate()` + rule 4a (dates in the answer's language) | ✅ |
| P-40 | A fix invisible despite a deployment | tab running the previous JavaScript | `no-store` pages + `Ctrl+F5` documented in the demo protocol | ✅ |
| P-41 | A member could list, and download by id, documents of a space they do not belong to | the document list (page + API) and `download_document` only checked the GLOBAL permission `document.consulter`, never the document itself | `PermissionService::documentsVisibles()` shared with the retrieval engine; list, search and download are scoped, a hidden document returns 404 (existence not revealed) | ✅ |
| P-42 | A simple member could open the audit journal page and read every entry (IP addresses, actions) | the `/journal` page never checked `journal.lire`, unlike its CSV export and its purge | 403 and a clear message for anyone without the permission, refusal journaled; verified: member 403 / 0 rows, administrator 200 / 58 rows | ✅ |
