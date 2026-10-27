# Agentic Knowledge Workspace

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
| Weak traceability | Cited and verifiable sources |
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
- Multi-level RBAC
- Global permissions
- Per-workspace permissions

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
| **Tests** | **57 automated tests · 122 assertions** |
| **Security** | Argon2 · CSP · CSRF · audit journal |

---

## Roadmap — **after the hackathon**

> These features are **not part of the submitted MVP**: they extend the platform and build on its existing foundations.

**Advanced collaboration** — mentions · comments · notifications · contextual discussions.

**Organizational steering** — decision dashboards · performance indicators · project tracking.

**Advanced AI** — document recommendations · stale-knowledge detection · domain-specific assistants.

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
.\serve.bat
```

Then open <http://127.0.0.1:8080/presentation?lang=en> (public presentation) or <http://127.0.0.1:8080/login>.

In production the document root must point to `public/`.

---

## Web UI

The workspace is split into **five navigable pages**, driven by the vertical menu:

| Page | URL | What it does |
|---|---|---|
| **Question** | `/` | Ask a question, get a grounded answer with cited sources |
| **Agentic task** | `/tache` | Ask for a note / report / brief; the draft opens in a modal |
| **Documents** | `/documents` | List of indexed documents (version history, summary, download, delete) |
| **Upload** | `/ajouter` | Index a text, PDF, image or audio file |
| **Audit journal** | `/journal` | Append-only traceability of every action (DataTables, CSV export) |

Every result opens in a **modal** with **PDF** and **DOCX** download buttons.

API endpoints (session-protected):

- `POST /api/upload.php` — index a file (multipart)
- `POST /api/ask.php` — `{"question": "..."}` → answer + sources; `{"instruction": "...", "mode": "task"}` → draft + sources
- `POST /api/summarize.php` — `{"document_id": 3}` → summary + sources
- `POST /api/export.php` — `{"title", "content", "format": "pdf|docx"}` → file
- `GET /api/documents.php` — recent documents (JSON)
- `GET /api/download_document.php` — download a document version (logged)
- `GET /api/journal_export.php` — audit journal as CSV

---

## Tests

```powershell
php vendor/phpunit/phpunit/phpunit
```

**57 tests, 122 assertions** — covering authentication, RBAC, document versions, audit, RAG, exports and the multimodal services. No database server or API required (SQLite in memory + deterministic fakes).

---

## Security

Prepared SQL statements · CSRF protection on every POST · password hashing (Argon2/bcrypt) · progressive brute-force lockout · RBAC resolved centrally (`PermissionService`) · deny-by-default · files stored outside the web root · no secrets in code or logs · audit journal (append-only) · security headers (CSP).

---

## Documentation

- [docs/GUIDE_DE_REALISATION.md](docs/GUIDE_DE_REALISATION.md) — execution roadmap (phases, checklists, milestones)
- [docs/REGISTRE_PROBLEMES.md](docs/REGISTRE_PROBLEMES.md) — issue register & resolution log
- [docs/SECURITE_DONNEES_API.md](docs/SECURITE_DONNEES_API.md) — API data security and privacy
- [docs/DEMO_VIDEO_ET_SOUMISSION.md](docs/DEMO_VIDEO_ET_SOUMISSION.md) — demo video script & submission checklist

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
