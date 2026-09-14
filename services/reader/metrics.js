const MAX_SAMPLES = 1024;

/** Aggregate-only Reader metrics; never retain URLs, identities, or payloads. */
export class ReaderMetrics {
  constructor({ now = () => performance.now() } = {}) {
    this.now = now;
    this.routes = new Map();
  }

  start() { return this.now(); }

  record(route, status, startedAt) {
    const key = String(route).replace(/[0-9a-f-]{8,}/gi, ':id');
    const metric = this.routes.get(key) ?? { requests: 0, errors: 0, throttled: 0, samples: [] };
    const elapsed = Math.max(0, this.now() - startedAt);
    metric.requests += 1;
    if (status >= 500) metric.errors += 1;
    if (status === 429) metric.throttled += 1;
    metric.samples.push(elapsed);
    if (metric.samples.length > MAX_SAMPLES) metric.samples.shift();
    this.routes.set(key, metric);
  }

  snapshot() {
    const routes = {};
    for (const [route, metric] of this.routes) {
      const samples = [...metric.samples].sort((left, right) => left - right);
      const at = percentile => samples.length ? samples[Math.min(samples.length - 1, Math.floor((samples.length - 1) * percentile))] : 0;
      routes[route] = { requests: metric.requests, errors: metric.errors, throttled: metric.throttled,
        p50Ms: Math.round(at(0.5)), p95Ms: Math.round(at(0.95)), p99Ms: Math.round(at(0.99)) };
    }
    const memory = process.memoryUsage();
    return { routes, process: { rssBytes: memory.rss, heapUsedBytes: memory.heapUsed } };
  }
}
