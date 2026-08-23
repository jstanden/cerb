---
name: search-queries
description: Cerb's search query language -- the filter:expression grammar, one syntax per filter type, deep search, boolean groups, and sorting. Load it before writing a worklist query, a saved search, or a `record_query:`.
---

# Cerb search queries

The text language Cerb uses to filter records in worklists, saved searches, API requests, and automations (`record_query:` in `record.search:` / `data.query:`).

Writing one is two jobs: knowing the GRAMMAR (this skill) and knowing which filter keys a record type actually exposes (the `records` skill — `cerb records filters <type>` is the authority, and it reports THIS install including its custom fieldsets).

## Method

1. Identify the target record type. Never assume it; if it's genuinely ambiguous, ask, otherwise state what you assumed.
2. Look up the filters that record type actually exposes before writing them — `cerb records filters <type>` is the fastest authority. Never invent a filter name; an unknown filter fails the whole query.
3. Match the expression syntax to the filter's TYPE (text, fulltext, number, boolean, date, chooser, record/deep-search, links, watchers, nullness).
4. Give the query, then explain each non-obvious filter in one line.
5. Offer a tighter or looser variant when the result set is likely too broad or too narrow (a date bound, a `sort:`, a deep search instead of a guess).

## Syntax cheat sheet (verify specifics against the docs)

- A query is space-separated `filter:expression` pairs, AND-ed by default.
- text: `status:open` `subject:"a phrase"` `mask:abc*` `color:[red,green]` `status:!open`
- fulltext: bare terms, `content:("exact phrase" other terms)`, `text:!(not these words)`
- numbers: `age:35` `priority:!1` `age:>21` `order:<=100` `importance:25...75` `importance:[0,50,75]`
- booleans: `checkbox:y` / `isAdmin:n` (yes/y/true, no/n/false)
- dates: a bare value is a SINCE bound -- `created:"-1 month"` means that point up to now. Join two with `to` to bound both ends: `created:"January 1 to June 30"`, `created:"-1 year to -6 months"`, `created:"big bang to first day of this month"`.
- **date shortcuts -- one word for a whole period, so reach for these before writing a range.** `created:"this year"`, and likewise `last year` / `next year`, `this month` / `last month` / `next month`, `this week` / `last week` / `next week`, plus `today`, `yesterday`, `tomorrow`. Each expands to a bounded range rather than a since-bound (`this year` is Jan 1 to Dec 31; `today` is 00:00:00 to 23:59:59), which is the difference that catches people out.
- dates, advanced: `created:(since:"-1 week" until:now months:Jan,Feb,Mar days:Weekdays times:9a-5p)`
- nullness: `sla.level:null` / `checkbox:!null`
- choosers (`*.id:`): `group.id:1` `group.id:[1,2,3]` — numeric expressions
- links: `links:ticket` `links.ticket:(mask:a*)`
- watchers: `watchers:me` `watchers:any` `watchers:none` `watchers:kina,karl` `watchers:1,2,3`
- deep search: the expression is a full query on the linked type — `group:(name:S*)`, chainable `messages.first:(sender:(org:(region:Europe)))`, negatable `group:!(name:S*)`
- boolean groups: `OR` between filters, parentheses to group, `!(...)` to negate
- sorting: `sort:subject` `sort:-updated` `sort:-importance,created`
- search indexes expose their own `filter:` keyword supporting `*` (prefix), `~` (stemming), and `top:N` ranking — e.g. `title:(11.1* release~ top:10)`

## Ground truth

`/cerb-docs/references/docs/search.md` — the complete query language, and the authority for SYNTAX. `cerb records` gives you keys and types, not grammar.

For runtime discovery inside an automation, `/cerb-docs/references/docs/data-queries/record/filters.md` documents the `type:record.filters of:<type>` data query.

Before telling anyone a filter doesn't exist, confirm with `cerb records filters` — then offer the nearest alternative, usually a deep search through a link.

## Output

Present a query for the UI or a saved search as a single line. For an automation, show it in KATA context under `record_query:`. When someone's input is interpolated into a query, use `record_query_params:` with `${name}` placeholders rather than pasting raw values into the query text.
