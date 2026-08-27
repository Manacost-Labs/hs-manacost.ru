# Claude Code entry point

Before any action in this repository, read and follow `AGENTS.md` completely. It is the canonical project rule file.

Always load `hs-manacost-project`, then choose the task-specific skills from `config/ai-skills.json`. Canonical skills are in `.agents/skills`; `.claude/skills` is a synchronized copy and must not be edited independently.

Start with:

```bash
git status --short
./.agents/skills/hs-manacost-project/scripts/context-snapshot.sh
```

Work in the source repository, test on staging, and never treat `/var/www`, databases, uploads, S3 objects, caches or secrets as Git source. Production promotion requires the exact staging-verified commit and explicit task scope.
