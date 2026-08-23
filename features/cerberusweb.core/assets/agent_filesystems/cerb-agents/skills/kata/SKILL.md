---
name: kata
description: KATA, the indentation-based configuration language Cerb uses for automations, toolbars, sheets, charts, workflows, policies, and more. Load it before writing any KATA -- the indentation and quoting rules are not YAML's.
---

# KATA

KATA is Cerb's configuration language: keys, values, and indentation. It looks like YAML and is not YAML. Everything below applies **everywhere Cerb uses KATA** -- automation scripts and policies, toolbars, sheets, charts, workflows, interaction elements, agent config, the schema file -- not just automations.

Full reference: `/cerb-docs/references/docs/kata.md`.

## The indentation rule -- read this one twice

**A bare `key:` with anything indented under it is an OBJECT. Always.** There is no exception and no heuristic.

To put a multi-line VALUE under a key, the key must carry an annotation. `@text:` is the generic one, but any annotation works -- `@int:`, `@bool:`, `@json:` all accept a value indented on the following lines.

```
message@text:
  Subject: Order 123
  Status: open
```
→ the string `"Subject: Order 123\nStatus: open"`.

Drop the annotation and the same lines mean something completely different:

```
message:
  Subject: Order 123
  Status: open
```
→ the object `{"message": {"Subject": "Order 123", "Status": "open"}}`.

**That second one does not error.** It parses cleanly into the wrong shape, and the failure surfaces much later as an empty placeholder or a command that received an object where it wanted a string. Prose is especially prone to it, because a colon anywhere in a sentence is enough -- `Subject:`, `Note:`, a time like `9:00`.

You are only warned when the indented text *can't* be read as keys:

```
note:
  See https://cerb.ai/docs/ for more
```
→ `Unexpected syntax (line 2)`. Same mistake, but this one is loud. Don't rely on it.

**So: any value that spans lines, or that contains prose, needs an annotation on its key.** When in doubt, `@text:`.

## Strings are never quoted

Values are literal text from the colon to the end of the line. Quotes are not syntax -- they become part of the value.

```
name: Jeff          →  "Jeff"
name: "Jeff"        →  "\"Jeff\""      ← the quotes are IN the string
name: 'Jeff'        →  "'Jeff'"
```

Never wrap a value in quotes to "make it a string". It already is one. Quote only when the quotes are genuinely part of the text you want.

## Comments must be on their own line

A `#` only starts a comment at the beginning of a line (after indentation). There are no trailing comments.

```
# This is a comment.
name: Jeff
```

```
name: Jeff # this is NOT a comment
```
→ the value is `"Jeff # this is NOT a comment"`.

Note that this cuts the other way inside an annotated text block: there, a `#` line *is* content, because the whole block is a literal value.

## Types come from annotations

All values are text by default. Convert explicitly:

`@bool` `@int` `@float` `@csv` `@json` `@list` `@date` `@key` `@text` `@raw` `@optional`

Annotations combine with a comma (`@optional,text:`). `@optional` is honored by whatever CONSUMES the KATA rather than by the parser, so what it does depends on the command -- check that command's documentation rather than assuming it drops empty values.

**Never stack `@raw` inside an already-raw block.** Inside `script@raw:` / `event_kata@raw:` / `policy_kata@raw:`, use the normal annotations (`if@bool:`, `set:`). Writing `if@raw,bool: {{...}}` makes the condition never match.

## Keys must be unique among siblings

A repeated key silently clobbers the earlier one. Where you need the same command twice under one parent, give each an alias with `/`:

```
set/init:
set/increment:
await/step:
```

The part before the `/` is what Cerb dispatches on; the part after is yours, and it may contain only letters, numbers, and underscores -- no dots.

## References

`&name:` defines a reusable block; `@ref` uses it. Useful for a long value you'd otherwise repeat, and for keeping a big text block out of the middle of a script.

## House style

Two-space indentation, spaces not tabs. Annotate types rather than relying on a reader to infer them.
