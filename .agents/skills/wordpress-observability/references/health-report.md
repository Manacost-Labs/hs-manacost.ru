# Health report

Use this compact structure:

1. **Status:** healthy, degraded or unavailable; UTC observation window.
2. **User impact:** affected hosts, regions and flows.
3. **Evidence:** measured status/latency/freshness with route and baseline.
4. **First abnormal layer:** observed fact; label any causal statement as inference.
5. **Risk:** data, SEO, security, capacity and recurrence.
6. **Action:** smallest safe next step and rollback.
7. **Gaps:** unavailable signals or checks not performed.

Never report “all regions” unless every configured route was actually tested. A green service process does not prove that WordPress, images, editor or public cache are correct.
