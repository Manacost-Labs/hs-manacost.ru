// This function is serialized into the browser by Playwright. Keep it self-contained.
export function collectSqlProfile() {
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
