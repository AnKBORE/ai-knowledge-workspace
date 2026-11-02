# Delivery guide — Agentic Knowledge Workspace

> **Collaborative knowledge management, reinvented by AI.**
>
> For **public and private administrations, companies and development organisations**: centralise your documents, preserve your institutional memory and turn your knowledge into a **strategic asset**, queryable in natural language thanks to artificial intelligence.
>
> Built for the **Meta Global AI Developer Hackathon** — Theme: **Build for Your World** · Track: **Indie Developer** · Model: **Muse Spark Open** · Licence: **MIT**.

🇫🇷 French version: [GUIDE_DE_REALISATION.fr.md](GUIDE_DE_REALISATION.fr.md)

---

## 0. The vision in one sentence

**A collaborative workspace, augmented by AI, that turns an organisation's documents, scans and voice recordings into a knowledge base queryable in natural language — with cited sources, a versioned history and a complete audit journal.**

---

## 1. The problem

In many organisations, critical information is **scattered across paper files, e-mails, file shares and personal computers**.

**Consequences:**

- Knowledge is lost when colleagues leave.
- New members struggle to get up to speed.
- Considerable time is spent searching for information.
- Risks of duplication, errors and missing traceability.

**The knowledge exists, but remains hard to exploit.**

---

## 2. The intelligent solution

**Agentic Knowledge Workspace** is an AI-assisted knowledge management platform that lets organisations **centralise, secure and exploit** their documentary heritage.

Instead of searching manually through hundreds of documents, users ask a question:

> *"Can you show me the 2025 activity reports about capacity building for the monitoring and evaluation team?"*

The AI assistant analyses the **authorised** documents, produces a **synthetic answer** and **cites its sources precisely**.

Then, to go further, the user asks for an **agentic task**:

> *"Generate a summary with the dates, objectives, participants, results achieved and recommendations for those reports."*

The result is a **structured document**, exportable to **PDF or DOCX**, with its sources.

**Every document, every answer and every action remains traceable and verifiable.**

---

## 3. More than a simple RAG application

The hackathon rules **explicitly exclude basic RAG applications**. Our approach goes beyond the simple *question → search → answer* pattern: the platform adopts an **agentic logic oriented towards business tasks**.

| Basic RAG | Agentic Knowledge Workspace |
|---|---|
| Answering a question | Executing complete documentary tasks |
| Textual PDFs only | PDFs, scanned images and multimedia content |
| A single answer | Reports, sheets, summaries and structured notes |
| Weak traceability | Cited, verifiable sources |
| No organisational control | RBAC, versions and audit |
| Generic chatbot | A business solution adapted to the African field |

> ⚠️ Never present the project as "a chatbot that answers questions about documents". Always insist on **agentic + multimodal + traceability + governance**.

---

## 4. Key features — **MVP**

| Feature | Detail |
|---|---|
| **Secure authentication** | Argon2, CSRF, sessions, progressive lockout |
| **Governance and access control** | Multi-level RBAC, global and per-space permissions |
| **Advanced document management** | Storage outside the web root, SHA-256, immutable versions, soft delete |
| **Contextual AI assistant** | Natural-language answers grounded in authorised documents, cited sources |
| **Smart search** | Hybrid vector and lexical search, context understanding |
| **Content generation** | Summaries, briefing notes, structured reports, citations |
| **Audit and compliance** | Complete journaling, timestamps, immutable history |
| **Professional export** | PDF, DOCX, simplified sharing |

---

## 5. An innovation rooted in the field

The idea of **Agentic Knowledge Workspace** was born from the experience gained with **GES-SACREMENTS-KTO-BF**, a platform already deployed in some parishes of Burkina Faso for managing **training**, the **monitoring and evaluation of catechumens** and the **sacraments of Christian faithful**.

Despite the digitalisation of activities, documents, reports, **procedures** and institutional knowledge often remain **scattered across several media and systems**, making access, exploitation and enhancement difficult.

The same observation applies to **administrations, NGOs and companies**, where institutional memory is fragmented across multiple systems and media.

**Agentic Knowledge Workspace** meets this challenge by turning organisational documents and data into an **intelligent, centralised knowledge base, queryable in natural language**.

---

## 6. Use cases

| Audience | What it solves |
|---|---|
| **Public and private administrations** | Quickly find regulations, procedures, administrative archives and official reports, without depending on a key person. |
| **Companies** | Reduce document research time, capitalise know-how and improve knowledge sharing between teams. |
| **Development organisations** | Capitalise project knowledge, ease experience transfer and produce reliable, sourced reports. |
| **Faith-based organisations** | Preserve pastoral, administrative and training archives, as GES-SACREMENTS-KTO-BF already does in parishes. |

---

## 7. Architecture

```
Browser
   │
   ▼
public/index.php (front controller, routes, security headers)
   │
   ├── Controllers (Auth, AiAssistant)
   ├── Core (Router, View, Session, CSRF, Auth, PDO, Env)
   ├── Models (Utilisateur, Espace, Adhesion, Document, VersionDocument, Journal)
   ├── Services (Ai\RagService, Ai\MetaClient, ExportService, PermissionService, DocumentService, JournalService)
   └── Validators (PolitiqueMotDePasse)
         │
         ├── MySQL 8.4.13 (InnoDB / UTF8MB4)
         └── Muse Spark Open (Meta partner, OpenAI-compatible API)
```

### Repository layout

```
public/        front controller + assets (the only exposed folder)
config/        environment configuration
core/          HTTP, routing, session, CSRF, auth, PDO
controllers/   MVC controllers
models/        data access (users, spaces, documents, versions, journal)
services/      business logic (+ Ai/ for model integration)
validators/    input validation
views/         templates and pages (including views/public/landing.php)
storage/       files, logs, backups (never exposed)
migrations/    versioned schema
tests/         PHPUnit
```

### Main tables

| Table | Role |
|---|---|
| `utilisateur` | Accounts (global roles, Argon2 password) |
| `espace` | Workspaces |
| `adhesion` | Per-space roles (reader → manager) |
| `document` | Documents (owner, space, status) |
| `version_document` | **Immutable** versions (SHA-256, original name, size) |
| `document_chunks` | Indexed segments (hybrid search) |
| `journal` | **Append-only** audit journal |

---

## 8. Web interface

The workspace is split into **five navigable pages**, driven by the vertical menu:

| Page | URL | Role |
|---|---|---|
| **Question** | `/` | Ask a question, get a sourced answer |
| **Agentic task** | `/tache` | Request a note / report / sheet; the draft opens in a modal |
| **Documents** | `/documents` | List of indexed documents (versions, summary, download, deletion) |
| **Add a document** | `/ajouter` | Index a text, a PDF, an image or audio |
| **Audit journal** | `/journal` | Append-only traceability (DataTables, CSV export) |

A **public presentation page** is available without authentication: `/presentation` (FR / EN).

Every result opens in a **modal** with **PDF** and **DOCX** download.

---

## 9. Security

| Mechanism | Implementation |
|---|---|
| **Passwords** | Argon2id (bcrypt fallback) |
| **CSRF** | Per-session token, verified on every POST |
| **Sessions** | `HttpOnly` cookies, `SameSite=Lax`, regenerated on login |
| **Brute force** | Progressive lockout after 5 failures |
| **RBAC** | `PermissionService::peut()` and `peutAilleurs()`, deny by default |
| **Files** | Stored **outside the web root**, named by **SHA-256** |
| **Journal** | **Append-only**, detailed (title, version, uploader, fingerprint) |
| **Headers** | `CSP`, `X-Frame-Options`, `nosniff`, `Referrer-Policy` |

---

## 10. Tests

```powershell
php vendor/phpunit/phpunit/phpunit
```

**124 tests, 295 assertions** — covering authentication, RBAC, document versions, audit, RAG, exports and the multimodal services. No database server or API required (in-memory SQLite + deterministic fakes).

---

## 10.1 Human corrections and learning

### Why

A model always lets vocabulary or phrasing errors through. Rather than hoping for a perfect model, the product **captures the human correction** and reuses it. This is the honest answer to the low-resource language problem.

### The two tables

| Table | Decisive columns |
|---|---|
| `correction_document` | `document_id`, `langue`, `texte_corrige`, `texte_source`, `nom_fichier`, `fichier`, `auteur_id`, `statut` |
| `document_chunks` | stays **unchanged**: the raw text remains queryable |

> The corrected file is stored in `storage/files/corrections/<doc>/<sha256>.<ext>`
> and served **byte for byte**; only its text is extracted for learning.

### The flow

```
Indexed documents → ✏️  →  language + proposed name + file (.pdf/.docx/.txt/.md/.csv)
                        →  correction recorded (validated by nature)
                        →  immediately authoritative in the RAG
```

### The four effects

| Effect | Implementation |
|---|---|
| **Priority retrieval** | `RagService::correctionHits()` then `preferCorrections()`: the document's raw segments are replaced by the corrected text, one extract per document |
| **Labelled context** | `[Extract n — Title · CORRECTED VERSION <language> of <date>]` + explicit note to the model |
| **Cited sources** | `sources()` exposes `corrige: true` and `correction {id, langue, date, fichier}`; the UI shows a badge and a download link |
| **Style learning** | `Apprentissage::exemples()` injects validated phrasings into the translation / rephrasing prompts |

### The design rules

- **No approval**: whoever uploads masters the language or owns the document.
- **Original file kept**: a `.docx` stays a `.docx` (identical SHA-256 on download).
- **Proposed name**: `<document>_corrige_vN.<ext>`, editable, `N` incremental.
- **Deletion**: author or `document.supprimer`, **immediate** effect on the prompts.
- **Language isolation**: a Mooré correction never pollutes the French.

### Dedicated tests (6)

`ApprentissageTest` covers: absence of correction, vocabulary filtering, weight of rare terms (IDF), language isolation, example cap and **the real effect of deletion**.

### A pitfall to know

`correction_document` and `documents` do not share the same collation. Any SQL comparison between their `langue` columns raises `Illegal mix of collations`: the language preference is decided **in PHP**.

---

## 10.2 Agentic task — scope, open answers, conformity

The loop does not always produce an administrative document: the **output shape depends on the instruction**.

| Instruction | Output |
|---|---|
| "Write a report on…", "draft a note…" | **document** with the structure of its genre (note, report, minutes, letter, APC/PI lesson) |
| "Analyse the risks…", "what are the obstacles?" | **open answer**: a 2–3 sentence synthesis, 3 to 6 titled and cited sections, an operational conclusion — 400 words maximum |

### Document scoping

A document **named** in the instruction ("based on Meeting6") or **checked** in the task page selector bounds the whole task to those documents:

1. `documentScope()` compares the instruction with the accessible titles;
2. `document_ids` (explicit selection) wins over detection;
3. the intersection with **access rights** is always applied;
4. the context receives `SCOPE: … Do not use any other document`;
5. a `scope` step appears in the trace.

Without this scoping, a speech "based on Meeting6" cited another project's matrix: grounded extracts, but the wrong document.

### The conformity step

`verify` checks that each claim is **supported** by its extract.
`conformity` checks that the output **answers the instruction** — a draft can be perfectly grounded and off-topic. When rejected: one rewrite, then a second check. A JSON parsing failure counts as "conform": the step never blocks.

---

## 10.3 From draft to deliverable

The **✦** button in the result modal removes the `Sources : [n]` line, the `[n]`
citations, the working markers (`[Proposition]`, `[To complete…]`), the
`Référence documentaire` and `Note de méthode` sections, the horizontal rules and
the "— Proposition à partir de…" suffix. It also hides the sources and the trace,
and **updates the export payload**.

Safety rule: **remove the marker, never the sentence it introduced**. A second
click restores the initial state.

The cleaning works on the **text**, not the DOM: a heading wrapped in `<strong>`
escaped line-by-line patterns.

---

## 11. Execution phases

| Phase | Content | Status |
|---|---|---|
| **0 — Framing** | Repository, README, licence, `.env`, migrations | ✅ |
| **1 — Foundation** | Router, View, Session, CSRF, Auth, PDO | ✅ |
| **2 — Governance** | Users, spaces, RBAC, journal | ✅ |
| **3 — Documents** | Upload, SHA-256, versions, indexing | ✅ |
| **4 — RAG** | Chunking, embeddings, hybrid search | ✅ |
| **5 — Assistant** | Cited `answer()`, agentic `task()`, `summarize()` | ✅ |
| **6 — Multimodal** | OCR (vision) + audio transcription | ✅ |
| **7 — Export** | PDF (dompdf) + DOCX (native OOXML) | ✅ |
| **8 — UI** | 5 pages, modals, DataTables, public page | ✅ |
| **9 — Quality** | 124 tests, CSP, detailed audit | ✅ |
| **10 — Delivery** | Video ≤ 3 min, submission | ⏳ |

---

## 12. Roadmap — **for the future**

> These features are **not part of the submitted MVP**: they extend the platform and build on its existing foundations.

**Advanced collaboration** — mentions · comments · notifications · contextual discussions.

**Organisational steering** — decision dashboards · performance indicators · project tracking.

**Advanced AI** — document recommendations · detection of obsolete knowledge · specialised assistants per business domain.

---

## 13. Author

**Anatole KABORE**

**Information systems consultant · Full Stack developer · Cybersecurity specialist**

Based in Ouagadougou (Burkina Faso), I am an information technology professional specialised in the design, architecture and development of secure digital solutions. I support administrations, companies, NGOs and faith-based organisations in the digital transformation of their business processes through robust, scalable, user-centred web platforms.

- LinkedIn: <https://www.linkedin.com/in/anatole-kabore-73b076342>
- GitHub: <https://github.com/AnKBORE>

## 14. Verification and governance — 2 November additions

### 14.1 The three verification commands

| Command | When to use it | Duration |
|---|---|---|
| `php tools/smoke_admin_endpoints.php` | after any change to an administration endpoint | 3 s |
| `php tools/smoke_endpoints_anonymous.php` | before any commit touching `public/api/` | 5 s |
| `php tools/lint_docs.php` | before any commit touching `README*.md` or `docs/` | 1 s |

Both sweeps require the application to be running (`php -S 127.0.0.1:8080 -t public`) and read credentials from environment variables (`SMOKE_ADMIN_EMAIL`, `SMOKE_ADMIN_PASSWORD`, `SMOKE_MEMBER_EMAIL`, `SMOKE_MEMBER_PASSWORD`).

### 14.2 Create and govern a workspace

1. `/administration` → **Workspaces** tab → "Create a collaborative workspace".
2. Choose a name and a visibility (`restreint` or `public_interne`).
3. The creator becomes **manager** of the workspace (automatic membership).
4. **Memberships** tab → add a member with a local role (`lecteur`, `contributeur`, `animateur`, `responsable`).
5. To close a workspace: button in the *Lifecycle* column. Closing erases nothing, it only forbids new deposits.

### 14.3 Protect an account (Super Admin)

Several accounts may be protected.

| Action | Where | Effect |
|---|---|---|
| Protect | `/administration`, crown icon on the account row | the account becomes Super Admin, can no longer be deleted/suspended/demoted |
| Remove | crossed crown on the protected row | becomes an ordinary technical administrator again |
| Disable entirely | remove the last protection | no protected account left; a banner says so |
| Restore the minimum | `php tools/seed_admin.php` | re-applies one protection |
| Replace the password | `php tools/seed_admin.php --reset` | regenerates and prints a password |

Every change is journaled (`compte.super_ajout`, `compte.super_retrait`, `compte.super_desactivation`).

### 14.4 File a document in several workspaces

- **At deposit time**: the "Workspaces" field accepts several selections.
- **After deposit**: lock icon on the document row → choose workspaces and confidentiality, confirm with the administrator password.
- **Effect**: access follows the union of the workspaces; removing one does not remove the others.

### 14.5 Before the demonstration

```powershell
php tools/lint_docs.php
php vendor/phpunit/phpunit/phpunit
php tools/smoke_admin_endpoints.php
php tools/smoke_endpoints_anonymous.php
```

Then check on screen: filtered menu (member), `/journal` answering 403 to a member, working delete button, administration tabs, workspace filter in the document list.
