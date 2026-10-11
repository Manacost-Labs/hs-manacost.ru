# Article AI tools

The MU-plugin `hs-editor-ai.php` adds three native media-toolbar buttons to the
Classic Editor, within the existing `hs_ew_is_enabled()` rollout and `edit_post`
permission. It adds no public frontend assets and makes no model calls during
editor initialization, normal save, autosave or rendering.

- **Заполнить карты** requires a selection. The article title and entire body are contextual
  input. Only `hs_card` / `hs_bg` identities confirmed by the installed tooltip
  dictionaries are accepted. Original words must be exactly recoverable after
  removing the inserted wrappers. Existing shortcodes are immutable.
- **Редактура EditorTeam** processes the whole article using the pinned style
  and voice profiles. A separate Opus request checks preservation of meaning.
  This is a profile-based editorial workflow, not the complete ManacostTeam
  Go/Python analysis engine and not a fact-check against the current game meta.
- **Пунктуация и опечатки** processes a selection, or the whole article when
  nothing is selected. Word order/count and numbers are preserved. Each changed
  word allows only one letter operation or an adjacent-letter transposition.

Opening the dialog does not send text. The explicit **Отправить текст** action
sends selected text plus unpublished article context to Anthropic. The dialog
shows a text preview before sending and highlighted changes in both versions.
HTML markup is available through an explicit toggle; previews insert only text
nodes, never article/model HTML. The fixed footer keeps actions reachable on a
phone. Closing during processing allows writing to continue; reopening keeps
the pending result. **Применить изменения** is the only mutation and is
rejected if the article/editor mode changed meanwhile. TinyMCE undo restores
the prior text. Saving/publishing remains the normal WordPress action.

## Runtime configuration

Use the official Anthropic API, model `claude-opus-5-5`. Prefer a protected file
`/etc/hs-manacost/editor-ai.key` outside the repository, readable by the PHP-FPM
pool only (directory 0750; file root:pool-group 0640). Alternatively set
`HS_EDITOR_AI_KEY_FILE` to a protected absolute path or inject
`HS_EDITOR_AI_API_KEY` into the server environment. Never place a credential in
Git, a browser setting, Docker test environment or a command argument.

`HS_EDITOR_AI_ENABLED=false` disables new provider calls and hides controls.
Existing background workers also consult the switch before contacting the API.
Deleting the runtime key disables the feature. Staging and production need
their PHP pool to be able to read the selected file; code deployment does not
copy secrets.

## Jobs and boundaries

Authenticated REST requests use WordPress nonce verification and object
capabilities. A job belongs to one editor; another editor cannot retrieve it.
Each editor has one active lease and a limit of 20 new operations per hour.
Identical recent requests reuse their result. Full editing uses up to two model
requests per operation. Card candidates are capped at 100 per format.

The enqueue POST and editor saving do not wait for the model. After enqueue,
the browser starts a separate nonce-protected `POST /jobs/{id}/run`, restricted
to the job owner with `edit_post`. Each HTTP worker makes at most one 75-second
provider call, within the site's 120-second PHP-FPM ceiling. Full editing stores
its validated candidate privately and returns `queued` / `review`; the next
worker request runs only the independent review. Unapproved candidates never
appear in status responses and are removed on completion/expiry. Cron can
finish a pending review if the browser closes. These requests do not block
editor save/autosave. Polling starts at 750 ms
and backs off to 3 seconds (5 seconds in a hidden browser tab). Transient read
failures retry at most three times; recovery checks the existing job rather than
resending article text. The worker lease and a fresh state read prevent duplicate
calls even when cron races with immediate dispatch.

WP-Cron still runs `hs_editor_ai_process` as a fallback if the worker request
cannot start, the connection drops or the browser closes. A working cron runner
is still required. A pending job expires after five
minutes; `hs_editor_ai_expire` cleans abandoned payloads and worker locks. Source
and context are removed on completion. Results/fingerprints expire after 30
minutes through WordPress transients; physical expired-record cleanup follows
the installed transient/cron storage policy. These are not article revisions.

Transport has a 75-second deadline per model request, TLS verification,
redirects disabled and a 1 MiB response cap. It never automatically retries a
billable request. HTTP 429 sets a bounded cooldown. Timeout, 401/429/5xx,
malformed/truncated output, unknown IDs, markup injection or failed independent
review leave `post_content` unchanged. Outcome hook `hs_editor_ai_outcome`
contains only opaque identity/mode/status/code/duration; attaching persistent
telemetry is optional and must not capture text or credentials.

Input limits: selection 160 KB, whole-article context 200 KB, at most 500 edits.
Long output may exceed the response token budget and is rejected atomically.
HTML, entities, URLs, code blocks and existing shortcodes remain immutable.
Tags split editable prose into segments; edits across formatting boundaries
are deliberately constrained. Human review remains necessary for literary
editing; independent model review cannot guarantee factual equivalence.

## Verification and release

Run `python3 -m unittest tests.test_editor_ai`, `make check`, `make code-quality`,
`make contracts`, `make integration` and `make visual`, plus staged secret checks.
`tests/editor-ai/run.sh` runs on an existing disposable loopback integration
stack, using a synthetic credential and deterministic boundary transport. It
checks real WordPress permissions, storage, queue/replay/expiry/limits and
provider failures, then the real TinyMCE/text-mode flow, undo, stale rejection,
mobile/keyboard, real installed tooltip catalogs/handlers, draft saving, autosaves/revisions and frontend publication.
It never sends a production draft or credential. Preview screenshots and a
browser report are written under ignored `.artifacts/editor-ai/`.

Deploy through the normal branch/PR pipeline. Production requires the exact
successful staging SHA and `Promote production`; runtime credential provisioning
does not substitute for deployment. Rollback is a prior code release plus the
disable switch; applying a suggestion is reversible via editor undo/revision.

### Repeatable dispatch benchmark

With the isolated integration stack running, `tests/editor-ai/performance.sh
before` measures five requests on the prior plugin release with a controlled
60-second cron runner; `tests/editor-ai/performance.sh after` measures the same
journey on the new release. Neither phase contacts Anthropic: only its HTTP
boundary is replaced with an instant deterministic response. The reports record
role, draft count, warm cache, viewport, navigation TTFB, enqueue SQL/memory,
dialog-open and send-to-review timings, and browser long tasks. This proves queue
and UI latency, **not real Claude inference performance**. No article text,
credentials or session values enter the reports. Code rollback restores the
cron-only dispatch path; no schema or content migration is involved.
