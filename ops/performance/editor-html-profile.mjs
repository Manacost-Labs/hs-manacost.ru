// Only return byte counts. Inline scripts and panel markup can contain nonces or article text.
export function profileEditorHtml() {
  const bytes = new TextEncoder();
  const size = value => bytes.encode(value).byteLength;
  const groups = {};
  const add = (name, length) => { groups[name] = (groups[name] || 0) + length; };
  for (const script of document.scripts) {
    if (script.src) continue;
    const group = script.type === 'text/html' || script.id.startsWith('tmpl-') ? 'templates'
      : script.id.endsWith('-js-translations') ? 'translations'
      : script.id.endsWith('-js-extra') ? 'localized_data' : 'other_inline_js';
    add(group, size(script.textContent));
  }
  const panels = [...document.querySelectorAll('#poststuff .postbox')].filter(
    panel => !panel.parentElement.closest('.postbox'));
  return { inline_script_text_bytes: groups,
    top_level_panels: panels.length,
    rendered_panel_html_bytes: panels.reduce((sum, panel) => sum + size(panel.outerHTML), 0) };
}
