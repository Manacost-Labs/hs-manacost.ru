# Supported editor extensions

## Classic Editor and TinyMCE

- Scope admin assets with the screen hook plus `get_current_screen()`.
- Add TinyMCE plugins through `mce_external_plugins` and controls through `mce_buttons`/the appropriate toolbar filter.
- Use WordPress dialogs, media frames and AJAX/REST APIs instead of reaching into unrelated editor DOM.
- Register meta boxes with `add_meta_boxes`; save through a dedicated handler that verifies capability, nonce, post type and typed input.
- Ignore unrelated autosaves and revisions in metadata handlers, but never disable WordPress autosave or revision creation globally.
- Make toolbar registration idempotent so Advanced Editor Tools and custom plugins cannot produce duplicate buttons.

## Gutenberg

- Enqueue editor-only JavaScript with `enqueue_block_editor_assets`.
- Register extensions through public block editor APIs. Use `registerPlugin` and a supported slot such as `PluginDocumentSettingPanel` for document-level controls.
- Define REST visibility and writable schemas for post metadata. Add a server-side `permission_callback` to custom routes.
- Do not scrape or reposition internal Gutenberg DOM nodes. WordPress editor markup is not a stable extension API.
- Test undo/redo, dirty-state indication, autosave, preview and switching modes where switching remains enabled.

Official references:

- [Block editor developer guides](https://developer.wordpress.org/block-editor/how-to-guides/)
- [Block editor interface](https://developer.wordpress.org/block-editor/explanations/user-interface/)
- [PluginDocumentSettingPanel](https://developer.wordpress.org/block-editor/reference-guides/slotfills/plugin-document-setting-panel/)
- [Advanced Editor Tools](https://wordpress.org/plugins/tinymce-advanced/)
- [Classic Editor](https://wordpress.org/plugins/classic-editor/)

## Save-handler template requirements

Every write path must:

1. Confirm the expected post ID, post type and current screen/action.
2. Reject missing/invalid nonce.
3. Check the narrowest capability for that post.
4. Validate safelisted choices and sanitize other input by type.
5. Preserve an absent field when absence means “UI not loaded”; do not erase metadata accidentally.
6. Avoid recursive `wp_update_post()` loops.
7. Escape values only when rendering them into the target context.

Prefer server-rendered controls with small progressive enhancements. Add React only when the existing editor APIs require it or the interaction genuinely needs client state.
