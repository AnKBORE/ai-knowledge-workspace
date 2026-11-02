# Recording of a real run — 2 November 2026

The organizers asked for provenance in the repository, since they cannot run the
product on the author's machine. This folder holds **captured outputs of real
runs**, not hand-written examples.

| File | What it is |
|---|---|
| `model_calls-2026-11-02.log` | raw excerpt of `storage/logs/model_calls.log` after a real call |

## What the log proves

```
2026-11-02 16:12:39 | POST inference.baseten.co/v1/chat/completions | \
model=meta-models/muse-spark-open-1026 | 200 | 1573 ms | in=180 o=864
```

- the call left the application at **16:12:39** and reached
  `inference.baseten.co`, the partner endpoint that serves the Meta open-weight
  model `muse-spark-open-1026`;
- the provider answered **200** in **1 573 ms**;
- the request carried 180 bytes and the response 864.

No prompt and no answer content is written: the line proves the call without
exposing what was asked.

**What pays for it.** The author received **100 USD of Baseten credits** for
the event, and every call runs on that key against a Meta open-weight model.
No Meta Model API credits were claimed, and none were needed.

## How to reproduce it

```powershell
Get-Content "C:\wamp64\www\projet hackaton\ai-knowledge-workspace\storage\logs\model_calls.log" -Wait -Tail 5
```

Then ask a question in the application; a new line appears within a second.

---

*Generated during the session of 2 November 2026. Extend this folder after the
acceptance run with a captured answer, an agent trace and an exported document.*

---

## Captures to collect during the acceptance run

One capture per proof, named as below, saved in this folder. **Full window, URL
visible** — a cropped image proves nothing. The check that matters is the database
one; these captures exist for the jury, which cannot run the machine.

| File | What to capture | What it proves |
|---|---|---|
| `01-audio-recorded.png` | the recorder with the timer running | the voice is captured in the browser, not uploaded from a file |
| `02-audio-indexed.png` | the same document, status **indexed** | the pipeline ran: transcription, chunks, versions |
| `03-answer-with-sources.png` ✅ | a question about that recording, with numbered sources | the answer comes from the new document, and it cites it |
| `03b-answer-cleaned.png` ✅ | the same answer after the clean step | the export strips the scaffolding and keeps the sentence |
| `04-agentic-trace.png` | the task page during or after the run: plan, retrieve, draft, verify | the agentic loop is real, not a description |
| `05-docx-export.png` | the clean result and the export button state | the document leaves the app with its citations |
| `06-workspaces.png` | the documents list with badges, then the filter on an empty workspace | access is computed per document |
| `07-administration.png` ✅ | the three tabs with their counts | governance is exposed and countable |
| `08-member-403.png` | the member session: 0 documents and the journal answering 403 | the interface and the server agree |

**Never capture a terminal showing the API key.** The model call log is safe — it
contains no key — and it can be pasted as text rather than as an image.
