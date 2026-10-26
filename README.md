# Agentic Multimodal Knowledge Workspace

> An **agentic, multimodal** knowledge workspace for organizations: it ingests documents, scanned records and voice notes, then **acts** — answering questions with cited sources, drafting notes and reports, and summarizing documents.
>
> Built for the **Meta Global AI Developer Hackathon** — Theme: **Build for Your World** · Track: **Indie Developer** · Deadline: **November 4, 2026, 9:00 PM ET**.

---

## Why this is NOT a "basic RAG application"

The hackathon explicitly bans *basic RAG applications* (see the Participant Guide, §4). This project deliberately goes beyond a single question → answer loop:

| Basic RAG | This project |
|---|---|
| Text PDFs only | **Text + PDF + scanned images + audio** (multimodal ingestion) |
| One question → one answer | **Agentic tasks**: draft a note, a report, a lesson plan (multi-step) |
| No traceability | **Numbered, cited sources** on every answer and every draft |
| No governance | **RBAC**, immutable document **versions** (SHA-256), append-only **audit journal** |
| No export | **PDF / DOCX export** of every generated result |
| Generic chatbot | **A real domain**: parishes, NGOs and schools in Burkina Faso |

---

## Built during the hackathon

The repository contains two **clearly separated** layers:

- **New — built during the hackathon**
  - the AI layer: RAG pipeline (`services/Ai/`), `answer()`, `task()`, `summarize()`, cited sources;
  - PDF / DOCX export (`ExportService`);
  - the multi-page web workspace (Question, Agentic task, Documents, Upload);
  - the governance layer: accounts, RBAC (`PermissionService`), document versions, audit journal.
- **Pre-existing — reused foundation**
  - the **visual design system** (Bootstrap 5 + CSS) and the MVC conventions from the author's earlier `espace_collaboratif` project.
  - No proprietary client code or data is included.

> Every feature shown in the demo video was **written during the hackathon**.

---

## What works today

| Capability | Status |
|---|---|
| Text / PDF ingestion + chunking + indexing | ✅ |
| Semantic retrieval (cosine similarity) | ✅ |
| Grounded answers **with cited sources** | ✅ |
| Agentic drafting (notes, reports, lesson plans) | ✅ |
| Automatic document summarization | ✅ |
| PDF / DOCX export of results | ✅ |
| Session authentication (CSRF, brute-force lockout) | ✅ |
| RBAC (global + per-space roles) | ✅ |
| Immutable document versions (SHA-256) | ✅ |
| Append-only audit journal | ✅ |
| Scanned images (OCR via Muse Spark Open vision) | ✅ (live-tested) |
| Voice (audio via Muse Spark Open) | 🔌 code path ready (to test with a real audio) |
| SAM 3.1 / Muse Voice Transcribe (dedicated endpoints) | 🔜 optional (Meta Model API) |

---

## AI (Meta models)

The generation model is **Muse Spark Open**, served by a **Meta inference partner** (Baseten) through the OpenAI-compatible API. The design keeps the door open for the Meta Model API (`/v1/responses`) and the other Muse models.

| Model | Role | Where |
|---|---|---|
| **Muse Spark Open** | Answers, drafts, summaries | Baseten (`meta-models/muse-spark-open`) |
| Muse Wonder | Long-form report generation (option) | Meta Model API |
| Muse Voice Transcribe | Meetings and voice notes → searchable text | Roadmap |
| SAM 3.1 | Scanned-document processing | Roadmap |
| Muse Glimmer / Muse Spark Open | Open-weight Meta models | Baseten / Modal / Fireworks |

Two API styles are supported out of the box:

```dotenv
# Meta Model API
META_API_STYLE=meta
META_MODEL_API_BASE_URL=https://api.meta.ai
META_MODEL_NAME=muse-wonder-1.0

# Meta inference partner (OpenAI-compatible)
META_API_STYLE=openai
META_MODEL_API_BASE_URL=https://inference.baseten.co
META_MODEL_NAME=meta-models/muse-spark-open
```

---

## Tech stack

| Component | Version |
|---|---|
| PHP | **8.4.27** |
| MySQL | **8.4.13** (InnoDB, utf8mb4) |
| Frontend | HTML5, CSS3, Bootstrap 5, JavaScript ES6 |
| AI | Muse Spark Open via Baseten (OpenAI-compatible) |
| PDF | `dompdf` |
| DOCX | native OOXML (`ZipArchive`) |

---

## Project structure

```
public/        front controller + assets (the only exposed folder)
config/        environment configuration
core/          HTTP, routing, session, CSRF, auth, PDO
controllers/   MVC controllers
models/        data access (users, spaces, documents, versions)
services/      business logic (+ Ai/ for model integration)
validators/    input validation
views/         templates and pages
storage/       files, logs, backups (never exposed)
migrations/    versioned schema
tests/         PHPUnit
```

---

## Installation

```powershell
# 1. Dependencies
php tools/composer.phar install

# 2. Local configuration
Copy-Item .env.example .env

# 3. Database
php tools/migrate.php migrate

# 4. First administrator + default space
php tools/seed_admin.php

# 5. Run locally
php -S 127.0.0.1:8080 -t public
```

Then open <http://127.0.0.1:8080/login>.

In production the document root must point to `public/`.

On Windows, `.\setup.ps1` (or `setup.bat`) runs the steps automatically with PHP 8.4.27.

---

## Web UI

The workspace is split into **four navigable pages**, driven by the vertical menu:

| Page | URL | What it does |
|---|---|---|
| **Question** | `/` | Ask a question, get a grounded answer with cited sources |
| **Agentic task** | `/tache` | Ask for a note / report / lesson plan; the draft opens in a modal |
| **Documents** | `/documents` | List of indexed documents, one-click summary |
| **Upload** | `/ajouter` | Index a text, PDF, image or audio file |

Every result opens in a **modal** with **PDF** and **DOCX** download buttons.

API endpoints (session-protected):

- `POST /api/upload.php` — index a file (multipart)
- `POST /api/ask.php` — `{"question": "..."}` → answer + sources; `{"instruction": "...", "mode": "task"}` → draft + sources
- `POST /api/summarize.php` — `{"document_id": 3}` → summary + sources
- `POST /api/export.php` — `{"title", "content", "format": "pdf|docx"}` → file
- `GET /api/documents.php` — recent documents (JSON)

---

## Tests

```powershell
php vendor/phpunit/phpunit/phpunit
```

**50 tests, 104 assertions** — covering authentication, RBAC, document versions, audit, RAG, exports and the multimodal services. No database server or API required (SQLite in memory + deterministic fakes).

---

## Security

Prepared SQL statements · CSRF protection on every POST · password hashing (Argon2/bcrypt) · progressive brute-force lockout · RBAC resolved centrally (`PermissionService`) · deny-by-default · files stored outside the web root · no secrets in code or logs · audit journal (append-only) · security headers.

---

## Documentation

- [docs/GUIDE_DE_REALISATION.md](docs/GUIDE_DE_REALISATION.md) — execution roadmap (phases, checklists, milestones)
- [docs/REGISTRE_PROBLEMES.md](docs/REGISTRE_PROBLEMES.md) — issue register & resolution log
- [docs/DEMO_VIDEO_ET_SOUMISSION.md](docs/DEMO_VIDEO_ET_SOUMISSION.md) — demo video script & submission checklist

---

## Author

**Anatole KABORE** — Independent Software Engineer and Cybersecurity Specialist, Burkina Faso.

- LinkedIn: <https://www.linkedin.com/in/anatole-kabore-73b076342>
- GitHub: <https://github.com/AnKBORE>

---

## License

**MIT** — see [LICENSE](LICENSE).
