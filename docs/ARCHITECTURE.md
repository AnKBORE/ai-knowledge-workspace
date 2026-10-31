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

## 6. Agentic task — plan → retrieve → draft → verify → revise

`AiAssistantController::task()` → `RagService::task()`:

| Step | What happens |
|---|---|
| **plan** | The model splits the instruction into 2 to 4 search queries |
| **retrieve** | Each query calls `retrieveSimilar()` (so it **benefits from corrections**); results merged by `document_id \| version_id \| chunk_index` |
| **draft** | Grounded writing, with mandatory `[n]` citations |
| **verify** | A second call checks that each claim is **supported** by the context, and returns unsupported ones |
| **revise** | If claims are unsupported, a third call fixes the draft |

The full trace (`steps`) is returned to the UI: this is what separates an
**agentic** task from a plain prompt.

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

## 11. Tests

`vendor/bin/phpunit` — **100 tests, 230 assertions**, with no database server and
no API.

| File | What it protects |
|---|---|
| `AuthTest`, `PermissionServiceTest` | authentication, RBAC, lockout |
| `DocumentTest`, `DocumentServiceTest` | documents, versions, categories |
| `CategorieTest`, `AdhesionTest` | auto identifiers, portable slugs, memberships |
| `RateLimiterTest` | fixed window, separate keys, reset |
| `ApprentissageTest` | **learning engine**: language isolation, IDF, example cap, deletion taking effect |
| `MooreResourcesTest` | lexicon loaded, stem search, proverbs, bidirectional glossary |
| `AnonymiseurTest`, `AudioSplitterTest` | anonymisation, audio splitting |
| `RagServiceTest`, `ExportServiceTest` | retrieval, exports |

Fakes (`FakeMetaClient`) keep the suite **deterministic** and **free**.

---

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
