import assert from "node:assert/strict";
import { existsSync } from "node:fs";
import { spawn } from "node:child_process";
import { chromium } from "@playwright/test";

const port = process.env.V2_BROWSER_PORT ?? "3213";
const origin = `http://127.0.0.1:${port}`;
const server = spawn(process.execPath, ["node_modules/next/dist/bin/next", "start", "-p", port], {
  cwd: new URL("..", import.meta.url),
  env: { ...process.env, NODE_ENV: "production" },
  stdio: "ignore",
});

async function waitForServer() {
  for (let attempt = 0; attempt < 30; attempt += 1) {
    try {
      const response = await fetch(`${origin}/api/health`);
      if (response.ok) return;
    } catch {}
    await new Promise((resolve) => setTimeout(resolve, 250));
  }
  throw new Error("v2 browser server did not become healthy");
}

try {
  await waitForServer();
  const launchOptions = existsSync("/usr/bin/chromium") ? { executablePath: "/usr/bin/chromium" } : {};
  const browser = await chromium.launch({ headless: true, ...launchOptions });
  const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
  const errors = [];
  page.on("console", (message) => { if (message.type() === "error") errors.push(message.text()); });

  const home = await page.goto(origin, { waitUntil: "networkidle" });
  assert.equal(home?.status(), 200);
  assert.match(home?.headers()["x-robots-tag"] ?? "", /noindex, nofollow/);
  assert.equal(await page.locator("h1").textContent(), "Свежее на Манакосте");
  assert.ok(await page.locator("img").first().evaluate((image) => image.naturalWidth > 0));

  const articlePath = await page.locator(".story-card--hero h2 a").getAttribute("href");
  assert.ok(articlePath?.startsWith("/"));
  const article = await page.goto(`${origin}${articlePath}`, { waitUntil: "networkidle" });
  assert.equal(article?.status(), 200);
  assert.ok((await page.locator(".article-body").textContent())?.trim().length > 200);

  assert.equal((await page.goto(`${origin}/category/novosti`))?.status(), 200);
  assert.deepEqual(errors, []);
  assert.equal((await page.goto(`${origin}/category/vip`))?.status(), 404);
  const denied = await page.request.get(`${origin}/api/media?url=${encodeURIComponent("http://127.0.0.1:80/private.jpg")}`);
  assert.equal(denied.status(), 403);

  await browser.close();
  process.stdout.write("v2 browser smoke: PASS\n");
} finally {
  server.kill("SIGTERM");
}
