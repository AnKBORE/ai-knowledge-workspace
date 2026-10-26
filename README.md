# Agentic Multimodal Knowledge Workspace

An agentic, multimodal knowledge workspace for organizations: it ingests text documents, scanned paper records and voice recordings, then carries out multi-step tasks — answering questions with cited sources, preparing meeting notes, drafting reports and flagging overdue projects.

Built for the **Meta Global AI Developer Hackathon** (Theme: *Build for Your World* · Track: *Indie Developer*).

## Overview

Organizations accumulate reports, minutes, procedures, scanned records and meeting recordings for years. The information is scattered, and after a while nobody can find it. **Agentic Multimodal Knowledge Workspace** lets a team:

1. Ingest text documents (PDF, reports, minutes), scanned paper records and voice recordings.
2. Have the AI turn them into a private, multimodal knowledge base.
3. Ask a question or request a task in natural language.
4. Get a grounded answer — or a ready-to-use draft — with every claim cited to its source.

## Features (MVP)

- Secure authentication and role-based access control (RBAC)
- Document management with versioning
- PDF upload and indexing
- Scanned paper records via SAM 3.1
- Voice notes and meeting transcription via Muse Voice Transcribe
- Knowledge base
- Agentic assistant: multi-step tasks with cited sources
- Automatic document summarization
- Automatic report generation
- Intelligent / semantic search
- Action logging (audit trail)

## AI (Meta Model API)

| Model | Role |
|---|---|
| Muse Spark | Conversational assistant, Q&A, content generation, document analysis |
| Muse Wonder | Report generation, planning, decision support |
| Muse Image | Project visuals and illustrations |
| Muse Voice Transcribe | Meetings and voice notes → searchable text |
| SAM 3.1 | Scanned-document processing, visual recognition |
| Muse Code | Coding agent |
| Muse Glimmer / Muse Spark Open | Open-weight Meta models (private deployment via Baseten, Modal, Fireworks) |

Retrieval-augmented generation (RAG) grounds every answer in the organization's own documents; phase 2 extends it to full vector search across all modalities.

## Tech stack

| Component | Version |
|---|---|
| PHP | 8.4.27 |
| MySQL | 8.4.13 (InnoDB, utf8mb4) |
| Web server | Apache 2.4 |
| Frontend | HTML5, CSS3, Bootstrap 5, JavaScript ES6 |
| AI | Meta Model API (REST) |

## Project structure

```
public/       front controller + assets (the only exposed folder)
config/       environment configuration and routes
core/         HTTP, routing, session, security, PDO
controllers/  MVC controllers
models/       data access
services/     business logic (+ Ai/ for Meta API integration)
validators/   input validation
views/        templates and pages
storage/      logs, files, backups, cache (never exposed)
migrations/   versioned schema
tests/        PHPUnit
```

## Installation

```powershell
# 1. Dependencies
composer install

# 2. Local configuration
Copy-Item .env.example .env

# 3. Database
php tools/migrate.php migrate

# 4. Run locally
php -S 127.0.0.1:8080 -t public
```

In production the document root must point to `public/`.

On Windows, `.\setup.ps1` (or `setup.bat`) runs these steps automatically with PHP 8.4.27.

## Quick start — AI demo

```powershell
# 1. Dependencies (creates vendor/autoload.php)
composer install

# 2. Create the schema
php tools/migrate.php migrate

# 3. Configure .env with your Meta API keys (see .env.example)

# 4. Index a document (.txt, .md, .pdf)
php tools/index_document.php "Annual report" docs/sample-report.txt

# 5. Run the local server
php -S 127.0.0.1:8080 -t public

# 6. Ask a question (in another terminal)
curl.exe -X POST http://127.0.0.1:8080/api/ask.php -H "Content-Type: application/json" -d "{\"question\":\"What are the main risks?\"}"
```

## Web UI

The workspace is a server-rendered page (Bootstrap 5 + the foundation's design system):

- `GET /` — upload documents, ask questions **with cited sources**, and run **agentic tasks** (draft a note/report).
- `POST /api/upload.php` — index a `.txt/.md/.pdf/image/audio` file.
- `POST /api/ask.php` — `{"question": "..."}` → answer + sources; `{"instruction": "...", "mode": "task"}` → draft + sources.

Start the server and open <http://127.0.0.1:8080/>.

## Documentation

- [docs/GUIDE_DE_REALISATION.md](docs/GUIDE_DE_REALISATION.md) — execution roadmap (phases, checklists, milestones).
- [docs/REGISTRE_PROBLEMES.md](docs/REGISTRE_PROBLEMES.md) — issue register & resolution log.
- [docs/DEMO_VIDEO_ET_SOUMISSION.md](docs/DEMO_VIDEO_ET_SOUMISSION.md) — demo video script & submission checklist.

## Tests

```powershell
php vendor/phpunit/phpunit/phpunit
```

15 tests cover RAG chunking, cosine similarity, the `.env` loader and the full RAG pipeline — no database server or API required.

## Security

Prepared SQL statements · CSRF protection · password hashing · RBAC checked on every target object · deny-by-default · files outside the web root · no secrets in code or logs · HTTPS and security headers · sensitive operations logged.

## Author

**Anatole KABORE** — Independent Software Engineer and Cybersecurity Specialist, Burkina Faso.

- LinkedIn: <https://www.linkedin.com/in/anatole-kabore-73b076342>

## License

Proprietary — demonstration project for the Meta Global AI Developer Hackathon.
