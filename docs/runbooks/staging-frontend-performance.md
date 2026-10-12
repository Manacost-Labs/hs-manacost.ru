# Protected staging frontend measurements

The staging performance workflow collects five anonymous, cold browser samples
per home, page 2 and account route, with 390 × 844 / DPR 3, 150 ms network latency,
200000 bytes/s download and fourfold CPU slowdown. It reports TTFB, laboratory
LCP/CLS, long tasks, visible-image failures, loaded fonts and layout overflow.
This is laboratory evidence, not field CWV or an INP measurement.

The protected HTTP credentials stay in memory. The observer permits read-only
staging navigation and static assets, blocks mutations, analytics/view endpoints,
and production pages/APIs. No cookies or Authorization headers enter artifacts.
Reports retain only allowlisted metrics/headers; caught errors emit their class.
Staging must remain noindex. Every sample requires CLS <=0.1 and no missing or
pending visible image or overflow. Widths 320/390/768/1024/1440 are additionally
checked on the fifth loaded page. Existing reader scenarios cover keyboard and
200% zoom separately; the collector does not replace functional/accessibility
checks or actual rendered-font comparisons.

A successful deployment's performance run must not be cancelled by a later
skipped deployment event; concurrency preserves the running measurement. The
existing authenticated admin, save/revision and upload diagnostics still run.
The report is `frontend-slow-mobile.json` in the existing staging evidence artifact.
