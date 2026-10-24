# AI Knowledge Workspace

An AI-powered institutional knowledge workspace that turns an organization's scattered documents into a private, searchable knowledge base — and a conversational assistant that answers questions, generates summaries, and drafts reports.

Built for the **Meta Global AI Developer Hackathon** (Theme: *Build for Your World* · Track: *Indie Developer*).

## Overview

Organizations accumulate reports, minutes, procedures, and regulations for years. The information is scattered, and after a while nobody can find it. **AI Knowledge Workspace** lets a team:

1. Upload documents (PDF, reports, minutes).
2. Have the AI index them into a private knowledge base.
3. Ask questions in natural language.
4. Get answers, summaries, and generated reports directly from the organization's own documents.

## Features (MVP)

- Secure authentication and role-based access control (RBAC)
- Document management with versioning
- PDF upload and indexing
- Knowledge base
- AI chat connected to the documents (natural-language Q&A)
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
| SAM 3.1 | Computer vision, scanned-document processing |
| Muse Code | Coding agent |
| Open-weight models | Self-hosted / partner-hosted (Baseten, Modal, Fireworks) |

Phase 2: vector search and Retrieval-Augmented Generation (RAG).

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
services/     business logic (+ ai/ for Meta API integration)
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
php tools/migrate.php fresh --seed

# 4. Run locally
php -S 127.0.0.1:8080 -t public
```

In production the document root must point to `public/`.

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

## Security

Prepared SQL statements · CSRF protection · password hashing · RBAC checked on every target object · deny-by-default · files outside the web root · no secrets in code or logs · HTTPS and security headers · sensitive operations logged.

## Author

**Anatole KABORE** — Independent Software Engineer and Cybersecurity Specialist, Burkina Faso.

- LinkedIn: <https://www.linkedin.com/in/anatole-kabore-73b076342>

## License

Proprietary — demonstration project for the Meta Global AI Developer Hackathon.
