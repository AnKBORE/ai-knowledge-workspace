# Data security and privacy — inference API

> **Purpose**: state precisely what the project sends (or does not send) to the inference API (Baseten / Muse Spark Open), who can see that data, what the real risks are, and which protections are in place.
>
> **Audience**: jury, auditors, security officers and any stakeholder concerned with confidentiality.

🇫🇷 French version: [SECURITE_DONNEES_API.fr.md](SECURITE_DONNEES_API.fr.md)

---

## 1. Principle

The project uses a **Meta open-weight model** (Muse Spark Open) **served by an inference partner** (Baseten). This is **not** data "theft": it is **data processing** by a **sub-processor**, framed by a contract (terms of service and a **DPA**).

> ⚠️ **Important nuance**: confidentiality is not guaranteed at 100 % — the data **leaves** the organisation's infrastructure to be **processed** at the partner. That is an **architectural choice** to own, and to **frame**.

---

## 2. Data that travels to the API

| Data | Sent? | When |
|---|---|---|
| **The user's question** | ✅ Yes | On every question |
| **Document extracts** (top-K) | ✅ Yes | On every question / task / summary |
| **Images** (scans) | ✅ Yes (base64) | On image upload (OCR) |
| **Audio** | ✅ Yes (base64) | On audio upload (transcription) |
| **System context** (prompt) | ✅ Yes | On every call |

> ✅ **Only the relevant extracts** (top-K) are sent — **never the whole document**.

---

## 3. Data that **never** travels

| Data | Status |
|---|---|
| **Passwords** (Argon2 hashes) | ❌ Never |
| **CSRF tokens / sessions** | ❌ Never |
| **SHA-256 fingerprints** (documents) | ❌ Never (computed locally) |
| **Embeddings** (indexing vectors) | ❌ Never — **computed locally** (hashing trick) |
| **Audit journal** | ❌ Never (stored in the database) |
| **Raw files** | ❌ Never (stored outside the web root) |

---

## 4. Who can see what

| Actor | Sees the data? | Detail |
|---|---|---|
| **Baseten** (inference host) | ✅ **Yes** | Runs the model; may log for abuse prevention |
| **Meta** | ❌ **No** | The model is **open-weight**, executed **at Baseten** |
| **Your server** | ✅ Yes | Source of the data |
| **Third parties** | ❌ No | No access |

> **Key point**: Meta **does not receive** the project's data — inference runs at Baseten.

---

## 5. "Theft" or "processing"?

| Term | Reality |
|---|---|
| **Theft** | ❌ No — Baseten "steals" nothing; it is a **commercial service** |
| **Processing** | ✅ Yes — your data is **processed** on its servers |
| **Logging** | ⚠️ Possible (abuse prevention) — check the policy |
| **Training on your data** | ⚠️ Check the partner's terms |

---

## 6. Real risks

| Risk | Level | Detail |
|---|---|---|
| **Confidentiality** (sensitive data) | 🟠 Real | The partner sees the text that is sent |
| **Data residency** | 🟠 To verify | Servers outside Burkina Faso (EU / US) |
| **Retention / logs** | 🟡 To verify | Retention period at the partner |
| **Network interception** | ✅ Protected | TLS 1.2+ (up-to-date CA bundle) |
| **Leak by a third party** | 🟡 Low | Depends on the partner |

---

## 7. Protection measures

| Measure | Effect | Status |
|---|---|---|
| **Anonymisation** before sending | Remove names, addresses, numbers | 🔧 Depending on the data |
| **Top-K only** | Data minimisation | ✅ Already done |
| **Local embeddings** | No call at indexing time | ✅ Already done |
| **Storage outside the web root** | Files not exposed | ✅ Already done |
| **SHA-256** | Integrity + non-repudiation | ✅ Already done |
| **Audit journal** | Traceability of access | ✅ Already done |
| **Self-hosting Muse Spark Open** | Zero data egress | 🔧 Optional (open weights) |
| **DPA** with the partner | Contractual framework | 🔧 To verify |

---

## 8. Compliance

| Point | Recommendation |
|---|---|
| **GDPR** (if personal data) | Check the **DPA**, the **legal basis**, **non-EU transfers** |
| **Sensitive data** (health, legal) | **Anonymise** or **self-host** |
| **Consent** | Inform users that AI processing occurs |
| **Processing register** | Keep a register (GDPR obligation) |

---

## 9. What is already done in the project

| Element | Status |
|---|---|
| **Local** embeddings (no API) | ✅ |
| **Top-K only** | ✅ |
| Storage **outside the web root** | ✅ |
| **SHA-256** fingerprints | ✅ |
| Detailed **audit journal** | ✅ |
| Security headers (**CSP**) | ✅ |
| Up-to-date **CA bundle** (TLS) | ✅ |
| **Argon2** passwords | ✅ |

---

## 10. Recommendations

1. **Anonymise** sensitive documents before indexing.
2. **Check the DPA** and the partner's retention policy.
3. **Consider self-hosting** Muse Spark Open (open weights) for critical data.
4. **Document** the processing (GDPR register).
5. **Inform** users ("AI processing" notice).

---

## 11. Conclusion

| Question | Answer |
|---|---|
| Is there **data theft**? | ❌ **No** (contractual service) |
| Does the data **leave**? | ✅ **Yes** (to Baseten) |
| Does Meta receive it? | ❌ **No** (open-weight model at Baseten) |
| Is it **risky**? | 🟡 **Depends on sensitivity** |
| Can it be **avoided**? | ✅ **Yes** (anonymisation, self-host, top-K) |

> **For the hackathon** (demonstration data), the risk is **negligible**. For a **real deployment** with sensitive data, apply the measures in section 7.

---

## 12. References

- Privacy policy of the inference partner (Baseten).
- **Meta Global AI Developer Hackathon** terms (§3.8 — open-weight models via partners).
- GDPR — regulation (EU) 2016/679 (if personal data).
- [GUIDE_DE_REALISATION.md](GUIDE_DE_REALISATION.md) — project architecture and security.

---

## 13. DPA checklist (to verify with the partner)

Before any deployment with sensitive data, obtain and verify:

| Point | Question to ask | Expected answer |
|---|---|---|
| **DPA** | Is a Data Processing Agreement available? | ✅ Signed |
| **Retention** | How long are requests kept? | Minimum duration (e.g. 30 days) |
| **Sub-processors** | Are there onward sub-processors? | A list is provided |
| **Location** | Where are the servers hosted? | EU / US (to be documented) |
| **Training** | Is the data used to train models? | No (by default) |
| **Deletion** | Can data deletion be requested? | Yes, on request |
| **Breach notification** | In case of a leak, how fast are we informed? | 72 h (GDPR) |

> 📌 **Action**: keep the signed DPA and attach it to the processing register.

---

## 14. Self-hosting Muse Spark Open (zero egress)

Muse Spark Open is an **open-weight** model: it can run **on your own infrastructure**, which removes any data egress.

### Option A — Dedicated GPU server (recommended for production)

1. Provision a GPU server (e.g. 1× A10G / L4).
2. Serve the model with **vLLM** or **TGI** (OpenAI-compatible endpoint).
3. In `.env`:

```dotenv
META_API_STYLE=openai
META_MODEL_API_BASE_URL=http://localhost:8000/v1
META_MODEL_NAME=meta-models/muse-spark-open
META_API_KEY=local
```

4. Verify: `curl http://localhost:8000/v1/models`.

### Option B — Workstation (development)

- Quantised model (4-bit) on a 16 GB GPU.
- **Demonstration data only** (reduced performance).

### What it changes

| Element | Before (Baseten) | After (self-host) |
|---|---|---|
| **Data egress** | ✅ Yes | ❌ **No** |
| **Cost** | Per token | **Fixed cost** (GPU) |
| **Performance** | Optimal | Depends on hardware |
| **Compliance** | Depends on the partner | **Complete** |

> ⚠️ Self-hosting requires **GPU skills** and a **hardware budget** — reserve it for deployments with critical data.

---

## 15. The project's anonymisation configuration

The project includes an **anonymiser** (e-mails, phone numbers, IBAN, bank cards) applied **before indexing** and **before any API call**.

```dotenv
# .env
ANONYMISATION=1
```

| Action | Effect |
|---|---|
| `ANONYMISATION=0` (default) | Text indexed as-is |
| `ANONYMISATION=1` | Direct identifiers are replaced by `[EMAIL]`, `[TELEPHONE]`, `[IBAN]`, `[CARTE]` |

> ✅ Implemented in `services/Anonymiseur.php`, covered by `tests/AnonymiseurTest.php`.

---

## 16. Legal purge of the audit journal

The journal is **append-only by design**. There is **one** framed exception: the **legal purge** (right to erasure, GDPR), reserved for the **technical administrator**.

| Control | Detail |
|---|---|
| **Permission** | `journal.lire` (technical administrator only) |
| **Method** | **POST** + mandatory **CSRF** |
| **Re-authentication** | **Account password** required for every purge |
| **Window** | Only entries **older than 10 days** can be purged |
| **Traceability** | The purge is **itself journaled** (`journal.purge`, with the number of entries) |
| **Refusals** | A wrong password is **journaled** (`journal.purge`, result `refus`) |

> ✅ **Nothing escapes the journal**: even the purge **leaves a trace**. Purged entries are **old** (> 10 days) and the purge is **restricted, re-authenticated and audited**.

---

## 17. Correction and rephrasing endpoints

These endpoints handle human-written content: they are therefore treated with the same controls as documents.

| Endpoint | Method | Controls |
|---|---|---|
| `correction_save.php` | POST multipart | Auth · **CSRF** · `document.deposer` · rate limit 30/5 min · 24 MB · extensions `.pdf/.docx/.txt/.md/.csv` · document not deleted |
| `correction_list.php` | GET | Auth · `document.consulter` · mandatory `document_id` |
| `correction_delete.php` | POST | Auth · **CSRF** · correction author **or** `document.supprimer` |
| `correction_download.php` | GET | Auth · `document.consulter` · `basename()` + name sanitising · `no-store` · journaled |
| `reformulate.php` | POST | Auth · **CSRF** · rate limit 30/5 min · accepts audio (24 MB), text or a `document_id` |

### What is journaled

| Action | Detail recorded |
|---|---|
| `document.correction` | language, character count |
| `document.correction.suppression` | identifier, language |
| `document.correction.telechargement` | identifier, language, source (file or text) |
| `reformulation` | source (audio / text), languages, length |

### What never leaves the server

The **corrected file** stays in `storage/files/corrections/`. Only its **text**
is sent to the API, like any indexed document.

### Reading corrections

Corrections uploaded by a native speaker are **validated by nature**: there is no
approval workflow. Control is **a posteriori**: any rights-holder can list and
delete an erroneous correction, and the deletion immediately stops feeding the
prompts.

---

## 18. Journaled actions (state as of 1 November 2026)

| Family | Actions |
|---|---|
| **Authentication** | `connexion` (success **and** refusal, lockout included), `deconnexion` |
| **Documents** | `document.depot`, `document.consultation`, `document.suppression`, `document.export`, `document.resume` |
| **Corrections** | `document.correction`, `document.correction.consultation`, `document.correction.telechargement`, `document.correction.suppression` |
| **AI usage** | `assistant.question`, `traduction`, `reformulation` |
| **Administration** | `categorie.creation/modification/suppression`, `profil.maj`, `profil.mot_de_passe`, `journal.export`, `journal.purge` |
| **Refusals** | `securite.refus` — stale CSRF token (419) or missing permission (403), with the requested path |

### What is deliberately **not** traced

A request rejected with 401 because it is **unauthenticated** is not recorded:
the journal being append-only, a bot could flood it and bury the real events.
Only refusals involving a **real session** are traced.

### Safeguards of the legal purge

`journal.purge` requires: technical administrator, POST + CSRF, **password
re-entered**, window **≤ 92 days**, period end **≤ D-10**, and **5 attempts per
5 minutes**. The purge is itself journaled, refusals included.
