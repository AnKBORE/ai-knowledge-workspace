# Demo state — built by the acceptance run of 2 November 2026

🇫🇷 Version française : [ETAT_DE_DEMO.fr.md](ETAT_DE_DEMO.fr.md)

> **Purpose.** This file describes the exact state the video and the jury rely on.
> It was **not** written by hand: it is the state the acceptance run produced, then
> read back from the database. If the state changes, this file changes in the same
> commit — otherwise the demonstration no longer matches what is described.
>
> Every figure below was queried on 2 November 2026, after the run.

---

## 1. Accounts — 2, one protected

| # | Address | Global role | Protected | Memberships |
|---|---|---|---|---|
| 1 | `anatolekbore@gmail.com` | administrateur_technique | **★ yes** | Général (responsable) · Chantier test (responsable) |
| 2 | `membre.recette@example.org` | membre | no | Chantier test (**contributeur**) |

**Passwords are not written in this repository** — it is public. The protected
account's password is regenerated with `php tools/seed_admin.php --reset`.

The second account exists to play the member in the video: it is the one that
shows what a non-administrator actually sees.

---

## 2. Workspaces — 2

| # | Name | Visibility | Status | Documents |
|---|---|---|---|---|
| 1 | **Général** | restreint | actif | **4** |
| 2 | **Chantier test** | restreint | actif | **2** |

The two workspaces hold **different** document sets on purpose: that is what makes
the scoping visible on screen.

---

## 3. Documents — 4, one per nature

| # | Title | Type | Language | Workspaces | Chunks | What it proves |
|---|---|---|---|---|---|---|
| 1 | Note chantier Koudougou | text | fr | Général + Chantier test | 1 | multi-workspace filing |
| 2 | Rapport scanné Koudougou | PDF | fr | Général + Chantier test | 4 | scanned PDF with a text layer |
| 3 | **Doc mooré scanné** | PDF | **mo** | Général | 2 | OCR of a mooré scan, 1 093 characters |
| 4 | **Meeting Validation** | audio (wav) | **en** | Général | 1 | **the voice becomes a source**, indexed in 9 s |

Document 4's transcript:

> *Welcome to this meeting. The budget approved by the donor for the school
> construction is 10 million CFA francs.*
> `[Traduction — French] Bienvenue à cette réunion. Le budget approuvé par le
> bailleur pour la construction de l'école est de 10 millions de francs CFA.`

---

## 4. What each profile sees — verified

| Check | anatole | membre.recette |
|---|---|---|
| `/documents` — visible documents | **4** | **2** *(docs 1 and 2)* |
| `/journal` | **200** | **403** |
| `/administration` | **200** | **403** |
| Deposit in Chantier test | yes | yes *(contributeur)* |
| Delete a document | yes | **no** |
| Question about the school budget | **answers** with the recording | **refuses** — the recording is not in their workspace |

The member's two documents are exactly the two filed in Chantier test. Nothing
from Général leaks, and the refusal is explicit rather than a wrong answer.

### 4.1 Routes taking a document id — measured in a member session

| Route, on a document filed only in Général | anatole | membre.recette |
|---|---|---|
| `reformulate.php` | 200 | **403** |
| `translate_document.php` | 200 | **403** |
| `correction_list.php` | 200 | **403** |
| `correction_download.php` | 200 | **403** |
| `summarize.php` | a summary | *"not accessible"* |
| agentic task requesting it | accepted | **refused**, 0 sources |

The rule is pinned by `testCanConsultDocument*` in `PermissionServiceDocumentsVisiblesTest`.

### 4.2 Same question, two workspaces — the exact values

| Question | Général | Chantier test |
|---|---|---|
| "what is the budget approved for the school construction?" | **10 million CFA francs**, source: the recording | **refusal** |
| "list all documents" (catalogue) | **4** documents | **2** documents |
| agentic task on budgets | cites documents **4 and 1** | cites documents **1 and 2** |

**These are the counts before the take.** Beat 2 records a new audio document into
Général, so the documents page then shows **5**, and the catalogue question answers
**5** where it answered 4. That change is the proof the recording created knowledge —
not a drift, and not something to correct before filming.

These are the numbers to reproduce on camera. If one of them differs, the
state changed: re-read this file before recording.

---

## 5. Demonstration script — the ten beats and what must appear

| # | Gesture | Expected |
|---|---|---|
| 1 | Landing page | the product, no slide |
| 2 | **Record 3 s, deposit** | status moves to *indexed* in **≈9 s** |
| 3 | Ask in English about the recording | **10 million CFA francs**, source = the audio |
| 4 | Ask about the mooré document | the answer comes from the mooré scan |
| 5 | Agentic task on two documents | plan · retrieve · draft · verify, then a structured result |
| 6 | Clean, then export | the answer without scaffolding |
| 7 | Summarise one document | a summary with its source |
| 8 | Switch workspace, ask the same question | **honest refusal**: *"I cannot find this information in the current workspace"* |
| 9 | Member session | 2 documents, `/journal` in 403 |
| 10 | Model call log | endpoint, model, status, duration — live |

---

## 6. Pre-flight, before recording

```powershell
# 1. The state matches this document
C:\wamp64\bin\mysql\mysql8.4.13\bin\mysql.exe -u root -D ai_knowledge_workspace ^
  -e "SELECT COUNT(*) FROM documents WHERE supprime_le IS NULL;"   # 4

# 2. The suite is green
php vendor/phpunit/phpunit/phpunit                                 # 124 tests

# 3. The chain holds
php tools/prove_provenance.php 4

# 4. The call log is readable live
Get-Content storage\logs\model_calls.log -Tail 3
```

Then **`Ctrl + F5`** on every page used in the recording. A stale tab is the most
frequent cause of a gesture that "does not work".

---

## 7. What the acceptance run proved

Nineteen defects were found by **doing the gestures**, not by reading the code. All are
fixed and recorded in `REGISTRE_PROBLEMES.md` (P-51 to P-69), including four that
would have been visible on camera:

| Defect | What it looked like |
|---|---|
| P-52 | the deposit form announced *"Général — 0 documents"* while Général held two |
| P-58 | the workspace at the top did not limit the search |
| P-59 | a confident answer about the **wrong** site, with no refusal |
| P-60 | the *Summarise* button did nothing |

That list is worth more than a clean report: it is the proof that the state below
was measured rather than imagined.

---

## 8. What this state deliberately does not contain

| Absent | Why |
|---|---|
| An empty workspace | the refusal (beat 8) proves scoping better than an empty filter |
| A public-internal workspace | the private case is the stronger demonstration |
| Soft-deleted test documents in the list | excluded from every query by design |
| Credentials | the repository is public |

---

*State produced and verified on 2 November 2026 during the acceptance run
(`RECETTE_AZ.fr.md`). Read it back against §4 before every recording.*
