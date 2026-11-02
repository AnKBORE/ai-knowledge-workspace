# Acceptance test — from scratch, 2 November 2026

🇫🇷 Version française : [RECETTE_AZ.fr.md](RECETTE_AZ.fr.md)

> **Purpose.** Verify that the system really executes end to end on a **fresh
> database**, not on a state prepared by hand. Each step states what to do and
> what must appear. A step that does not produce the expected result is a
> finding, not a nuisance: write it in the register.
>
> The database was wiped on 2 November: 25 migrations applied from zero, one
> bootstrap account, one workspace, no document. Everything below builds the
> state back.

---

## 0. Starting point

| Element | Expected value |
|---|---|
| Migrations | **25** |
| Accounts | **1** — `anatolekbore@gmail.com` (technical, protected) |
| Workspaces | **1** — « Général » (restricted) |
| Documents | **0** |
| Journal | **1** entry (the bootstrap) |

Check:

```powershell
C:\wamp64\bin\mysql\mysql8.4.13\bin\mysql.exe -u root -D ai_knowledge_workspace ^
  -e "SELECT (SELECT COUNT(*) FROM migrations) m, (SELECT COUNT(*) FROM utilisateur) u, (SELECT COUNT(*) FROM documents) d;"
```

---

## 1. Installation and sign-in

| # | Do | Expect |
|---|---|---|
| 1.1 | `php tools/migrate.php` | « Migrated 0 migration(s) » — everything is already applied |
| 1.2 | `php tools/seed_admin.php` | « Protected account confirmed », 1 protected |
| 1.3 | Open `/login`, sign in | Redirect to `/`, topbar shows the account and « Général » as active workspace |
| 1.4 | Open the ten pages of the menu | All answer 200, no blank page, no console error |

---

## 2. Governance — create the test actors

| # | Do | Expect |
|---|---|---|
| 2.1 | `/administration` → **Create a user** | Account created, listed in the *Accounts* tab, count +1 |
| 2.2 | **Create a collaborative workspace** « Chantier test » | Space listed, status `actif`, you are its manager |
| 2.3 | **Add a member**: the new account → « Chantier test », role `contributeur` | Membership listed, count +1 |
| 2.4 | Sign out, sign in as the new account | Menu has fewer entries, no *Administration*, no *Audit journal* |

> A plain `lecteur` must get **403** on `/journal` and `/administration`, and a
> **403 page** (not a crash) on `/categories` if they cannot deposit.

---

## 3. Deposit — the three natures

| # | Do | Expect |
|---|---|---|
| 3.1 | Upload a **text** file, choose two workspaces | Document appears, **two** workspace badges |
| 3.2 | Upload a **scanned PDF** | OCR runs, document indexed, status `publie` |
| 3.3 | Upload **audio** (short recording) | Transcript produced, chunks indexed, translations available |
| 3.4 | Check the documents list | 3 rows, categories, workspace badges, no broken column |

---

## 4. Retrieval and generation

| # | Do | Expect |
|---|---|---|
| 4.1 | Ask a question about one of the documents | Answer built from it, **numbered sources** under the answer |
| 4.2 | Ask a question naming a document you cannot read (member session) | Explicit refusal, no invented answer |
| 4.3 | Agentic task on two documents | Structured result, sources, *Clean* and **DOCX** work |
| 4.4 | Translate an extract into Mooré | Translation, extracts panel, entry in the journal |

---

## 5. Human correction — the learning loop

| # | Do | Expect |
|---|---|---|
| 5.1 | Correct a transcript (or upload a corrected file) | Correction saved, visible in the correction list |
| 5.2 | Re-ask the same question | The corrected version is preferred, and the source text is still stored |
| 5.3 | `php tools/prove_provenance.php <id>` | Chain intact, correction **linked to its version** |

---

## 6. Scoping — what a member really sees

| # | Check | Expected |
|---|---|---|
| 6.1 | Member `/documents` | Only the documents of their workspaces |
| 6.2 | Member `/api/documents.php` | Same set, never the others |
| 6.3 | Member download of a foreign document id | **404**, not a file |
| 6.4 | Member `/journal` and `/administration` | **403** with a clear message |

---

## 7. Workspace lifecycle

| # | Do | Expect |
|---|---|---|
| 7.1 | Close « Chantier test » | Status `archive`, no new deposit accepted (422) |
| 7.2 | Open a document of that space | Still readable — closing does not erase |
| 7.3 | Reopen the workspace | Deposits accepted again |
| 7.4 | End a membership | Access cut **immediately** (member gets 403 at once) |

---

## 8. Protection of accounts

| # | Do | Expect |
|---|---|---|
| 8.1 | Crown on another account | Becomes protected, listed with ★ |
| 8.2 | Try to delete or suspend it | **403**, refusal journaled |
| 8.3 | Remove both protections | Warning banner « No account is protected » |
| 8.4 | `php tools/seed_admin.php` | At least one protection restored |

---

## 9. Evidence to collect

```powershell
php vendor/phpunit/phpunit/phpunit            # 121 tests, 281 assertions
php tools/smoke_admin_endpoints.php           # 27 checks
php tools/smoke_endpoints_anonymous.php       # 31 endpoints x 2 methods
php tools/lint_docs.php                       # documentation
php tools/prove_provenance.php <id>           # provenance chain
```

---

## 10. What to do with the findings

| Outcome | Action |
|---|---|
| Step behaves as expected | Tick it, keep the state |
| Step fails | **Write it in `docs/REGISTRE_PROBLEMES.md`** with the expected and the observed |
| Step is ambiguous | Rewrite the step — an ambiguous acceptance test is a test that cannot fail |

---

*Run on a fresh database on 2 November 2026. The demo state document
(`ETAT_DE_DEMO.md`) must be rewritten once this run produces a stable state.*
