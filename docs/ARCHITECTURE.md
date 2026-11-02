# Architecture — Agentic Knowledge Workspace™

> Technical reference. It describes **what the code actually does**, not what we
> wish it did. Known limitations are stated explicitly (section 12).

🇫🇷 French version: [ARCHITECTURE.fr.md](ARCHITECTURE.fr.md)

| Metric | Value |
|---|---|
| Web routes | **8** |
| API endpoints | **20** |
| Migrations | **21** |
| Services | **22** |
| Models | **10** |
| Views | **17** |
| Test files | **23** |
| Tests / assertions | **100 / 230** |
| PHP | **8.4.27** · MySQL **8.4.13** |

---

## 1. Architectural principles

| Principle | Implementation |
|---|---|
| **Strict MVC, no framework** | `public/index.php` routes, controllers (`controllers/`) orchestrate, models (`models/`) own SQL, views (`views/`) render |
| **PSR-4** | `App\Core\`, `App\Controllers\`, `App\Models\`, `App\Services\`, `App\Validators\`, `App\Services\Ai\` |
| **No heavy dependency** | only `dompdf` + `pdfparser`; DOCX is generated as native OOXML through `ZipArchive` |
| **Storage outside the web root** | `storage/files/` — never reachable by direct URL |
| **Single entry point** | `public/` is the only exposed root; everything goes through the front controller or `public/api/*` |
| **Traceability** | every sensitive action writes to `journal` (append-only) |

---

## 2. Directory map

```
ai-knowledge-workspace/
├── bootstrap.php            # Dependency assembly (simple container)
├── public/
│   ├── index.php            # Router + view rendering
│   ├── api/                 # 20 JSON / file endpoints
│   └── assets/              # css, js, vendor (DataTables, jQuery, Bootstrap, Select2)
├── controllers/             # AiAssistantController, AuthController…
├── core/                    # Auth, Csrf, Session, Env, i18n, helpers
├── models/                  # 10 PDO models
├── services/                # 22 services (10 of them in Ai/)
├── views/
│   ├── layouts/             # app.php, auth.php, public.php
│   ├── ai/                  # question, task, translate, upload, documents, journal, profil, categories
│   ├── public/              # landing.php (bilingual presentation page)
│   └── auth/                # login
├── migrations/              # 21 .up.sql / .down.sql pairs
├── tests/                   # 23 files, 100 tests (in-memory SQLite)
├── docs/                    # this documentation
└── storage/files/           # documents + corrections (outside the web root)
```

---

## 3. Data model

| Table | Purpose |
|---|---|
| `utilisateur` | Accounts, `role_global` (membre / administrateur_fonctionnel / administrateur_technique), Argon2id |
| `jeton_compte` | Verification / reset tokens |
| `tentative_connexion` | Progressive lockout per e-mail + IP |
| `espace` / `adhesion` / `historique_adhesions` | Multi-space isolation, local roles (lecteur → responsable), history |
| `documents` | Metadata: title, file, `langue`, `categorie_id`, confidentiality, status |
| `version_document` | Immutable versions: number, SHA-256 fingerprint, author, comment |
| `document_chunks` | Indexed segments (text + JSON vector + `chunk_index`) |
| `categorie` | Business categories (auto id, unique slug, colour) |
| `ingestion_jobs` | Background ingestion queue (non-blocking upload) |
| `rate_limit` | Fixed-window counters (abuse protection) |
| **`correction_document`** | **Validated corrected versions: language, corrected text, source text, original file, name, author** |
| `document_translations` | Per-segment translation cache (audio karaoke) |
| `moore_corpus` | Mooré corpus (reference sentences) |
| `journal` | Append-only audit (actor, action, object, result, IP, JSON details) |

### N‑N document ↔ workspaces relation (migration 025)

`documents.espace_id` was not enough: one report may concern two projects. The `document_espace` table carries the actual filing.

| Element | Role |
|---|---|
| `document_espace (document_id, espace_id)` | the set of workspaces of a document, unique constraint |
| `documents.espace_id` | **primary workspace**, kept for display and compatibility; always included in the link |
| `ingestion_jobs.espace_ids` (JSON, migration 026) | context of a deferred deposit, so the worker does not attach everything to the first workspace |

**Access rule**: `visible = owner OR (not private AND at least one accessible workspace)`. The union is computed by `PermissionService::documentsVisibles()`, shared by the list, the search, the download and the retrieval engine. A document without any workspace stays visible to its owner only.

### Protected accounts (Super Admin)

`utilisateur.compte_super` marks a **set** of protected accounts, not a single seat.

| Invariant | Enforced by |
|---|---|
| no deletion, suspension or demotion | `changerRole`, `changerStatut`, `supprimerLogique` refuse any protected account |
| free grant and revoke, down to zero if desired | `accorderSuperAdmin()` / `retirerSuperAdmin()` |
| traceability | `compte.super_ajout`, `compte.super_retrait`, `compte.super_desactivation` |
| bootstrap after a wipe | `compteSuperId()` returns the oldest; `tools/seed_admin.php` re-applies the minimum |
### Pitfall: collations

`correction_document` was created with `utf8mb4_unicode_ci` while `documents`
uses `utf8mb4_0900_ai_ci`. **Any SQL comparison between their `langue` columns
raises `Illegal mix of collations`.** Language preference is therefore decided
**in PHP**, never in SQL (see `RagService::preferCorrections()`).

---

## 4. Document ingestion flow

```
Upload (public/api/upload.php)
   ├─ size / MIME / extension checks
   ├─ version_document: SHA-256 fingerprint, incremental number
   ├─ file written to storage/files/
   └─ ingestion_jobs: queued task
        ↓ (BackgroundWorker, outside the HTTP request)
     IngestionService
        ├─ PDF          → `smalot/pdfparser`
        ├─ DOCX         → `ZipArchive` on word/document.xml
        ├─ audio        → TranscriptionService (ASR cascade)
        ├─ image / scanned PDF → VisionService (page-by-page OCR, parallel calls)
        ├─ anonymisation (Anonymiseur) when requested
        └─ RagService::indexDocument() → 800-char chunks / 100 overlap
                                       → EmbeddingService (local hashing, zero cost)
                                       → INSERT document_chunks
```

**Why a worker:** a 40-page scanned PDF takes minutes. The HTTP request returns
immediately and `upload_status.php` exposes progress.

---

## 5. Retrieval engine (RAG++)

### 5.1 Hybrid score

```
score = 0.6 × cosine similarity (vectors) + 0.4 × lexical score (token overlap)
```

With `META_EMBEDDING_MODE=local`, vectors are computed by **hashing**: indexing
costs **no API call**, at the price of a coarser semantics than a real embedding
model.

### 5.2 Neighbour expansion

A title or heading often sits in the chunk **adjacent** to the best hit.
`expandWithNeighbors()` merges `chunk_index ± 1` **and** the first chunk of the
same version: the model sees the full passage, not a fragment.

### 5.3 Correction priority — the heart of the learning module

```
retrieveSimilar()
   ├─ vector + lexical search over document_chunks
   ├─ correctionHits()      ← DEDICATED lexical search over correction_document
   │                          (a correction is not in the index: without this,
   │                           a question matching only the corrected text
   │                           would find no document at all)
   └─ preferCorrections()   ← replaces ALL chunks of a corrected document with
                              the corrected text, one extract per document
```

Correction selection rules:

1. the one written in the **document's own language** first;
2. otherwise, the **most recent**.

The context sent to the model is labelled:

```
NOTE: an extract marked « VERSION CORRIGÉE » is the validated reference for that
document. Always prefer it over any other extract of the same document, and say
so when you cite it.

[Extract 1 — Meeting6 · VERSION CORRIGÉE MO of 2026-10-31]
Ne y waoongo, nẽn y kende…
```

### 5.4 Sources

`sourcesFor()` returns the extracts the model **cited**; if it cited nothing
(forgot, or the answer was cut), the **first three extracts** are listed. A
grounded answer must **never** look sourceless.

Each exposed source carries:

```json
{
  "num": 1, "document_id": 30, "title": "Meeting6",
  "corrige": true,
  "correction": { "id": 5, "langue": "mo", "date": "2026-10-31 16:36:45",
                  "fichier": "meeting6_corrige_v1.txt" }
}
```

---

### 5.5 Two answer modes — strict vs developed

The server decides the mode; the model does not guess it.

| Mode | Trigger | Shape |
|---|---|---|
| **Closed** | how many, which, when, who, yes/no, a precise value | **one to three sentences**, the first being the answer; no preamble, no unsolicited inventory, bullets only if several items are requested |
| **Open** | summarise, tell me about, explain, describe, what does it say, compare, key points | **developed but structured**: an opening sentence, 3 to 6 bullet points, a closing sentence only if it adds a fact — **150 words maximum** |

Detection lives in `RagService::isOpenQuestion()` (French, English and Mooré
markers). The chosen mode is written into the prompt as an explicit instruction,
so the same question always gets the same shape.
---

### 5.6 Wording and dates follow the language

Every label and date produced by the retrieval layer is built in the interface
language, so the model reads `Registered on: 31 October 2026` and never a raw
ISO string in the middle of a sentence.

| Concept | French | English |
|---|---|---|
| Labels | Titre · Nature · Langue d'origine · Catégorie · Enregistré le · Versions corrigées · Fichier | Title · Type · Original language · Category · Registered on · Corrected versions · File |
| Date | `31 octobre 2026` | `31 October 2026` |
| Nature | enregistrement audio (transcrit) · document PDF · scan (OCR) · document Word | audio recording (transcribed) · PDF document · scan (OCR) · Word document |

The answer prompt adds rule 4a: dates are written the way the language writes
them, never ISO — the value itself is never altered.
---

## 6. Agentic task — plan → retrieve → draft → verify → revise

`AiAssistantController::task()` → `RagService::task()`:

| Step | What happens |
|---|---|
| **plan** | The model splits the instruction into 2 to 4 search queries |
| **retrieve** | Each query calls `retrieveSimilar()` (so it **benefits from corrections**); results merged by `document_id \| version_id \| chunk_index` |
| **draft** | Grounded writing, with mandatory `[n]` citations |
| **verify** | A second call checks that each claim is **supported** by the context, and returns unsupported ones |
| **revise** | If claims are unsupported, a third call fixes the draft |
| **conformity** | A final check asks whether the output actually **answers the instruction**. A grounded draft can still be off-topic (describing the project when a risk analysis was asked); if rejected, one rewrite pass is made and the check is repeated. This step never blocks: a parsing failure counts as "conform". |

The full trace (`steps`) is returned to the UI: this is what separates an
**agentic** task from a plain prompt.

---

### 6.1 Document or open answer

The task loop does not always produce an administrative document. Rule 7 of the
task prompt switches the output shape when the instruction is a question or an
analysis (`?`, why / how / which / analyse / compare / explain / evaluate /
propose):

| Instruction | Output |
|---|---|
| "Write a report on…", "draft a note…" | **document** with the structure of its genre (memo, report, minutes, letter, APC/PI lesson plan) |
| "Analyse the risks of…", "what are the obstacles?" | **open answer**: a 2-3 sentence synthesis, 3 to 6 titled and cited sections, one operational conclusion — 400 words maximum, no "objet", no addressees, no salutation |

Both modes obey the same grounding rules: every claim comes from an extract and
carries its `[n]` citation, and the answer ends with `Sources : [n]`.

Rules 8 to 10 complete the picture: describe a document by its real nature and
original language, treat a corrected version as a translation, and produce an
actual translation when the instruction asks one.
---

### 6.2 Document scope — "based on Meeting6"

When the instruction names a document ("à base de Meeting6", "d'après
Meeting6", "from Meeting6"), that name **bounds the whole task**:

1. `documentScope()` matches the instruction against the titles the user may
   access (longest title wins);
2. retrieval results are filtered to that document, falling back to
   `chunksForDocument()` when the filter leaves nothing;
3. the context is prefixed with `SCOPE: … N'utilise aucun autre document`;
4. a `scope` step is added to the trace.

Without it, the planner searched the entire corpus and a speech "based on
Meeting6" quoted an unrelated project matrix — the extracts were grounded, but
about the wrong document.
---

## 7. Translation pipeline

### 7.1 The six directions

`fr ↔ en`, `fr ↔ mo`, `en ↔ mo` — no privileged direction.

### 7.2 Prompt composition (by importance)

| Layer | Source | Role |
|---|---|---|
| **Strict rules** | code | output only, entities/figures/dates preserved, month names localised, no omission |
| **Business glossary** | `Glossaire` | 20 terms (catechist, monitoring and evaluation, vicariate…), **disabled for a Mooré target** |
| **SIL lexicon** | `LexiqueMoore` | 1 349 verified entries, **stem** lookup (inflected `rabda` matches `rabde`) |
| **Proverbs** | `Proverbes` | 40 Mooré proverbs, **cultural** reference only |
| **Mooré reference** | `MooreReference` | orthography (ɩ ʋ ã), pronouns, negation `ka…ye`, greetings |
| **Validated corrections** | `Apprentissage` | style examples approved by the organisation |

### 7.3 Learning engine (`Apprentissage`)

This is **not** fine-tuning, and that is a deliberate choice: fine-tuning on a few
dozen corrections would overfit and could not be demonstrated.

```
1. Load up to 500 validated corrections in the language
2. Compute the document frequency of every query term
3. Score = Σ IDF(term) + recency bonus + shared-term count bonus
4. One example per document, de-duplicated by signature, 2 600-character budget
5. Excerpt centred on the shared term, 480 characters
```

A rare term ("vicariate") therefore weighs far more than a generic one
("project"). Verified by `ApprentissageTest` (6 tests).

### 7.4 Production guardrails

| Guardrail | Effect |
|---|---|
| `reasoning_effort: low` | removes the model's "hesitation" on a mechanical task |
| `max_tokens: 3000` | prevents truncated answers (translations stop on their own) |
| Length ratio | if output is < 0.35× or > 3.5× the source, **retry** with a repair instruction |
| Temperature 0.0 / 0.7 | 0.0 to translate (determinism), 0.7 to rephrase (variation) |
| No silent `catch` | every failure surfaces an explicit `translation_error` |

**Measured latency**: 4.6 s → **2.2-3.0 s** after these settings, with a **stable**
output across three consecutive runs.

---

## 8. Meeting recording (transcription)

### 8.1 Architectural choice

**Chunked streaming** was implemented and then **removed**: the intrinsic latency
(ASR + translation per chunk) made real time unstable.

```
Today: record → Stop → ONE call over the complete audio
```

A single call is **measurable and reproducible**; live depended on model load.

### 8.2 Interface

| Area | Content |
|---|---|
| Header | microphone, "Meeting recording", document name |
| Bar | REC, timer (frozen while paused), **audio level** |
| Fields | **Spoken language** ("Automatic detection" by default) · **Output** ("Same language" = transcript) |
| Note | "The transcript is generated when the recording ends…" |
| Result | transcript + actions **↻ Rephrase · 📄 DOCX · ⤓ PDF** |

### 8.3 Design decisions

- **Output = same language** → transcript only; **another language** → translation.
- **RMS threshold, not peak**: a peak below 0.012 discarded 6 s of quiet speech —
  that was the cause of the "missing words".
- **Real pause**: the timer freezes and no chunk is sent.

---

## 9. Human corrections — the learning module

### 9.1 Why

A model always lets vocabulary or phrasing errors through. A **native speaker**
(or the document's author) uploads a corrected version: the system then reuses it
as a **reliable reference**.

### 9.2 Flow

```
Indexed documents → ✏️  →  languages + proposed name + corrected file
                        →  correction_document (+ file in storage)
                        →  immediately usable by the RAG
```

### 9.3 Rules

| Rule | Reason |
|---|---|
| **No approval step** | whoever uploads masters the language or owns the document |
| **Original file kept** | `storage/files/corrections/<doc>/<sha256>.<ext>` — served **byte for byte** on download |
| **Formats** | `.pdf`, `.docx`, `.txt`, `.md`, `.csv` — a `.docx` **stays** a `.docx` |
| **Proposed name** | `<document>_corrige_vN.<ext>`, **editable**, `N` = corrections + 1 |
| **Deletion** | correction author **or** `document.supprimer`; **immediate** effect on prompts |
| **Listing** | visible in the modal, with language, author, date, size |

### 9.4 Text extraction

`TexteExtracteur`: `.pdf` via `smalot/pdfparser`, `.docx` via `ZipArchive`
(paragraphs → lines), plain text otherwise. The extracted text is used **only**
for learning and RAG — it never rebuilds the file.

---

### 9.5 Deliverable mode — one click to a clean text

The result modal's ✦ toggle turns a grounded answer into something a person can
read out loud.

| Removed | Kept |
|---|---|
| The `Sources : [n]` line and the inline `[n]` citations | every sentence that carries meaning |
| Bracketed working markers (`[Proposition]`, `[Élément issu…]`, `[À compléter…]`) | the sentence they introduced |
| The `Référence documentaire` and `Note de méthode` sections | the body |
| Horizontal rules and the "— Proposition à partir de…" title suffix | the title |
| The source panel and the agent trace (visually) | — |

Two implementation details matter:

1. it operates on the **text**, not the DOM — a heading wrapped in `<strong>`
   escaped the line-based patterns when the markup was stripped;
2. it updates the export payload too, so the DOCX and the PDF contain exactly
   what is displayed. A second click restores everything.

A bracketed year such as `[2026]` is preserved: only 1–2 digit citations are
treated as references.
---

## 10. Security

| Measure | Detail |
|---|---|
| **Passwords** | Argon2id, complexity policy, progressive lockout |
| **Sessions** | `HttpOnly` cookie, `SameSite=Lax`, regenerated on login |
| **CSRF** | 11/15 state-changing endpoints, `_csrf` field **or** `X-CSRF-Token` header (global `fetch` wrapper) |
| **RBAC** | 2 levels: global (`membre` → `administrateur_technique`), local (`lecteur` → `responsable`) |
| **Rate limiting** | `rate_limit`: `ask` 30/5 min, `summarize` 20, `translate` 30, `reformulate` 30, `correction` 30 |
| **Headers** | CSP, `X-Frame-Options`, `X-Content-Type-Options: nosniff`, `Referrer-Policy` |
| **Cache** | pages served `no-store`: an old page can no longer pin an old `?v=` |
| **Files** | storage outside the web root, random name (SHA-256), `basename()` on every served name |
| **Journal** | append-only; restricted legal purge (GDPR), re-authenticated and journaled |

---

### 10.1 What the audit journal records

| Family | Actions |
|---|---|
| **Authentication** | `connexion` (success **and** refusal, including lockout), `deconnexion` |
| **Documents** | `document.depot`, `document.consultation`, `document.suppression`, `document.export`, `document.resume` |
| **Corrections** | `document.correction`, `document.correction.consultation`, `document.correction.telechargement`, `document.correction.suppression` |
| **AI usage** | `assistant.question`, `traduction`, `reformulation` |
| **Administration** | `categorie.creation/modification/suppression`, `profil.maj`, `profil.mot_de_passe`, `journal.export`, `journal.purge` |

Refused requests are recorded too, with `resultat = refus`:

| Refusal | Detail |
|---|---|
| **CSRF token missing or stale (419)** | `securite.refus` · `{"raison": "csrf", "chemin": "/api/translate.php"}` |
| **Missing permission (403)** | `securite.refus` · `{"raison": "permission", "chemin": "…"}` |
| **Wrong purge password** | `journal.purge` · `refus` |
| **Locked account / bad credentials** | `connexion` · `refus` |

Exporting the journal (`journal.export`) and any generated file
(`document.export`) are audited too: the trail cannot be exfiltrated silently.

**Deliberate choice:** an **unauthenticated** request rejected with 401 is *not*
recorded. A bot could otherwise flood the append-only journal and bury the real
events. Only refusals involving a real session are traced.
---

### Nonce-based CSP

`script-src 'self' 'nonce-…'`: the nonce is generated once per request (`csp_nonce()`), carried by the three inline scripts, and **no inline script runs without it**. Inline event handlers (`onsubmit="…"`) were replaced by `data-*` attributes handled in JavaScript. `style-src` keeps `'unsafe-inline'` for the dynamic colours of the views.

### Content negotiation

An endpoint receiving `Accept: application/json` (or `X-Requested-With: XMLHttpRequest`) **always** answers JSON, never a redirect: a `fetch` cannot read a 302. Native forms keep the redirect with a session flash.

### Page protection

`/journal` requires `journal.lire`, `/administration` at least one administration permission, `/categories` the ability to deposit or delete a document. Without the right, the page answers **403** and the refusal is journaled (`securite.refus`).

## 11. Tests

`vendor/bin/phpunit` — **121 tests, 281 assertions**, without a database or an API.

| File | What it protects |
|---|---|
| `AuthTest`, `PermissionServiceTest` | authentication, RBAC, lockout |
| `PermissionServiceDocumentsVisiblesTest` | **visibility through the union of workspaces**: owner, private, internal public space, suspended account |
| `DocumentTest`, `DocumentServiceTest` | documents, versions, categories, **remplacerEspaces()** and the primary column |
| `UtilisateurSuperTest` | **protected accounts**: coexistence, partial revocation, full deactivation, refusal of deletion/suspension/demotion |
| `CategorieTest`, `AdhesionTest` | auto identifiers, portable slugs, memberships |
| `RateLimiterTest` | fixed window, distinct keys, reset |
| `ApprentissageTest` | **learning engine**: per-language isolation, IDF, example cap, effective deletion |
| `MooreResourcesTest` | loaded lexicon, root search, proverbs, bidirectional glossary |
| `AnonymiseurTest`, `AudioSplitterTest` | anonymisation, audio splitting |
| `RagServiceTest`, `RagServiceScopeTest`, `ExportServiceTest` | retrieval, scoping, exports |

The doubles (`FakeMetaClient`) make the tests **deterministic** and **free**.

### Three verification tools outside PHPUnit

The endpoints are scripts (`require bootstrap`, `exit`): PHPUnit cannot call them. Three commands cover what the unit tests do not reach.

| Command | What it proves |
|---|---|
| `php tools/smoke_admin_endpoints.php` | 9 administration endpoints × 3 profiles (anonymous 401, member 403, admin without password 422) — **27 checks** |
| `php tools/smoke_endpoints_anonymous.php` | 31 endpoints × 2 methods **without a session**: everything is refused, no data key exposed |
| `php tools/lint_docs.php` | documentation structure (glued headings, tables, escaping, leftovers) |
## 12. Known limitations (accepted)

| Limitation | Reality |
|---|---|
| **Mooré, a low-resource language** | the lexicon covers 1 349 entries against ~10 650 in the SIL dictionary: complex constructions remain approximate. This is stated on the presentation page. |
| **Hashing embeddings** | fast and free, but less semantic than a real embedding model |
| **Retrieval-based learning** | no fine-tuning: the model is **guided**, not retrained |
| **CSP `unsafe-inline`** | inherited from view scripts; to be hardened (nonces) |
| **Real-time streaming** | removed in favour of a single reliable call |
| **No pagination** on some lists | acceptable up to a few hundred documents |

---

## 13. Notable engineering decisions

| Decision | Rejected alternative | Reason |
|---|---|---|
| **One call at stop** for recording | chunked streaming | model latency ≈ real time → unstable |
| **Retrieval** to learn from corrections | fine-tuning | instant, reversible, explainable, free |
| **Native DOCX** (ZipArchive) | Word library | zero dependency, full control |
| **Outside the web root** for files | `public/` | prevents any execution / direct leak |
| **SQLite in tests** | test MySQL server | fast tests, no server |
| **`no-store` on pages** | browser cache | an invisible fix is worse than a slow load |

---

## 14. Incident log (resolved)

| Symptom | Real cause | Fix |
|---|---|---|
| Answers with no source | truncated answer (`max_tokens` 1200) → no citation | ceiling 3 000 + fallback to the first three extracts |
| Corrections ignored (question/task) | `Illegal mix of collations` swallowed by a `catch` | language preference decided in PHP |
| "Are you going today?" rendered "Do you want it today?" | inflected `rabda` ≠ lexicon entry `rabde` | stem search (4 letters) |
| Recording silent for 47 s | deadlock: a rejected chunk consumed a sequence number | sequence allocated at real send time |
| Missing words in transcription | threshold on **peak** instead of **RMS** | RMS threshold 0.0015 |
| Fix visible only after clearing the cache | HTML pages not versioned | `Cache-Control: no-store` |
| `.docx` correction rejected | allow-list limited to `.txt/.md/.csv` | `.pdf` and `.docx` accepted + extraction |
| Download delivered `.txt` | file discarded, only text kept | original file stored and served as-is |

---

## 15. Going further

| Topic | Document |
|---|---|
| API security and privacy | `docs/SECURITE_DONNEES_API.md` |
| Brand and ownership | `docs/MARQUE_ET_PROPRIETE.md` |
| Delivery guide | `docs/GUIDE_DE_REALISATION.md` |
| Issue register | `docs/REGISTRE_PROBLEMES.md` |
| Demo and submission | `docs/DEMO_VIDEO_ET_SOUMISSION.md` |
