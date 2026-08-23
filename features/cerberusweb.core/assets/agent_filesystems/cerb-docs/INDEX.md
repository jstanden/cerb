---
title: "Cerb documentation index"
summary: "What lives in each directory of the Cerb documentation volume, the file paths that are predictable enough to read directly, and the frontmatter every page carries."
---

# Cerb documentation

The complete Cerb documentation, mirrored from `https://cerb.ai/docs/` for the release you are running on. It is the authority for how Cerb behaves and what its syntax is. Read-only.

It is **not** the authority for this installation's own keys: record types, searchable filters, writable fields, and custom fieldsets vary per install, and these pages describe the generic baseline. Where a terminal offers a `cerb records` command, that reflects what is actually here and wins on names.

## Directories

All content is under `references/`.

| Path | What it holds | Pages |
|---|---|---|
| `references/docs/` | The reference manual -- syntax, commands, record types, API, setup. The default place to look. | 581 |
| `references/releases/` | Per-version release notes. Where a feature's first appearance is recorded. | 227 |
| `references/solutions/` | Worked, copy-pasteable answers to specific problems, especially `solutions/automations/`. Adapt one before writing from scratch. | 107 |
| `references/guides/` | Task-oriented walkthroughs, including `guides/integrations/<vendor>/`. | 66 |
| `references/workflows/` | Workflow packages and what they do. | 29 |
| `references/tips/` | Short single-idea notes. | 23 |
| `references/resources/` | Assets and downloads. | 4 |

## Paths you can go to directly

These are predictable enough to read without searching first:

- `references/docs/automations/commands/<command>.md` -- one page per automation command (`set.md`, `record.search.md`, ...)
- `references/docs/scripting/filters.md` and `functions.md` -- indexes, with a page each under `filters/` and `functions/`
- `references/docs/records/types/<type>.md` -- one page per record type: its API fields, placeholders, search filters, worklist columns
- `references/docs/data-queries/<type>/<name>.md` -- one page per data query type
- `references/docs/search.md` -- the search query grammar
- `references/docs/kata.md` -- the KATA language

## Every page carries frontmatter

`id`, `title`, `url`, `summary`, `tags`. Two things follow.

A listing can carry those fields, so you can survey a whole subtree by title and summary without opening a single file. The `summary` is a full abstract, not a fragment -- often enough to pick the right page.

The `url` is the public page. When telling someone where something is documented, cite that rather than a path inside a volume only you can see.

## Search it, do not browse it

Over a thousand files. Listing a directory tells you little; matching content tells you a lot. Two distinctive words beat five ordinary ones, since this corpus shares vocabulary heavily. Then read only the section you need rather than the whole page.

If a `cerb-agents` volume is also mounted, `@cerb-agents/skills/docs/SKILL.md` covers the search mechanics in more depth.
