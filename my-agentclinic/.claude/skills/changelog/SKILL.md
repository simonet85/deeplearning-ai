---
name: changelog
description: Update CHANGELOG.md in the project root from git commits, grouped under date headings. Invoke manually before merging a branch.
disable-model-invocation: true
---

# Changelog

Keep `CHANGELOG.md` (project root, next to `README.md`) current. Run this before merging a branch.

## File format
- Title `# Changelog`, a short intro, then date headings `## YYYY-MM-DD`, newest first.
- Under each date, bullets describing what changed.
- A marker comment near the top records the last commit already covered: `<!-- changelog-last-commit: <short-hash> -->`.

## Steps
1. Read `CHANGELOG.md`. If it does not exist, create it with the title, intro and marker, and treat every commit as new.
2. Find new commits: `git log --date=short --format="%ad|%h|%s" <marker-hash>..HEAD -- .` (omit the range if there is no marker). If the marker hash is not in history, fall back to commits dated after the newest heading.
3. If there are no new commits, say so and stop without editing anything.
4. For each commit, read its message and, if needed, `git show --stat <hash>` to understand the change. Skip merge commits, changelog-only commits and trivial noise.
5. Group by commit date (`%ad`). Write one bullet per meaningful change, in plain past tense, describing the user-visible or team-visible effect (for example "Added...", "Fixed...", "Removed..."). Combine closely related commits into one bullet. Do not paste commit hashes or raw messages.
6. Insert the bullets under the matching `## YYYY-MM-DD` heading, creating it in date order if missing and appending to it if it already exists. Never duplicate or rewrite earlier entries.
7. Update the marker to the newest commit hash covered (current `git rev-parse --short HEAD`, unless the only unrecorded commit is the changelog update itself).
8. Show the user the diff of `CHANGELOG.md`. Do not commit; the user decides when to commit and merge.

## Style
- One line per bullet, specific, no marketing language.
- Reflect real behavior changes first (features, fixes, removals), then specs and docs.
- Match the existing entries' tone and tense.
