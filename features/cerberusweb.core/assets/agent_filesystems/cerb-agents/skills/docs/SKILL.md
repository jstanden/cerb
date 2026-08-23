---
name: docs
description: The mounted Cerb documentation -- what lives where, how to find a page without reading it, and when the docs are the authority versus this install. Load it before your first search of `cerb-docs`.
---

# The Cerb documentation

`cerb-docs` is a read-only volume holding the complete Cerb documentation as Markdown -- the same pages published at `https://cerb.ai/docs/`, mirrored for the release you are running on. It is the authority for **how Cerb behaves and what its syntax is**.

## What it is NOT the authority for

**This install's keys.** Record types, searchable filters, writable fields, and custom fieldsets are per-installation, and the docs describe the generic baseline. `cerb records` reflects what is actually here. When the two disagree about a name, `cerb records` wins; see the `records` skill.

The docs still win over your own recall, always. A syntax you are confident about is exactly the kind of thing that is subtly wrong.

## Where things live

Seven directories under `references/`, and knowing which one you want saves a search:

| Path | What | Pages |
|---|---|---|
| `references/docs/` | The reference manual -- syntax, commands, record types, API, setup. The default place to look. | 581 |
| `references/guides/` | Task-oriented walkthroughs, including integrations (`guides/integrations/<vendor>/`). | 66 |
| `references/solutions/` | Worked, copy-pasteable answers to specific problems -- `solutions/automations/` especially. Adapt one before writing from scratch. | 107 |
| `references/tips/` | Short single-idea notes. | 23 |
| `references/workflows/` | Workflow packages and what they do. | 29 |
| `references/releases/` | Per-version release notes. Where a feature's first appearance is recorded. | 227 |
| `references/resources/` | Assets and downloads. | 4 |

Paths worth going to DIRECTLY rather than searching for, because the name is predictable:

- `references/docs/automations/commands/<command>.md` -- one page per automation command (`set.md`, `record.search.md`, ...)
- `references/docs/scripting/filters.md` and `functions.md` -- indexes, with `filters/<name>.md` and `functions/<name>.md` per entry
- `references/docs/records/types/<type>.md` -- one page per record type, listing its API fields, placeholders, search filters, and worklist columns
- `references/docs/search.md` -- the whole search query grammar
- `references/docs/data-queries/<type>/<name>.md` -- one page per data query type
- `references/docs/kata.md` -- the KATA language

## Every page carries frontmatter, and it is worth using

```
id, title, url, summary, tags
```

Two things follow from that.

**Survey without opening anything.** `find` carries frontmatter on every row, so you can read titles and summaries across a whole subtree in one call rather than opening files to find out what they are:

```
find references/docs/automations --fields title | files|column("meta.title")
```

**`url` is the public page.** When you tell a worker where something is documented, cite that URL -- it is a link they can open -- rather than a path inside a volume only you can see.

The `summary` field is a full abstract, not a sentence fragment. Reading summaries is often enough to pick the right page, and always cheaper than reading the pages.

## Finding a page

The mechanics of `search`, `--terms`, `find` and `read --offset/--limit` are the `terminal` skill; read that one if a search comes back empty. Two things specific to this corpus:

- It is over a thousand files, so **search it, do not browse it**. `ls` on `references/docs/` tells you almost nothing.
- Search matches with AND. Cerb's documentation is dense with shared vocabulary, so two distinctive words beat five ordinary ones. `--terms` is how you find out which words this corpus actually uses.

Then read only what you need: jump to the section, not the whole page.

## It is read-only, and that is deliberate

The volume mirrors what shipped. Do not try to write to it, and do not treat a gap as something to patch locally -- there is nothing here that would survive the next release.

If the documentation is genuinely missing or wrong, say so to the worker, with the nearest page's `url` and what you expected to find. Filing it is their call, not yours.
