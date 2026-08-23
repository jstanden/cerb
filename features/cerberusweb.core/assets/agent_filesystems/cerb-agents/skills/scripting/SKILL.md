---
name: scripting
description: Cerb scripting -- Twig at the base, extended with Cerb commands, functions, filters, and tests. Load it whenever you write or debug the contents of a scripting value, wherever that value lives.
---

# Cerb scripting

Cerb scripts are written in **Twig** -- the PHP-ecosystem templating language (twig.symfony.com), not a lookalike. Cerb embeds a real Twig environment, sandboxes it, and extends it.

**Standard Twig works.** Its syntax, its operators, and its built-in filters, functions, and tests behave as documented upstream -- `upper`, `join`, `default`, `merge`, `slice`, `date`, `json_encode` and the rest are all there. Reach for what you know about Twig first; you don't need Cerb documentation to justify a standard feature.

**What the sandbox removes is template composition and introspection.** These tags are the complete allowed set:

```
apply  do  filter  for  if  sandbox  set  verbatim  with
```

So there is no `{% macro %}`, `{% include %}`, `{% import %}`, `{% from %}`, `{% use %}`, `{% extends %}`, `{% embed %}`, or `{% block %}`, and no `include()`, `source()`, `template_from_string()`, `constant()`, or `dump()`. A scripting value is one self-contained template with nothing to compose against and no way to reach into the host -- which is the same reason it can't reach the automation around it.

## Scope: a scripting value is ONE Twig template, not an automation

A scripting value is the body of one template. It is plain text with Twig tokens in it, and nothing else. Whatever it holds is rendered as a template and evaluates to a single string.

- Do NOT write KATA. No `start:`, no `set:` / `decision:` / `outcome:` / `await:` blocks, no `key: value` trees, no `@bool` / `@int` / `@text` / `@key` annotations, no `&refs`. That is the syntax of the automation AROUND this field, not of the field itself; emitting it here just prints literal garbage.
- Do NOT wrap the value in markdown code fences, a YAML/KATA key, or any indentation scaffolding. The field takes the raw template text exactly as it should appear.
- Do NOT put explanatory prose in the value — explanations go in chat. Only `{# ... #}` Twig comments belong in the template.
- Control flow INSIDE the value is Twig: {% if %}, {% for %}, {% set %}. Control flow BETWEEN steps — running commands, awaiting input, branching across records — is the automation's job, in KATA, outside this field. If the goal actually needs that, say so rather than writing KATA here.
- Produce exactly one template per request: the complete new value of this one field, not several alternatives pasted together.

## Core model

- Everything typed is literal text output until a special token appears.
- {{ ... }} prints an expression; {% ... %} runs a statement/command; {# ... #} is a comment.
- Variables are set with {% set x = ... %} and are scoped to the current template only — they do not carry across separate actions/templates. A variable defined solely inside a loop is not visible after it; define it before the loop to persist values across iterations.
- **For multi-line text, prefer the block form** {% set body %}...{% endset %} over assigning a quoted string. The body is ordinary template text — line breaks, quotes and apostrophes need no escaping, and {{ ... }} and {% ... %} work inside it — so a paragraph reads as a paragraph instead of a concatenation.
- Values pass through filters via | (e.g. {{name|upper}}), and filters stack left to right (e.g. {{x|default('there')|upper}}).

## What Cerb adds on top

Cerb ships a large set of its own filters, functions, and tests alongside Twig's, plus commands. They are not upstream Twig, so look them up rather than recalling them, and don't assume one is portable to a Twig project elsewhere:

- `/cerb-docs/references/docs/scripting/filters.md` — the filter index, with a page per filter under `filters/` (82 of them).
- `/cerb-docs/references/docs/scripting/functions.md` — the function index, with a page per function under `functions/` (57 of them).
- `/cerb-docs/references/docs/scripting/tests.md` — the tests usable after `is` / `is not`.
- `/cerb-docs/references/docs/scripting/commands.md` — the `{% %}` commands Cerb adds.

The rule runs one way only. A standard Twig feature needs no lookup; a name you can't place in stock Twig probably belongs to Cerb, so confirm it in the reference above before you use it.

Placeholders are pre-set variables supplied by the surrounding context (e.g. automation event inputs). Identify which are available before using them.

## Ground truth (always verify against the mounted docs)

- `/cerb-docs/references/docs/scripting.md` — overview and topic index.
- `/cerb-docs/references/docs/scripting/` — per-topic pages: variables, strings, arrays-objects, dates, conditional-logic, operators, loops, regex, json, xml, commands, functions, filters, tests (plus `functions/` and `filters/` subdirectories for individual entries).
- `/cerb-docs/references/solutions/automations/` — worked, copy-pasteable scripting solutions (e.g. loop-search-results, filter-array-values, map-array-values, json-encode-decode). Prefer adapting one of these to reinventing a snippet.

For how a scripting value fits into an automation, workflow, snippet, or bot — and which placeholders a given trigger/event supplies — load the `automations` skill. Read it to understand the surroundings; never copy its KATA into a scripting value.

## Method

1. Confirm the CONTEXT (automation, snippet, bot behavior, HTTP action, etc.), since that determines available placeholders and expected output.
2. Look up exact names/signatures of filters, functions, and tests in the docs rather than guessing — Cerb's set differs from vanilla Twig. Prefer search/find to locate, then read only the needed section.
3. Write idiomatic, minimal templates — text plus Twig tokens, nothing else. Show expected rendered output when helpful.
4. Flag scope pitfalls (loop-local variables, per-action scope) and empty-value handling (default filter) proactively.

## Style

- Be concise and practical. Prefer a working example over prose.
- When you catch yourself about to type a line ending in `:` with children indented under it, stop — that is KATA, and it belongs to the automation, not to this field.
- State any assumptions and offer the obvious next step (e.g. wrap in an automation input, collect into an array, verify a filter name).
