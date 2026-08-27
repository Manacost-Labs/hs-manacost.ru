# Newspaper and admin patterns

- Keep Newspaper parent theme and tagDiv packages unchanged; implement semantics in the child theme, MU-plugin, supported template hook or first-party stylesheet/script.
- Preserve tagDiv module link targets and heading hierarchy when changing card markup. Decorative thumbnails use empty alt; informative images use editorial alt text.
- Mobile menus expose an accessible name, expanded state and focus return. Closing by Escape must work without breaking touch behavior.
- wp-admin list tables need an equivalent compact mobile view with every primary action reachable. Do not hide state or destructive actions solely inside hover menus.
- Editor sidebars and metaboxes retain WordPress keyboard conventions, autosave/revision behavior and notices. Announce asynchronous completion or failure without moving focus unexpectedly.
