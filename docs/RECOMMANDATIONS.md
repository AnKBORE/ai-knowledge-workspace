# Recommendations register

🇫🇷 Version française : [RECOMMANDATIONS.fr.md](RECOMMANDATIONS.fr.md)

> Every recommendation made during the build is written here with its **status**,
> so none is lost between two conversations and none is claimed as done without
> evidence. A recommendation leaves this register only when the work is merged.

| # | Recommendation | Status | Evidence |
|---|---|---|---|
| R-01 | Make the declared page language match the rendered one (`lang="en"` while the text was English but the tag said `fr`) | **done** | `config/app.php`, the three layouts; `tools/check_english_ui.php` passes on ten pages |
| R-02 | Fix the dead language selector on the login page (the route answered before the switch) | **done** | `public/index.php`, switch moved before the login route |
| R-03 | Stop the landing page from choosing the session language of the whole app | **done** | `/presentation` no longer writes `lang` |
| R-04 | Translate the client-side chrome (DataTables, Select2, buttons, picker, journal actions) | **done** | 47 keys, `public/assets/js/ui-i18n.js`, `tools/scan_french_ui.php` |
| R-05 | State the rights of every asset, as the organizers require | **done** | "Third-party assets and rights" in both READMEs |
| R-06 | Stop redistributing the Mooré PDFs (© SIL International 2016) | **done** | untracked, `.gitignore`, cited in the README |
| R-07 | Delete the views nothing referenced and the unused default layout | **done** | `views/ai/index.php`, `views/layouts/main.php` removed; default layout is `app` |
| R-08 | Fix the duplicated password field in the document control modal | **done** | `views/ai/documents.php` — one label, one input |
| R-09 | Make the live model calls visible, as the organizers require | **done** | `MetaClient::logModelCall()`, host included |
| R-10 | Keep captured evidence in the repository | **started** | `docs/PREUVES/` — first real call committed |
| R-11 | Record the demo in English, with the live capture as the opening | **decided** | `docs/DEMO_VIDEO_ET_SOUMISSION.md` §3 |
| R-12 | Rewrite `ETAT_DE_DEMO.md` from the state the acceptance run produces | **open** | to do after `RECETTE_AZ.fr.md` |
| R-13 | Run the acceptance test end to end on the fresh database | **open** | `RECETTE_AZ.fr.md`, steps 1 to 8 |
| R-14 | Push the local commits | **open** | `git status` — 14 ahead |
| R-15 | **Rotate the Baseten API key** once the event is over | **open** | the key appeared in this conversation |
| R-16 | Set a spending limit on the Baseten account | **open** | the calls are billed to the $100 credits granted to the author |
| R-17 | Decide the fate of the two protected accounts | **decided** | two are kept on purpose: the model is a set, not a seat |
| R-18 | Do not rewrite the Git history to erase the PDFs | **decided** | it would destroy the dated evidence the jury is asked to check |
| R-19 | Functional tests for the 16 endpoints covered only by the anonymous sweep | **open** | optional, after submission |
| R-20 | Remove the `--transfer` alias once the multiple model is explained everywhere | **open** | optional, cosmetic |
| R-21 | Accessibility audit (keyboard, contrasts, screen reader) | **open** | never done; assumed limitation |

---

*Maintained by hand, like the problem register. A line is only marked `done` when
a command or a file in this repository proves it.*
