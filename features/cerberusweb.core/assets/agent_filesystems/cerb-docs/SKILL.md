---
name: cerb-docs
description: Look up Cerb documentation locally. Use when answering questions about Cerb features, configuration, automations, APIs, integrations, or any Cerb topic — search the local reference files without web access.
---

# Cerb Docs

1059 Cerb documentation pages stored as local Markdown files under `references/`.
Each file has YAML frontmatter (`id`, `title`, `url`, `summary`, `tags`) followed by the full page content.

## How to Search

Grep the references directory for keywords, then read the matching files:

```bash
# Find files mentioning a topic
grep -rl "KEYWORD" cerb-docs/references/

# Search within a subtree
grep -rl "oauth" cerb-docs/references/guides/integrations/

# Search titles/summaries only (frontmatter)
grep -l "title:.*KEYWORD" cerb-docs/references/**/*.md

# Case-insensitive content search with context
grep -ri "webhooks" cerb-docs/references/docs/ -l
```

Read matched files in full — they contain complete documentation content.

## Doc Gaps — Proposing New or Improved Documentation

When a question can't be adequately answered from the local references, check GitHub for an existing proposal before filing a new one.

**1. Check for existing issues (no auth required):**
```bash
gh issue list --repo cerb/cerb.github.io --search "KEYWORDS" --state open
```
Search with 2-3 specific keywords from the question. If a matching open issue exists, note it and do not create a duplicate.

**2. If no existing issue, create one (requires `gh auth login`):**
```bash
gh issue create --repo cerb/cerb.github.io \
  --label "ai-proposed" \
  --title "Docs gap: CONCISE TITLE" \
  --body "$(cat <<'EOF'
## Question
[The user's question verbatim or paraphrased]

## Gap
[What's missing or inadequate in the current docs — be specific]

## Closest existing page
[URL of the nearest existing doc, or "none found"]

## Suggested content
[Brief outline of what the new/improved page should cover]
EOF
)"
```

Always include `--label "ai-proposed"`. The title must start with `Docs gap:` so automated tooling can identify it. Omit the issue body section `## Suggested content` if you have nothing useful to add.

If `gh` is not authenticated, surface the gap in conversation and suggest the user visit https://github.com/cerb/cerb.github.io/issues/new to file it manually.

## Never Edit Reference Files Directly

The `references/` directory is auto-generated from the official Cerb documentation at `https://cerb.ai/docs/`. **Do not edit any file under `references/` by hand** — changes will be overwritten the next time the fetch tool runs.

To improve or correct documentation: use the Doc Gaps workflow above to file an issue on `cerb/cerb.github.io`. That is the authoritative source.

## Updating the Reference Files

Run the fetch tool to pull the latest docs from cerb.ai:

```bash
python3 cerb-docs/tools/fetch-docs.py --clean
# or from a local JSONL snapshot:
python3 cerb-docs/tools/fetch-docs.py --input /path/to/search.jsonl --clean
```

## Related Skills

These skills are always installed alongside this one:

- `/cerb-automations` — write or modify Cerb automations (KATA, commands, triggers, events)
- `/cerb-dev` — make changes to the Cerb PHP/MySQL codebase (new record types, extensions, migrations)
- `/cerb-search` — build search queries for any record type

## Directory Structure

```
references/
  docs/
    api/
      authentication/
      endpoints/
      libraries/
      records/
    automations/
      commands/
      events/
      inputs/
      triggers/
        interaction.website/
          elements/
        interaction.worker/
          elements/
    dashboards/
      widgets/
    data-queries/
      attachment/
      autocomplete/
      automation/
      calendar/
      classifier/
      data/
      gpg/
      metrics/
      platform/
      record/
      ui/
      usage/
      worklist/
    developers/
    groups/
    guide/
      admins/
      developers/
    installation/
    plugins/
    portals/
    records/
      fields/
        types/
      types/
    scripting/
      filters/
      functions/
    setup/
      configure/
      developers/
      mail/
      packages/
      plugins/
      records/
      storage/
      team/
    toolbars/
      interactions/
  guides/
    ai-agents/
    api/
    automations/
      automation.timer/
      interaction.worker/
    bots/
    classifiers/
    developers/
    impex/
    installation/
    integrations/
      aws/
      azure/
      google/
      jotform/
      salesforce/
      slack/
    interactions/
      website/
    localization/
    machine-learning/
      image-generation/
      question-answering/
    mail/
      encryption/
      privacy/
    packages/
    portals/
    project-boards/
    records/
    scaling/
    security/
    sso/
    webhooks/
  releases/
  resources/
  solutions/
    automations/
    integrations/
  tips/
  workflows/
```
