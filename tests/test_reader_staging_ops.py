"""Regression checks for the isolated reader staging release tooling."""

from pathlib import Path
import os
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[1]
RELEASE = ROOT / "ops/reader/release-staging.sh"
ROLLBACK = ROOT / "ops/reader/rollback-staging.sh"
UNIT = ROOT / "ops/reader/manacost-reader-staging.service"
HEARTHPULSE_UNIT = ROOT / "ops/reader/hearthpulse-identity-staging.service"
HEARTHPULSE_NGINX = ROOT / "ops/reader/hearthpulse-identity-staging.nginx.conf"
IDENTITY_RELEASE = ROOT / "ops/reader/release-identity-staging.sh"
IDENTITY_ROLLBACK = ROOT / "ops/reader/rollback-identity-staging.sh"


class ReaderStagingOpsTests(unittest.TestCase):
    def make_clean_fixture(self, temporary: Path) -> tuple[Path, dict[str, str], str]:
        repo = temporary / "hs-manacost-reader-activation"
        (repo / "ops/reader").mkdir(parents=True)
        (repo / "services/reader").mkdir(parents=True)
        (repo / "services/reader/package-lock.json").write_text("{}", encoding="utf-8")
        (repo / "services/reader/server.js").write_text("// fixture", encoding="utf-8")
        for name in ("release-staging.sh", "rollback-staging.sh"):
            source = ROOT / "ops/reader" / name
            target = repo / "ops/reader" / name
            target.write_text(source.read_text(encoding="utf-8"), encoding="utf-8")
            target.chmod(0o755)
        subprocess.run(["git", "init", "-q"], cwd=repo, check=True)
        subprocess.run(["git", "config", "user.email", "test@example.invalid"], cwd=repo, check=True)
        subprocess.run(["git", "config", "user.name", "Reader test"], cwd=repo, check=True)
        subprocess.run(["git", "add", "."], cwd=repo, check=True)
        subprocess.run(["git", "commit", "-qm", "fixture"], cwd=repo, check=True)
        sha = subprocess.run(["git", "rev-parse", "HEAD"], cwd=repo, check=True, text=True, capture_output=True).stdout.strip()
        state_root = temporary / "state/manacost-reader-staging"
        env_file = temporary / "etc/manacost-reader-staging/service.env"
        env_file.parent.mkdir(parents=True)
        env_file.write_text("# secrets are managed outside the source tree\n", encoding="utf-8")
        env = {**os.environ, "MANACOST_READER_STAGING_ROOT": str(state_root), "MANACOST_READER_STAGING_ENV_FILE": str(env_file)}
        return repo, env, sha

    def run_release(self, repo: Path, env: dict[str, str], sha: str) -> subprocess.CompletedProcess[str]:
        return subprocess.run(["ops/reader/release-staging.sh", "--sha", sha], cwd=repo, env=env, text=True, capture_output=True, check=False)

    def make_identity_fixture(self, temporary: Path) -> tuple[Path, dict[str, str], str]:
        source = temporary / "hearthpulse-reader-staging-20260908"
        (source / "dist").mkdir(parents=True)
        (source / "build/server").mkdir(parents=True)
        (source / "dist/index.html").write_text("<!doctype html>", encoding="utf-8")
        (source / "build/server/index.js").write_text("// fixture", encoding="utf-8")
        (source / "package.json").write_text('{"name":"fixture"}', encoding="utf-8")
        (source / "package-lock.json").write_text('{"lockfileVersion":3}', encoding="utf-8")
        subprocess.run(["git", "init", "-q"], cwd=source, check=True)
        subprocess.run(["git", "config", "user.email", "test@example.invalid"], cwd=source, check=True)
        subprocess.run(["git", "config", "user.name", "Identity test"], cwd=source, check=True)
        subprocess.run(["git", "add", "."], cwd=source, check=True)
        subprocess.run(["git", "commit", "-qm", "fixture"], cwd=source, check=True)
        sha = subprocess.run(["git", "rev-parse", "HEAD"], cwd=source, check=True, text=True, capture_output=True).stdout.strip()
        env_file = temporary / "etc/hearthpulse-identity-staging/service.env"
        env_file.parent.mkdir(parents=True)
        env_file.write_text("# managed outside source\n", encoding="utf-8")
        env = {**os.environ, "HEARTHPULSE_IDENTITY_SOURCE_ROOT": str(source), "HEARTHPULSE_IDENTITY_STAGING_ROOT": str(temporary / "runtime/hearthpulse-identity-staging"), "HEARTHPULSE_IDENTITY_ENV_FILE": str(env_file)}
        return source, env, sha

    def run_identity_release(self, source: Path, env: dict[str, str], sha: str) -> subprocess.CompletedProcess[str]:
        return subprocess.run([str(IDENTITY_RELEASE), "--sha", sha], cwd=source, env=env, text=True, capture_output=True, check=False)

    def test_hardened_loopback_unit_has_private_state(self) -> None:
        source = UNIT.read_text(encoding="utf-8")
        self.assertIn("User=manacost-reader-staging", source)
        self.assertIn("Group=manacost-reader-staging", source)
        self.assertIn("WorkingDirectory=/srv/manacost-reader-staging/current", source)
        self.assertIn("EnvironmentFile=/etc/manacost-reader-staging/service.env", source)
        self.assertIn("READER_PORT=18181", source)
        self.assertIn("READER_DATABASE=/var/lib/manacost-reader-staging/reader.sqlite", source)
        for directive in (
            "ProtectSystem=strict",
            "ProtectHome=true",
            "NoNewPrivileges=true",
            "PrivateTmp=true",
            "StateDirectory=manacost-reader-staging",
            "StateDirectoryMode=0700",
            "UMask=0077",
            "MemoryMax=256M",
            "Restart=on-failure",
            "StartLimitBurst=3",
            "IPAddressDeny=any",
            "IPAddressAllow=127.0.0.1",
            "IPAddressAllow=151.80.21.140",
            "BindReadOnlyPaths=/etc/manacost-reader-staging/identity-hosts:/etc/hosts",
            "InaccessiblePaths=-/var/lib/manacost-ecosystem -/var/lib/docker -/var/www -/srv/projects/web -/etc/hs-arena",
        ):
            self.assertIn(directive, source)

    def test_release_defaults_to_dry_run_and_keeps_staging_boundary(self) -> None:
        source = RELEASE.read_text(encoding="utf-8")
        self.assertIn("apply=false", source)
        self.assertIn("--apply", source)
        self.assertIn("/srv/manacost-reader-staging", source)
        self.assertIn("/etc/manacost-reader-staging/service.env", source)
        self.assertIn("git status --porcelain", source)
        self.assertIn("git rev-parse HEAD", source)
        self.assertIn("npm ci --ignore-scripts", source)
        self.assertIn("sudo -u manacost-reader-staging rsync", source)
        self.assertIn("-type d -exec chmod 0550", source)
        self.assertIn("-type f -exec chmod 0440", source)
        self.assertIn("Apply requires the exact canonical staging targets", source)
        self.assertNotIn("chmod -R a-w", source)
        self.assertIn("ln -s", source)
        self.assertIn("mv -T", source)
        self.assertIn("systemctl daemon-reload", source)
        self.assertNotIn("systemctl start", source)
        self.assertNotIn("systemctl restart", source)
        self.assertNotIn("/srv/manacost-reader/current", source)

    def test_release_refuses_dirty_mismatched_missing_env_and_aliases(self) -> None:
        source = RELEASE.read_text(encoding="utf-8")
        for guard in (
            "Source checkout is dirty",
            "Expected SHA does not match HEAD",
            "Required environment file is absent",
            "Refusing non-staging release root",
            "Refusing a production-like release root",
            "Refusing symlinked path component",
            "Release link must be a symlink",
        ):
            self.assertIn(guard, source)

    def test_dry_run_rejects_each_unsafe_precondition_without_writes(self) -> None:
        with tempfile.TemporaryDirectory() as temporary_directory:
            temporary = Path(temporary_directory)
            repo, env, sha = self.make_clean_fixture(temporary)
            root = Path(env["MANACOST_READER_STAGING_ROOT"])

            clean = self.run_release(repo, env, sha)
            self.assertEqual(0, clean.returncode, clean.stderr)
            self.assertFalse(root.exists(), "default dry run must not create the release root")

            (repo / "dirty.txt").write_text("dirty", encoding="utf-8")
            dirty = self.run_release(repo, env, sha)
            self.assertNotEqual(0, dirty.returncode)
            self.assertIn("Source checkout is dirty", dirty.stderr)
            (repo / "dirty.txt").unlink()

            mismatch = self.run_release(repo, env, "0" * 40)
            self.assertNotEqual(0, mismatch.returncode)
            self.assertIn("Expected SHA does not match HEAD", mismatch.stderr)

            missing_env = {**env, "MANACOST_READER_STAGING_ENV_FILE": str(temporary / "missing/manacost-reader-staging/service.env")}
            missing = self.run_release(repo, missing_env, sha)
            self.assertNotEqual(0, missing.returncode)
            self.assertIn("Required environment file is absent", missing.stderr)

            root.mkdir(parents=True)
            (root / "current").symlink_to("/srv/manacost-reader")
            alias = self.run_release(repo, env, sha)
            self.assertNotEqual(0, alias.returncode)
            self.assertIn("Refusing unsafe release link", alias.stderr)
            self.assertTrue((root / "current").is_symlink())

            regular_root = temporary / "regular/manacost-reader-staging"
            regular_root.mkdir(parents=True)
            (regular_root / "current").write_text("not a link", encoding="utf-8")
            regular = self.run_release(repo, {**env, "MANACOST_READER_STAGING_ROOT": str(regular_root)}, sha)
            self.assertNotEqual(0, regular.returncode)
            self.assertIn("Release link must be a symlink", regular.stderr)

            linked_parent = temporary / "linked"
            linked_parent.symlink_to(temporary / "regular")
            component = self.run_release(repo, {**env, "MANACOST_READER_STAGING_ROOT": str(linked_parent / "manacost-reader-staging")}, sha)
            self.assertNotEqual(0, component.returncode)
            self.assertIn("Refusing symlinked path component", component.stderr)

    def test_rollback_only_switches_known_previous_and_never_removes_state(self) -> None:
        source = ROLLBACK.read_text(encoding="utf-8")
        self.assertIn("previous", source)
        self.assertIn("current", source)
        self.assertIn("mv -T", source)
        self.assertNotIn("rm -rf", source)
        self.assertNotIn("/var/lib/manacost-reader-staging", source)
        self.assertNotIn("systemctl start", source)
        self.assertNotIn("systemctl restart", source)

    def test_identity_release_is_dry_by_default_and_limits_artifact_contents(self) -> None:
        source = IDENTITY_RELEASE.read_text(encoding="utf-8")
        for requirement in (
            "/srv/projects/tasks/hearthpulse-reader-staging-20260908",
            "/srv/hearthpulse-identity-staging",
            "/etc/hearthpulse-identity-staging/service.env",
            "dist/index.html",
            "build/server/index.js",
            "npm ci --ignore-scripts --omit=dev",
            '"sha"',
            "chmod 0550",
            "chmod 0440",
            "chmod 0555",
            "chmod 0444",
        ):
            self.assertIn(requirement, source)
        self.assertNotIn("systemctl start", source)
        self.assertNotIn("systemctl restart", source)

    def test_identity_dry_run_rejects_dirty_mismatch_and_production_alias_without_writes(self) -> None:
        with tempfile.TemporaryDirectory() as temporary_directory:
            temporary = Path(temporary_directory)
            source, env, sha = self.make_identity_fixture(temporary)
            target = Path(env["HEARTHPULSE_IDENTITY_STAGING_ROOT"])
            clean = self.run_identity_release(source, env, sha)
            self.assertEqual(0, clean.returncode, clean.stderr)
            self.assertFalse(target.exists(), "default dry run must not create the identity target")

            (source / "dirty.txt").write_text("dirty", encoding="utf-8")
            dirty = self.run_identity_release(source, env, sha)
            self.assertNotEqual(0, dirty.returncode)
            self.assertIn("Identity source checkout is dirty", dirty.stderr)
            (source / "dirty.txt").unlink()

            mismatch = self.run_identity_release(source, env, "0" * 40)
            self.assertNotEqual(0, mismatch.returncode)
            self.assertIn("Expected SHA does not match identity source HEAD", mismatch.stderr)

            alias = self.run_identity_release(source, {**env, "HEARTHPULSE_IDENTITY_STAGING_ROOT": "/srv/hearthpulse-identity"}, sha)
            self.assertNotEqual(0, alias.returncode)
            self.assertIn("Refusing non-staging identity release root", alias.stderr)

    def test_identity_rollback_only_relinks_known_previous_without_runtime_actions(self) -> None:
        source = IDENTITY_ROLLBACK.read_text(encoding="utf-8")
        self.assertIn("previous", source)
        self.assertIn("mv -T", source)
        self.assertNotIn("rm -rf", source)
        self.assertNotIn("systemctl start", source)
        self.assertNotIn("systemctl restart", source)

    def test_hearthpulse_staging_unit_isolated_to_loopback_without_background_jobs(self) -> None:
        source = HEARTHPULSE_UNIT.read_text(encoding="utf-8")
        for directive in (
            "User=hearthpulse-identity-staging",
            "Group=hearthpulse-identity-staging",
            "WorkingDirectory=/srv/hearthpulse-identity-staging/current",
            "EnvironmentFile=/etc/hearthpulse-identity-staging/service.env",
            "Environment=HOST=127.0.0.1",
            "Environment=PORT=18182",
            "Environment=BACKGROUND_JOBS_ENABLED=0",
            "Environment=ARENA_DRAFT_REFRESH_ENABLED=0",
            "Environment=REDIS_ENABLED=0",
            "ExecStart=/usr/bin/node build/server/index.js",
            "StateDirectory=hearthpulse-identity-staging",
            "StateDirectoryMode=0700",
            "ProtectSystem=strict",
            "ProtectHome=true",
            "NoNewPrivileges=true",
            "PrivateTmp=true",
            "IPAddressDeny=any",
            "IPAddressAllow=127.0.0.1",
            "IPAddressAllow=::1",
            "InaccessiblePaths=-/var/lib/manacost-ecosystem -/var/lib/docker -/var/www -/srv/projects/web -/etc/hs-arena",
        ):
            self.assertIn(directive, source)

    def test_reader_edge_routes_disable_query_logs_and_verify_origin_tls(self) -> None:
        source = (ROOT / "ops/reader/proxy-staging-reader.conf").read_text(encoding="utf-8")
        for directive in (
            "location ~ ^/(?:reader-auth|reader-api)/",
            "access_log off;", "error_log /dev/null;", "proxy_cache off;",
            "proxy_ssl_verify on;", "proxy_ssl_name test.hs-manacost.ru;",
            "proxy_ssl_trusted_certificate /etc/ssl/certs/ca-certificates.crt;",
            "proxy_set_header Authorization $http_authorization;",
            "proxy_set_header X-Forwarded-For $remote_addr;",
            "proxy_set_header CF-Connecting-IP $remote_addr;",
        ):
            self.assertIn(directive, source)
        self.assertNotIn("auth_basic off", source)
        self.assertNotIn("$proxy_add_x_forwarded_for", source)

    def test_hearthpulse_vhost_is_public_but_has_exact_noindex_allowlist(self) -> None:
        source = HEARTHPULSE_NGINX.read_text(encoding="utf-8")
        self.assertIn("listen 151.80.21.140:443 ssl;", source)
        self.assertIn("http2 on;", source)
        self.assertIn("listen 151.80.21.140:80", source)
        self.assertIn("server_name test.hearthpulse.net", source)
        self.assertIn("/etc/letsencrypt/live/test.hearthpulse.net/fullchain.pem", source)
        self.assertIn("/etc/letsencrypt/live/test.hearthpulse.net/privkey.pem", source)
        self.assertIn("root /srv/hearthpulse-identity-staging/current/dist", source)
        self.assertIn('X-Robots-Tag "noindex, nofollow, noarchive"', source)
        self.assertNotIn("auth_basic", source)
        for location in (
            "location ~ ^/(?:identity/.*|api/auth/(?:me|telegram/config|login|register|verify|password-reset/request|password-reset/confirm|logout))$",
            "location = /account/",
            "location = /account/",
            "location /api/ { return 404; }",
            "location / { return 404; }",
        ):
            self.assertIn(location, source)
        for header in (
            "proxy_set_header Host test.hearthpulse.net",
            "proxy_set_header X-Forwarded-Host test.hearthpulse.net",
            "proxy_set_header X-Forwarded-Proto https",
            "proxy_set_header X-Forwarded-For $remote_addr",
            "proxy_set_header X-Real-IP $remote_addr",
            'proxy_set_header CF-Connecting-IP ""',
            'proxy_set_header Forwarded ""',
            "proxy_set_header Authorization $http_authorization",
            'Cache-Control "private, no-store"',
        ):
            self.assertIn(header, source)
        self.assertIn("if ($identity_method_allowed = 0) { return 405; }", source)
        self.assertNotIn("location = /register", source)
        self.assertNotIn("location = /telegram/config", source)
        self.assertNotIn("location ^~ /api/", source)


if __name__ == "__main__":
    unittest.main()
