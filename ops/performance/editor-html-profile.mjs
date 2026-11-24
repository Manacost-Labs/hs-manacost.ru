// Only return byte counts. Inline scripts and panel markup can contain nonces or article text.
export function profileEditorHtml() {
  const bytes = new TextEncoder();
  const size = value => bytes.encode(value).byteLength;
  const groups = {};
  const localized = [];
  const add = (name, length) => { groups[name] = (groups[name] || 0) + length; };
  for (const script of document.scripts) {
    if (script.src) continue;
    const group = script.type === 'text/html' || script.id.startsWith('tmpl-') ? 'templates'
      : script.id.endsWith('-js-translations') ? 'translations'
      : script.id.endsWith('-js-extra') ? 'localized_data' : 'other_inline_js';
    const length = size(script.textContent);
    add(group, length);
    if (group === 'localized_data') {
      const handle = script.id.slice(0, -9);
      localized.push({handle: /^[A-Za-z0-9_.:@/-]{1,120}$/.test(handle) ? handle : '(other)', bytes: length});
    }
  }
  const panels = [...document.querySelectorAll('#poststuff .postbox')].filter(
    panel => !panel.parentElement.closest('.postbox'));
  return { inline_script_text_bytes: groups,
    largest_localized_handles: localized.sort((a, b) => b.bytes - a.bytes).slice(0, 6),
    top_level_panels: panels.length,
    rendered_panel_html_bytes: panels.reduce((sum, panel) => sum + size(panel.outerHTML), 0) };
}
