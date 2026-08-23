---
name: automations
description: Writing Cerb automations in KATA -- naming, command shape, the correctness rules that bite, and least-privilege policies. Load it before writing or editing any automation script or policy.
---

# Cerb automations (KATA)

An automation is a KATA script bound to a trigger, plus a policy that grants the script its privileges. The two move together: a script that gains a privileged command stops working until the policy allows it.

## Knowledge — use the mounted documentation, don't guess

`cerb-docs` contains the Cerb documentation for KATA syntax and annotations, automation commands, triggers, events, policies, record types, search queries, toolbars, workflows, and guides. Consult it before writing anything you're unsure of — especially `@cerb-docs/references/docs/automations.md`, `@cerb-docs/references/docs/kata.md`, and `@cerb-docs/references/docs/records/types.md`.

Search `cerb-docs` for the exact command, trigger, event, field, or filter you need rather than inventing syntax. Cite what you relied on when it clarifies your change.

## Naming automations

- An automation's `name` is an IDENTIFIER, not a title. It's how the automation is addressed everywhere else — `uri: cerb:automation:example.math.sum` in function:, toolbars, timers, and data queries. Human-friendly wording belongs in `description:`, not in the name.
- Allowed characters: lowercase letters (a-z), numbers (0-9), underscores (`_`), and dots (`.`) — the same set Cerb requires for plugin and extension IDs (@cerb-docs/references/docs/plugins.md). No spaces, dashes, or slashes.
- Dots delimit a namespace hierarchy, broadest to most specific. The conventional shape is `<namespace>.<area>.<thing>` -- e.g. `example.math.sum`, `example.translate.function`, `example.automationTimer.createDailyTasks`, `wgm.example.openai`.
- The FIRST segment is a namespace unique to the author/org — conventionally based on a domain you own (wgm, acme) so names stay globally unique. Reserve `cerb.` for Cerb itself and `example.` for documentation samples.
- Middle segments group related automations: by trigger (automationTimer, interactionWorker), by feature area (mail, billing), or by calling subsystem. Keep grouping consistent — prefix search (`name:example.mail.*`) is how people find them in worklists.
- The LAST segment names the specific behavior; camelCase is conventional there (createDailyTasks, openai). Prefer a verb phrase for automations that act (sendWelcomeEmail) and a noun for ones that fetch (openTicketCount).
- Match the namespace and style already used in this install rather than introducing a new scheme; name a new function in the same namespace as its caller. Renaming breaks every `cerb:automation:` URI pointing at it, so call out affected call sites when you propose a rename.
- Don't confuse this with command aliases inside a script (`set/a:`, `await/step:`), which allow only letters, numbers, and underscores — no dots.

## Automation correctness rules (common pitfalls — honor them)

These are the automation-specific ones. The language itself — indentation, quoting, comments, annotations, unique sibling keys — is the `kata` skill, and its indentation rule bites here constantly: a bare `key:` with indented lines under it is an OBJECT, so any multi-line or prose value needs `@text:` on its key.
- Every automation starts at start:. The working dictionary begins as a copy of inputs and is mutated as it runs.
- await: resumes AFTER the await, not from start:. Commands above the await do not re-run (their results persist in the continuation). A per-item/paging UI must put the await INSIDE a while: loop, or the next resume falls off the end and returns nothing. Build source lists before the loop.
- decision:/outcome: use first-match semantics; an outcome without if@bool: is the default. Write conditions as if@bool: {{...}}
- Never stack @raw inside an already-raw block — `script@raw:`, `event_kata@raw:`, and `policy_kata@raw:` are where this comes up. Use the normal annotations inside them (`if@bool:`, `set:`); `if@raw,bool: {{...}}` makes the condition never match.
- For Twig arrays in set:, match the annotation to the rendered text: pipe through `|json_encode` for @json; prefer native KATA (0:, 1:, …) for static arrays of objects.
- Choose the right output field for the trigger's return keys, and match inputs/ placeholders to the trigger (e.g. interaction.worker exposes worker_* directly).
- icon: values must come from the Cerb icon set — invented names silently render nothing. When unsure, reuse an icon already present in the automation rather than guessing a new name; the `icons` skill covers the set and its naming conventions.

## Command shape — action commands take `output:` and `inputs:`

Action commands do NOT take their parameters directly. Each takes an `output:` (the placeholder that receives the result — name one whenever you need the result) plus an `inputs:` block holding every documented parameter, with the handlers `on_simulate:`/`on_success:`/`on_error:` as siblings of those keys:

```
  start:
    record.create/newTask:
      output: new_task
      inputs:
        record_type: task
        fields:
          title: This is a new task
          status: open
      on_success:
        return:
          task_id: {{new_task.id}}
```

Wrong — parameters hung directly off the command. `record_type:`/`fields:` are not recognized there, so the command gets no inputs and fails:

```
  record.create:
    record_type: task
    fields:
      title: This is a new task
```

This shape applies to record.*/records.update:, http.request:, data.query:, function:, storage.*, file.*, kata.parse:, llm.chat:/llm.agent:/llm.embed:/llm.router:, queue.*, metric.increment:, email.parse:, encrypt.pgp:/decrypt.pgp:, api.command:, and var.set:/var.push:/var.unset:/var.expand:.

The exceptions are the state and flow keywords, which take their parameters directly and have no output:/inputs:  — return:, error:, await:, tool.return:, set:, decision:/outcome:, repeat:, while:, and log:/log.warn:/log.error:/log.alert: (e.g. `log: This is a notice`).

Results read off the output placeholder after the call ({{new_task.id}}), and an error reads off that same placeholder inside on_error: ({{new_task.error}}) — no separate output key is needed for errors. Look up a command's exact input table in `@cerb-docs/references/docs/automations.md` instead of guessing parameter names.

## Available Commands (quick reference)

Each command has its own page at `@cerb-docs/references/docs/automations/commands/<command>.md` -- `read` it directly for full syntax, parameters, and examples. Go straight there rather than searching: a name like `set` or `return` appears in every corner of the docs, and the search costs turns to land where one `read` would have. Six have no page of their own -- `start`, `records.update`, `tool.return`, and the `log.warn`/`log.error`/`log.alert` variants (covered by `log.md`); for those, and for the overview, read `@cerb-docs/references/docs/automations.md`.

**State:** `return:` (success), `error:` (fail), `await:` (pause/continuation), `tool.return:` (answer a tool call -- only valid inside an `llm.agent:` `on_tool:` branch)

**Flow:** `decision:`/`outcome:` (first-match branching), `repeat:` (iterate array), `while:` (loop)

**Variables:** `set:`, `var.set:` (key path), `var.push:` (append), `var.unset:` (remove), `var.expand:` (lazy-load)

**Records:** `record.create:`, `record.get:`, `record.search:`, `record.update:`, `record.upsert:`, `record.delete:`, `records.update:` (one call updating many records by id -- reach for it instead of wrapping `record.update:` in a `repeat:`)

**HTTP:** `http.request:` (GET/POST/PUT/PATCH/DELETE, streaming, auth)

**Data:** `data.query:` (worklist queries, custom formats)

**Functions:** `function:` (call another automation)

**Storage:** `storage.get:`, `storage.set:`, `storage.delete:` (key/value, optional TTL)

**Files:** `file.read:` (attachments/resources), `file.write:` (automation resources)

**KATA:** `kata.parse:` (parse KATA with placeholders)

**LLM:** `llm.chat:` (single-turn), `llm.agent:` (conversational + tools), `llm.embed:` (vectors), `llm.router:` (resolve a model from requirements instead of naming one)

**Queues:** `queue.push:`, `queue.pop:` (message queues)

**Metrics:** `metric.increment:` (time-series samples)

**Email:** `email.parse:` (MIME → ticket)

**Encryption:** `encrypt.pgp:`, `decrypt.pgp:`

**API:** `api.command:` (internal Cerb commands)

**Logging:** `log:`, `log.warn:`, `log.error:`, `log.alert:`

**Simulation:** `simulate.success:`, `simulate.error:` (mock outputs in `on_simulate:`)

## Policies (principle of least privilege)

- The policy must allowlist every command the script uses.
- Each command entry holds only allow/deny children (optionally /named).
- Guard by record type or URL with the deny-first idiom, then `allow@bool: yes`. Do NOT try a positive allow/...@bool with a ternary on inputs.record_type — it does not work. Default is deny when no rule matches.
- Add callers: only when restricting which toolbars may invoke an interaction.

The deny-first idiom:

```
deny/type@bool: {{inputs.record_type is not record type ('task')}}
allow@bool: yes
```

## Style

- Keep KATA idiomatic — see the `kata` skill for the language's own conventions.
- Flag bugs or risky patterns you notice even when unasked.

## Related skills

- `kata` — the language this is written in: the indentation rule, quoting, comments, annotations.
- `scripting` — the Twig inside a `{{...}}` placeholder or a `script@raw:` value.
- `search-queries` — writing a `record_query:` for `record.search:` / `data.query:`.
- `records` — confirming a record type's alias, its writable fields, and its search filters.
- `data-queries` — the `data.query:` command's own query language.
