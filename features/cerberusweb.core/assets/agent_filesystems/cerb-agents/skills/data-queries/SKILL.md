---
name: data-queries
description: Cerb data queries -- the `key:value` language behind worklists, metrics, charts, and reports. Load it when choosing a `type:`, a `format:`, or an aggregation.
---

# Cerb data queries

The text-based `key:value` query language used for worklists, metrics, charts, and other data.

## Essentials

- A data query is a set of `key:value` pairs. **No space after the colon.** `type:worklist.records` is one pair; `type: worklist.records` is the key `type` with an empty value plus a stray bare term, which is not what you meant and usually will not error.
- Quote a value only when it contains a space. `of:ticket`, `limit:25`, and `sort:-updated` need no quotes; `query:"status:open"` does. Quotes are not what makes something a string, and unnecessary ones are just noise to read past.
- Group a key's children with `(` `)`.
- Pairs are separated by whitespace, and a newline IS whitespace, so break a long query across lines wherever it reads best:

```
type:worklist.records
of:ticket
query:(
  status:open
  sort:-updated
  limit:10
)
format:dictionaries
```
- Every query MUST include a `type:`. The available types — e.g. `worklist.records`, `worklist.subtotals`, `worklist.metrics`, `worklist.series`, `worklist.xy`, `metrics.timeseries`, `record.fields`, `record.filters`, `record.types`, `ui.icons` — are documented under `/cerb-docs/references/docs/data-queries/`; `data.query.types` lists them all.
- A `format:` prepares the response for its consumer (e.g. `dictionaries`).
- For worklist queries, confirm the record type's valid search filters (their names, example values, and which are sortable) and its fields before writing `query:`/`sort:` — don't assume field names. The `record.filters` and `record.fields` query types exist precisely for this discovery; point people at them when useful.
- Reach for `worklist.subtotals` / `metrics.timeseries` / `worklist.series` for aggregation and reporting rather than hand-rolling; use `worklist.records` for a filtered list of record dictionaries.

## Untrusted input goes in `query_params:`, never in the query text

Interpolating someone's words straight into `query:` is an injection: their text is parsed as query syntax, so a value can add filters, widen the result set, or reach records they should never see.

Pass it as a binding instead. `data.query:` takes a `query_params:` block, and the query references each entry as `${name}`:

```
data.query:
  output: results
  inputs:
    query_params:
      term: {{search_text}}
    query@text:
      type:worklist.records
      of:ticket
      query:(subject:${term} status:open)
```

A substituted value is forced to a quoted string token, so whatever it contains -- colons, parentheses, another `key:value` -- lands as one literal value and cannot become syntax. `record.search:` and `record.upsert:` take the same thing under `record_query_params:`.

## Ground truth — read the docs, don't guess

The `cerb-docs` filesystem is the full, authoritative Cerb documentation. When a query type, key, field, search filter, format, or icon is in question, look it up before you write it, and cite what you relied on. Invented field or filter names silently return nothing (or error), so confirm them.

- `/cerb-docs/references/docs/data-queries/` — one page per type (e.g. `worklist/records.md`, `worklist/subtotals.md`, `record/filters.md`).

## Related skills

- `records` — confirm a record type's alias for `of:`, and its real filter keys, against THIS install rather than the baseline docs.
- `search-queries` — the grammar for the `query:` you put inside a worklist data query.
