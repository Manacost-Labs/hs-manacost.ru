# Interaction patterns

## Forms

- Place a visible label before every control and connect it with `for`/`id`.
- Put concise help after the control and associate it with `aria-describedby`.
- Mark required fields in text, not only by color or an asterisk.
- Validate on the server. Add client validation only to shorten recovery.
- Put an inline error beside the field and a focusable summary above long forms.
- Keep entered values and return focus to the first invalid field after submission.
- Group related choices with `fieldset` and `legend`.
- Warn about unsaved changes only when data has actually changed.

## Lists and tables

- Put search, status filters, useful saved views, result count, and primary action above the list.
- Store filter, sort, page, and search state in the URL.
- Paginate on the server and select only columns needed for the current view.
- Make the entity name the row's primary link; keep common row actions discoverable.
- Support bulk actions only when they save meaningful repeated work.
- On narrow screens, keep identity, status, and primary action visible; move secondary fields into an expandable row or a deliberate stacked list.
- Explain the empty result for the current filter separately from an entirely empty product state.

## Navigation

- A single utility belongs under an existing WordPress menu.
- A daily multi-screen area may use one top-level entry with a small stable submenu.
- Keep tabs for peer views of the same object; use steps only for a linear process.
- Preserve query state when returning from edit to list.

## Async actions

- Disable only the control being submitted, not the whole page.
- Announce progress with visible text and an `aria-live` region.
- Use a skeleton for predictable content layouts; use a spinner for compact actions.
- Prevent duplicate submissions, but allow retry after failure.
- Use optimistic UI only for reversible low-risk changes and restore the previous state automatically when the request fails.
- For background jobs, display queued/running/completed/failed state and a durable result link.

## Notices and errors

- Put validation messages next to the field and summarize them when the form is long.
- Make errors actionable: identify the object, failed action, and safe next step.
- Do not use success styling for requests that are still processing.
- Do not rely on transient toast messages for permissions, data loss, exports, imports, or deletion.

## Dialogs and destructive actions

- Prefer an inline confirmation when context matters; use a modal only when the decision must interrupt the flow.
- Move focus into the dialog, trap it, close with Escape when safe, and restore focus to the opener.
- Name the exact object and consequence. Distinguish recoverable trash/archive from permanent deletion.
- Keep cancel as the safe default. Require additional confirmation for irreversible bulk operations.

## Media fields

Use the WordPress media library when selecting existing images. Show preview, filename, alternative text status, replace, and remove actions. Validate attachment type and ownership/permission on the server; never trust a client-provided URL as proof of access.
