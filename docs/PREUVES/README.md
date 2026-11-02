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