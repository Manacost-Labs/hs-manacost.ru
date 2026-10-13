// This function is serialized into the browser by Playwright. Keep it self-contained.
export function collectSqlProfile() {
  const native = globalThis.__hsAdminPerformance?.sql_profile;
  if (native && typeof native.callers === 'object') {
    const callers = Object.values(native.callers).filter(item => item
      && typeof item.caller === 'string' && /^[A-Za-z_\\][A-Za-z0-9_\\:>\-]{0,79}$/.test(item.caller)
      && Number.isFinite(item.count) && item.count >= 0
      && Number.isFinite(item.total_ms) && item.total_ms >= 0)
      .map(({ caller, count, total_ms }) => ({ caller, count, total_ms: Math.round(total_ms * 1000) / 1000 }));
    return {
      available: true, source: 'request-scoped-probe',
      window: native.window === 'admin_init_to_admin_footer' ? native.window : null,
      coverage_consistent: native.coverage_consistent === true,
      queries_before: Number(native.queries_before), profiled_queries: Number(native.profiled_queries),
      total_ms: Math.round(Number(native.total_ms) * 1000) / 1000,
      top_callers: [...callers].sort((a, b) => b.total_ms - a.total_ms).slice(0, 12),
      top_by_count: [...callers].sort((a, b) => b.count - a.count).slice(0, 12),
    };
  }
  const rows = globalThis.QueryMonitorData?.data?.db_queries?.data?.rows;
  const totals = new Map();
  if (rows && typeof rows === 'object') {
    for (const row of Object.values(rows)) {
      const seconds = Number(row?.ltime);
      if (!Number.isFinite(seconds) || seconds < 0) continue;
      const stack = Array.isArray(row?.stack) ? row.stack : [];
      const safeNames = stack.filter(name =>
        typeof name === 'string' && /^[A-Za-z_\\][A-Za-z0-9_\\:>\-]{0,79}$/.test(name));
      const caller = safeNames.find(name => /^(hs_|wf|td_|AIOSEO|WPRocket)/i.test(name))
        ?? safeNames[0] ?? 'other';
      const total = totals.get(caller) ?? { caller, count: 0, total_ms: 0 };
      total.count += 1;
      total.total_ms += seconds * 1000;
      totals.set(caller, total);
    }
  }
  const callers = [...totals.values()].map(item => ({
    ...item, total_ms: Math.round(item.total_ms),
  }));
  return {
    available: Boolean(rows),
    profiled_queries: callers.reduce((sum, item) => sum + item.count, 0),
    top_callers: callers
      .sort((left, right) => right.total_ms - left.total_ms)
      .slice(0, 12),
    top_by_count: [...callers]
      .sort((left, right) => right.count - left.count)
      .slice(0, 12),
  };
}
