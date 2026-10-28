# Agentic Knowledge Workspace™

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
| Weak traceability | Numbered citations bound to exact retrieved extracts |
| Top-K only context | Neighbour + first-chunk expansion, catalogue intent |
| Single-language output | Question-language answers, translated cited sources |
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

### Audio transcription — dual pipeline
- **Provider ASR (Baseten)** — designed for a dedicated speech-to-text endpoint (`/v1/audio/transcriptions`, Whisper-style multipart `file` + `model`), enabled via `META_TRANSCRIPTION_MODEL`/`META_TRANSCRIPTION_ENDPOINT`
- **Local whisper.cpp** — `whisper-cli.exe` + a GGML model runs entirely offline: verbatim lyrics/speech, no rate limit, **zero credit consumption**
- Long audio is auto-split into provider-sized segments (`AudioSplitter`, frame-aligned MP3 / header-correct WAV), each segment transcribed and concatenated with `[Segment n/N]` markers
- *Why local during development:* the hackathon free-tier Baseten credits are nearly exhausted, so `META_WHISPER_BIN`/`META_WHISPER_MODEL` route transcription locally while the API pipeline remains wired and production-ready — switching back only takes an env variable
- *Fallback:* when no ASR is configured, the multimodal chat model describes the audio (its copyright guardrails can refuse verbatim lyrics)

### Precision retrieval engine (RAG++)
- **Permission-aware retrieval** — every scored chunk belongs to a document the actor is allowed to read (tenant, workspace membership, confidentiality, ownership, soft-delete), resolved once per query
- **Hybrid scoring** — `0.6 × semantic cosine + 0.4 × lexical overlap` keeps recall strong even with local hashing embeddings
- **Word-boundary chunking** — fixed-size splits backtrack to the last space, so excerpts never end mid-word
- **Context expansion** — each hit is enriched with its ±1 neighbour chunks **and the document's first chunk**, where titles and headings live; a question about a title/heading is therefore always answerable
- **Catalogue intent detection** — "list all documents" bypasses chunk retrieval and feeds the real, permission-scoped document inventory (title · category · indexed date · file) to the model
- **Verifiable citations** — the model cites `[n]`; only the **cited** extracts are shown as sources, with their exact context number (`Sources: [3]` ⇢ entry *3*). No citation, no source list
- **Full multilingual pipeline** — the answer follows the **question's language**; literal quotes are translated with the original kept in parentheses; cited **source excerpts are translated** too via a dedicated low-temperature pass
- **Direct download** — every cited source carries a `document_id` download link to its latest version

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
| **Tests** | **68 automated tests · 143 assertions** |
| **UI** | Bilingual EN/FR (EN default), Bootstrap + DataTables + Select2 |
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

The workspace is split into **seven navigable pages**, driven by the vertical menu — all bilingual EN/FR (EN default, per-session switch):

| Page | URL | What it does |
|---|---|---|
| **Question** | `/` | Ask a question, get a grounded answer with numbered cited sources (downloadable) |
| **Agentic task** | `/tache` | Ask for a note / report / brief; the draft opens in a modal |
| **Documents** | `/documents` | List of indexed documents (version history, summary, download, password-gated delete) |
| **Upload** | `/ajouter` | Index a text, PDF, image or audio file (category required) |
| **Categories** | `/categories` | Create and manage document categories |
| **Audit journal** | `/journal` | Traceability of every action (DataTables, CSV export, password-gated period purge ≥10 days) |
| **My profile** | `/mon-profil` | Identity, memberships; password-confirmed profile & password changes |

Every result opens in a **modal** with **PDF** and **DOCX** download buttons.

API endpoints (session-protected):

- `POST /api/upload.php` — index a file (multipart)
- `POST /api/ask.php` — `{"question": "..."}` → answer + sources; `{"instruction": "...", "mode": "task"}` → draft + sources
- `POST /api/summarize.php` — `{"document_id": 3}` → summary + sources
- `POST /api/export.php` — `{"title", "content", "format": "pdf|docx"}` → file
- `GET /api/documents.php` — recent documents (JSON)
- `GET /api/download_document.php` — download a document version (logged)
- `GET /api/journal_export.php` — audit journal as CSV
- `POST /api/journal_purge.php` — password-confirmed purge of journal entries older than 10 days (the purge itself is logged)

---

## Tests

```powershell
php vendor/phpunit/phpunit/phpunit
```

**68 tests, 143 assertions** — covering authentication, RBAC, document versions, audit, permission-scoped RAG retrieval, exports and the multimodal services. No database server or API required (SQLite in memory + deterministic fakes).

---

## Security

Prepared SQL statements · CSRF protection on every POST · password hashing (Argon2/bcrypt) · progressive brute-force lockout · RBAC resolved centrally (`PermissionService`) · deny-by-default · files stored outside the web root · no secrets in code or logs · audit journal (append-only) · security headers (CSP).

---

## Documentation

- [docs/GUIDE_DE_REALISATION.md](docs/GUIDE_DE_REALISATION.md) — execution roadmap (phases, checklists, milestones)
- [docs/REGISTRE_PROBLEMES.md](docs/REGISTRE_PROBLEMES.md) — issue register & resolution log
- [docs/MARQUE_ET_PROPRIETE.md](docs/MARQUE_ET_PROPRIETE.md) — marque et propriété intellectuelle
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
