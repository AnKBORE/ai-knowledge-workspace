# Demo state — frozen on 2 November 2026

🇫🇷 Version française : [ETAT_DE_DEMO.fr.md](ETAT_DE_DEMO.fr.md)

> **Purpose.** This file describes the exact state of the database the video and
> the jury rely on. If that state changes, this file must be updated in the same
> commit — otherwise the demonstration no longer matches what is described.
>
> Every figure below was read from the live database on 2 November 2026, not from
> memory.

---

## 1. Accounts — 3 active, 2 protected

| # | Address | Name | Global role | Protected | Memberships |
|---|---|---|---|---|---|
| 1 | `anatolekbore@gmail.com` | Anatole KABORE | administrateur_technique | **★ yes** | Général (responsable) · Espace demo (responsable) · Projet 001 (responsable) |
| 6 | `admin@super.bf` | Admin Super | administrateur_technique | **★ yes** | Général (responsable) |
| 4 | `membre.demo@local` | Membre Demo | membre | no | Espace demo (**lecteur**) · Projet 001 (**contributeur**) |

**Passwords are deliberately not written in this repository.** It is public. The
protected account's password can be regenerated at any time:

```powershell
php tools/seed_admin.php --reset
```

The command prints the new password once and stores only an Argon2id hash.

> **Why two protected accounts?** It demonstrates the model: protection is a set,
> not a single seat. Either account can recover the installation.

---

## 2. Workspaces — 3

| # | Name | Visibility | Status | Active documents |
|---|---|---|---|---|
| 1 | **Général** | restreint | actif | **9** |
| 3 | **Espace demo (cloisonnement)** | restreint | actif | 0 |
| 5 | **Projet 001** | restreint | actif | 0 |

Spaces 3 and 5 are empty **on purpose**: they prove that a member attached to
another workspace sees nothing from « Général ».

---

## 3. Memberships — 6

| Account | Workspace | Local role | What it grants |
|---|---|---|---|
| anatole | Général | responsable | read, deposit, task, delete, moderate |
| anatole | Espace demo | responsable | same, on an empty workspace |
| anatole | Projet 001 | responsable | same, on an empty workspace |
| admin@super.bf | Général | responsable | read, deposit, task, delete, moderate |
| membre.demo | Espace demo | **lecteur** | read only |
| membre.demo | Projet 001 | **contributeur** | read, deposit, task |

---

## 4. Documents — 9 active

| # | Title | Category | Confidentiality | Workspace | Type |
|---|---|---|---|---|---|
| 13 | Formation | Formation | restreint | Général | PDF |
| 17 | Audio | Chants | restreint | Général | audio |
| 18 | Scan | Rapports | restreint | Général | image (OCR) |
| 19 | chant1 | Chants | restreint | Général | scanned PDF |
| 26 | Meeting2 | Rapports | restreint | Général | audio (transcribed) |
| 27 | Meeting3 | Rapports | restreint | Général | audio (transcribed) |
| 28 | Meeting4 | Rapports | restreint | Général | audio (transcribed) |
| 29 | Meeting5 | Rapports | restreint | Général | audio (transcribed) |
| 30 | Meeting6 | Formation | restreint | Général | audio (transcribed) **+ 1 correction** |

Volumes: **34 versions**, **2 732 indexed chunks**, **1 correction** (document 30).

### Not part of this state

- **26 documents are soft-deleted** (`supprime_le` filled) — leftovers from the
  audits. They are invisible everywhere (list, search, retrieval, download) but
  still present in the table. Harmless; purge with the `DELETE` below if a clean
  database is wanted.
- **11 ingestion jobs**, all `termine`. No job is pending.

---

## 5. Categories — 5

`Rapports` · `Procédures` · `Formation` · `Administratif` · `Chants`

---

## 6. What each profile sees — expected results

These are the values to check on screen before recording. Any difference means
the state drifted.

| Check | anatole | admin@super.bf | membre.demo |
|---|---|---|---|
| `/documents` — rows | **9** | **9** | **0** |
| Workspace filter « Général » | 9 | 9 | 0 |
| Workspace filter « Projet 001 » | 0 | 0 | 0 |
| `/journal` | **200** | **200** | **403** |
| `/administration` | **200** | **200** | **403** |
| `/categories` | 200 | 200 | 200 *(contributeur)* |
| `/espaces` — workspaces listed | 3 | 1 | 2 |
| API `/api/documents.php` | 9 | 9 | `[]` |
| Download of a « Général » document | 200 | 200 | **404** |
| Menu entries visible | 10 | 10 | 6 |

Menu of a plain member: Question · Traduction · Documents indexés · Mon profil ·
Espaces — the administrative entries are hidden **and** refused.

---

## 7. Demonstration scenarios — 8 gestures

Each line states what to do and **what must appear**, so a failure is visible
immediately rather than during the editing.

| # | Gesture | Where | Expected result |
|---|---|---|---|
| 1 | Ask a question about the reports | `/` | Answer + numbered citations pointing to « Général » documents |
| 2 | Ask for a synthesis task | `/tache` | Structured document, sources listed, export buttons active |
| 3 | Translate a French extract into Mooré | `/traduire` | Translation + panel showing the extracts used |
| 4 | Show the document list | `/documents` | 9 rows, workspace badges, category colours |
| 5 | Filter by « Espace demo » | `/documents` | **0 rows** and the empty state — proves scoping |
| 6 | Open the lock modal on « Meeting6 » | `/documents` | Modal with workspace + confidentiality, password required |
| 7 | Open the administration tabs | `/administration` | 3 tabs with their counts: 3 accounts, 3 workspaces, 6 memberships |
| 8 | Connect as `membre.demo` | `/login` | Menu of 6 entries, `/documents` empty, `/journal` answers 403 |

Optional, if time allows: delete a document (works), or grant/revoke protection
from the crown icon (journaled).

---

## 8. Pre-flight checklist

```powershell
# 1. The server answers
curl.exe -s -o NUL -w "%{http_code}" http://127.0.0.1:8080/login      # 200

# 2. The state matches this document
C:\wamp64\bin\mysql\mysql8.4.13\bin\mysql.exe -u root -D ai_knowledge_workspace ^
  -e "SELECT COUNT(*) FROM documents WHERE supprime_le IS NULL;"       # 9

# 3. The protection is in place
php tools/seed_admin.php                                                # 2 protected accounts

# 4. The suite is green
php vendor/phpunit/phpunit/phpunit                                      # 121 tests
```

Then, in the browser: **`Ctrl + F5`** on every page used in the recording — the
application sends `no-store`, but a stale tab is the most frequent cause of a
gesture that "does not work".

---

## 9. Restoring this exact state

If a demo gesture changes the state, here is how to come back.

```sql
-- The 9 documents belong to « Général »
UPDATE documents SET supprime_le = NULL WHERE id IN (13,17,18,19,26,27,28,29,30);

-- Every document is filed in Général only
DELETE FROM document_espace WHERE document_id IN (13,17,18,19,26,27,28,29,30);
INSERT INTO document_espace (document_id, espace_id)
SELECT id, 1 FROM documents WHERE supprime_le IS NULL;

-- Confidentiality back to the default
UPDATE documents SET confidentialite = 'restreint' WHERE supprime_le IS NULL;

-- Workspaces open
UPDATE espace SET statut = 'actif';
```

```powershell
# Both protected accounts
php tools/seed_admin.php --grant=anatolekbore@gmail.com
php tools/seed_admin.php --grant=admin@super.bf
```

---

## 10. What this state deliberately does not contain

| Absent | Why |
|---|---|
| Public-internal workspace | the visibility rule is demonstrated by the **private** case, which is stronger |
| Expired membership | would complicate the explanation without adding proof |
| Deleted document in the list | the list must stay stable during the recording |
| Personal credentials in git | the repository is public |

---

*State frozen on 2 November 2026 — verify against §6 before every recording.*
