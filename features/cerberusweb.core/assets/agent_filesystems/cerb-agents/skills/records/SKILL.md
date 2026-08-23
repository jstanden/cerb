---
name: records
description: Introspecting THIS Cerb install's record types with the `cerb records` command line -- aliases, searchable filters, writable fields, and why those last two are separate namespaces. Load it before naming any field or filter.
---

# Record types, filters, and fields

## The `cerb` command line — this install, not the generic baseline

Your terminal tool carries more than files: `cerb` is a command line into THIS Cerb install, so you can confirm a record type's alias and its real keys instead of guessing. Prefer it over the docs for lookups — it's one command, and it reflects this installation, including its custom record types and custom fieldsets. The docs describe the generic baseline: they can omit what someone is actually looking at, and list types a plugin here doesn't provide.

`cerb records <sub> help` prints usage; rows pipe like any command.

- `cerb records types [--filter <text>]` — every record type, with the `alias` that `record.*`, a data query's `of:`, and `links.<alias>:` all take. `cerb records types --filter task` → `task`, `task_project`.
- `cerb records filters <type>[,<type>...]` — **the keys you can SEARCH by.** `cerb records filters ticket` returns a `key`/`type` table: `subject` Text, `importance` Number, `created` Date, `owner.id` Worker, `group.id` Record, `status` Virtual, `messages.count.in` Number, `spam.score` Decimal, `links.<record-type>` Record, plus this install's custom fieldset keys (e.g. `testFieldset.date`).
- `cerb records fields <type>[,<type>...]` — the keys you can WRITE, i.e. what `record.create`/`record.update` accept, with a required marker and notes. These are NOT query filters; reach for it when someone asks what a field is called on the record itself.

## Filters and fields are separate namespaces

**Never lift a key from `fields` into a query.** On `task` the writable field is `owner_id` but the search filter is `owner.id`; writable `status_id` takes `0`/`1`/`2` while the `status` filter takes `open`/`waiting`/`closed`.

## The `type:` column tells you which syntax applies

Text, Number, Decimal, Date, Seconds, Yes/No, Geo, Worker, Record (a chooser — numeric ids), Search (deep search), Virtual (its own vocabulary, e.g. `status:open`, `watchers:me`). The `search-queries` skill has one expression form per type.

## Pipe rather than eyeball a long table

```
cerb records filters ticket | filters|filter(r => r.type == "Date")|column("key")
cerb records types | types|filter(r => r.is_custom)|column("alias")
```

## Docs as fallback

`cerb records` is the authority for keys. Fall back to `cerb-docs` for the things it doesn't cover — background and semantics — and cite what you relied on:

- `/cerb-docs/references/docs/records/types.md` — the baseline record type list (the index), plus `types/<type>.md` for one type's background (e.g. `types/ticket.md`). Each type page lists its Records API fields, dictionary placeholders, Search Query Fields, and worklist columns.
- `/cerb-docs/references/docs/records/fields/types/` — what a field TYPE means: `text`, `number`, `float`, `timestamp`, `boolean`, `context`, `links`, `url`, `object`, `image`, `extension`.
