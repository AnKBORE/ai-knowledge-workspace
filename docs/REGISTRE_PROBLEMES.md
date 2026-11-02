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
| P-43 | The claim "the Super Admin is protected" was declared but never enforced | `compte_super_protege()` had no caller, and no endpoint could suspend or delete an account, so the guarantee was theoretical and untested | invariants moved into the model (`changerRole`, `changerStatut`, `supprimerLogique` refuse the protected account), suspend/delete/role endpoints added behind the password gate, seed made self-healing with `--reset`, 2 new tests | ✅ |
| P-44 | Uploads were broken: every deposit failed with a TypeError | a generator artefact had written literal newline escapes inside a // comment, so the assignment to $result was commented out and finalize() read an undefined variable | comment restored, $result assigned before use, the confidentiality argument now reaches the model; a scan for literal newline escapes in PHP sources passes | ✅ |
| P-45 | Deleting a document stopped working | the button binding was placed at the **end** of `workspace.js`: an earlier initialisation error prevented it from being reached, silently | delegation on `document` + early idempotent bind; the flag is declared **before** the call, otherwise it was reset and the delete fired twice | ✅ |
| P-46 | The accounts table showed a DataTables warning | my action block had been inserted inside the **name cell**, swallowing e-mail, role and status (6 headers, 2 cells) | row rebuilt, 6 columns for 6 headers, verified on the three inventories | ✅ |
| P-47 | `/categories` was readable by a plain reader | no guard on the page, while the endpoints required `document.deposer` / `document.supprimer` | 403 + dedicated message for anyone who can neither deposit nor delete, refusal journaled | ✅ |
| P-48 | Three endpoints answered with a redirect instead of a refusal | `$respond` only read `X-Requested-With`, so a JSON client received a 302 that `fetch` cannot read | content negotiation: 401 JSON for a JSON client, redirect for a native form | ✅ |
| P-49 | An inline script of the journal was blocked by the CSP | a pattern substitution was reported but not applied: the nonce was missing on the `<script>` tag | nonce added line by line, verified page by page (inline scripts = nonces) | ✅ |
| P-50 | `--transfer` suggested an addition | the name said "transfer" for a move, then changed meaning with the multiple model | `--grant` / `--revoke`, `--transfer` kept as an alias; `--help` describes the model | ✅ |
| P-51 | The refusal "Invalid e-mail address" does not say what is expected: `membre.recette@local` is rejected without explanation (`filter_var` requires a dot in the domain) | generic message, no hint about the format | explicit message with a valid example, in the API and in the profile form | ✅ |
| P-52 | The per-workspace document counter showed "Général — 0 documents" while Général held both documents | the count read `documents.espace_id` (primary column) instead of the `document_espace` link table | count computed on the pivot plus the primary column, verified: 2 and 2 | ✅ |
| P-53 | A scan without a text layer was refused with "The file is empty or unreadable" | generic message, no hint about the cause or what to do next | explicit message: no text extracted, check the page, OCR does not read Mooré yet | ✅ |
| P-54 | A scanned PDF whose images are not JPEG was refused with "file empty or unreadable", without the OCR even being attempted | the filter kept only JPEG streams (`FF D8 FF`); Flate bitmaps were skipped silently | raw 8-bit bitmaps (grey or RGB) are converted to PNG before the call, unusual encodings are refused explicitly — verified: a Mooré scan extracts 1,093 characters in 8.6 s | ✅ |
| P-55 | Vision (OCR) calls did not appear in the model call log | that path goes through `curl_multi` and bypasses `request()` | logging added on the parallel path, with the real URL and duration — verified: two lines at 200 in 8.5 s | ✅ |
| P-56 | A Mooré document was stored as `fr` while its body is Mooré | no detection existed: the column kept its default `fr` | detection at ingestion, Mooré first (ɛ ɔ ã ẽ ĩ õ ũ diacritics), then French/English by function words — verified: Mooré → `mo`, French → `fr`, English → `en` | ✅ |
| P-57 | Failed deposits left the `resultat` column **empty** in the journal | the column is an enum `succes / refus / erreur` and the worker wrote `echec`, a refused value: MySQL stored an empty string | the worker writes `erreur`, and `JournalService` normalises any value outside the enum to `erreur` — verified: asking for `echec` stores `erreur` | ✅ |
| P-58 | The workspace shown at the top did not limit the search: a question asked in "Chantier test" returned an answer taken from a "Général" document | retrieval received the user id only, never the active workspace | `RagService` receives the workspace, filters documents on the link table, and `ask.php` passes the session workspace — verified: same question, "Général" cites the audio (10 million), "Chantier test" cites its own note (4,200,000) | ✅ |
| P-59 | A question about the school budget, asked in a workspace that does not hold the document, received a **confident but wrong** answer about another site | retrieval always returned the best extracts however weakly relevant, and the model answered anyway | measured relevance threshold (0.25): below it, an explicit refusal naming the workspace; above it, a sourced answer — verified on four cases: 0.72 and 0.53 answer, 0.166 and 0.157 refuse | ✅ |
| P-60 | The "Summarise" button in the document list did nothing | the rows rendered by PHP carry `.doc-confirm`, and `bindDocConfirm()` was only called after a dynamic refresh: on load, no handler existed | binding moved to startup, beside the delete binding — the button now opens the confirmation modal, then the summary | ✅ |
| P-61 | The "Replay" button in the result modal often did nothing | it was active only when a question or a task had been memorised; after a summary, a clean or a translation, `lastRequest` was `null` and the click returned silently | each action now remembers its **endpoint** (summary included), and the button is **disabled** when there is nothing to replay — the tooltip says "Nothing to replay" | ✅ |
| P-62 | Answers appeared **entirely in bold**, and the translation pane showed literal `**` | the renderer turned every `**…**` into `<strong>`, while models wrap whole sentences; the translation pane is a textarea, where markers stay visible | bold now applies only to spans of **60 characters or fewer**; longer ones lose their markers; the translation strips `**` — verified on three cases | ✅ |
| P-63 | Answers and summaries showed their bullets as literal `*`: the result looked like a draft | the renderer only knew `**bold**` and line breaks, not lists | dedicated renderer: `*`/`-` bullets become `<ul>`, `1.` items become `<ol>`, titles stay bold, text is escaped — verified on the real Mooré summary | ✅ |
| P-64 | In the audio playback modal the **AUDIO IN** field stayed empty ("Select an option") although the document language is known | the language was neither carried by the button nor preselected on open; the list query did not even select the `langue` column | the document language is read in the list, carried by the button and preselected in the modal — verified: the button renders `data-document-lang="en"` | ✅ |
| P-65 | In "Chantier test", asking "list all documents" returned **4 documents**, two of them from another workspace, while the counter above said 2 | the catalogue path (document list) never received the workspace, unlike the search; P-58 had only fixed the search | `catalogChunks()` now receives the workspace and intersects, like the search — verified: Général returns 4 sources, Chantier test returns 2 | ✅ |
| P-66 | Three endpoints accepted a `document_id` without checking that the user may read **that** document: reformulation, translation, and the correction list and download | they only checked the global `document.consulter` right — a member could read a document from a workspace they do not belong to by guessing its id | one shared `PermissionService::peutConsulterDocument()` applied to the four endpoints, with a journaled refusal — verified in a member session: 403 on a Général document, 200 on their own | ✅ |
| P-67 | The **agentic task** did not respect the displayed workspace, and its requested documents were only intersected with permissions | `task()` never received the workspace; only the plain search applied it (P-58) | `task()` accepts the workspace, passes it to retrieval and intersects the requested documents with it — verified: Général cites documents 4 and 1, Chantier test documents 1 and 2 | ✅ |
| P-68 | Translation turned the common noun "the donor" into a proper noun **"Voodoonor"**, including in the English output where the sentence was already correct | the prompt asked to keep names exact without forbidding invention; the model phonetised "donor" into a name, and the result was cached | instruction added: a common noun stays a common noun, "the donor" is not a person; the corrupted cache was cleared and the translations regenerated — verified in English, French and Mooré: no invented name | ✅ |
