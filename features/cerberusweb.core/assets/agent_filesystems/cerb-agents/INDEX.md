---
name: cerb-agents
description: Index of the Cerb agent skills -- what each one covers and when to load it.
---

# Cerb agent skills

Each skill under `skills/` is one `SKILL.md` holding knowledge that is portable across agents. Read the one you need, when you need it; nothing here is loaded for you.

There is no working directory. Use an absolute path (`/cerb-agents/skills/scripting/SKILL.md`) or the `@cerb-agents/...` shorthand.

Your system prompt may name one of these as a **precondition** -- "before you X, read Y". That is an order of operations, not a recommendation: read it before you produce anything, not after you are corrected. A skill exists because the house convention differs from the sensible default, so being confident you already know how to do the work is exactly the case it is there for.

## The skills

| Skill | Load it when |
| --- | --- |
| `skills/terminal/SKILL.md` | Before your first search comes back empty. The terminal's commands, absolute paths, why `search` matches with AND, the `\|` Twig pipeline, and `/tmp` as a compute surface. |
| `skills/kata/SKILL.md` | Before writing any KATA, anywhere -- automations, toolbars, sheets, charts, workflows, policies. Indentation, quoting, and comments do NOT work like YAML's. |
| `skills/automations/SKILL.md` | Writing or editing an automation: KATA command shape, naming, the correctness rules that bite, least-privilege policies. |
| `skills/scripting/SKILL.md` | Writing the contents of a scripting value -- Twig plus Cerb's own filters, functions, tests, and commands. |
| `skills/search-queries/SKILL.md` | Writing a worklist query, a saved search, or a `record_query:`. The `filter:expression` grammar, one syntax per filter type. |
| `skills/docs/SKILL.md` | Looking anything up in `cerb-docs`. What lives in which directory, the frontmatter every page carries, and when the docs are the authority versus this install. |
| `skills/records/SKILL.md` | Before naming any field or filter. `cerb records` reports THIS install's types, searchable filters, and writable fields -- and those last two are separate namespaces. |
| `skills/data-queries/SKILL.md` | Choosing a data query's `type:`, `format:`, or aggregation. |
| `skills/icons/SKILL.md` | Drawing or editing an icon. The four pattern families and the mask-image constraints that make ordinary SVG instincts wrong. |
| `skills/mail-replies/SKILL.md` | Drafting a customer-facing reply. Interleaved quoting, the `#signature` token, house typography. |

Skills compose. Writing an automation that searches records means `kata` for the language, `automations` for the command shape, `records` for the filter keys, and `search-queries` for the grammar -- four reads, not one oversized file.

## You load a skill; you do not become another agent

Loading a skill gives you knowledge, not a new identity or new tools. Your role and your tool list come from the system prompt and stay fixed for the whole conversation. If a job genuinely belongs to a different role, spawn a subagent with that role rather than trying to adopt it mid-chat.
