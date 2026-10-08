import assert from "node:assert/strict";
import { mkdtempSync, mkdirSync, rmSync, writeFileSync } from "node:fs";
import { createRequire } from "node:module";
import { tmpdir } from "node:os";
import { dirname, join, resolve } from "node:path";
import test from "node:test";
import { Linter } from "eslint";

const load = createRequire(import.meta.url);
const pluginDirectory = dirname(load.resolve("@next/eslint-plugin-next"));
const { getRootDirs } = load(join(pluginDirectory, "utils/get-root-dirs.js"));

test("Next lint root globs retain directory and brace matching without vulnerable braces", () => {
  const metadata = load(load.resolve("fast-glob/package.json", { paths: [pluginDirectory] }));
  assert.equal(metadata.name, "tinyglobby");
  const root = mkdtempSync(join(tmpdir(), "manacost-lint-glob-"));
  try {
    for (const name of ["one", "two", ".hidden"]) mkdirSync(join(root, name));
    writeFileSync(join(root, "file"), "not a root directory");
    const find = (pattern: string | string[]) => getRootDirs({ cwd: root, settings: { next: { rootDir: pattern } } })
      .map((entry: string) => resolve(entry)).sort();
    assert.deepEqual(find(`${root}/*`), [join(root, "one"), join(root, "two")]);
    assert.deepEqual(find(`${root}/{one,two}`), [join(root, "one"), join(root, "two")]);
    assert.deepEqual(find([`${root}/one`, `${root}/two`]), [join(root, "one"), join(root, "two")]);
    assert.deepEqual(find(`${root}/${"{".repeat(5000)}x${"}".repeat(5000)}`), []);
    mkdirSync(join(root, "one", "pages"));
    writeFileSync(join(root, "one", "pages", "index.js"), "export default function Home() {}");
    const messages = new Linter().verify('const link = <a href="/">Home</a>;', [{
      languageOptions: { parserOptions: { ecmaFeatures: { jsx: true } } },
      settings: { next: { rootDir: `${root}/*` } },
      plugins: { "@next/next": load("@next/eslint-plugin-next") },
      rules: { "@next/next/no-html-link-for-pages": "error" },
    }]);
    assert.ok(messages.some((message) => message.ruleId === "@next/next/no-html-link-for-pages"),
      "Next link rule still discovers pages and rejects plain links through the replacement engine");
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});
