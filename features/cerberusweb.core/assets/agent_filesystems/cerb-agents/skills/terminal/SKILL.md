---
name: terminal
description: The agent terminal -- its commands, absolute paths, how `search` actually matches, the `|` Twig pipeline, and /tmp as a compute surface. Load it before your first search comes back empty.
---

# The agent terminal

Your terminal tool runs ONE command line per call -- verb, arguments, and flags together, exactly as you would type them at a prompt. Everything below is a COMMAND, not a tool of its own: there is no separate `search` tool, only `search ...` typed into the terminal.

It is NOT a Unix shell. It has only the documented commands -- `ls`, `find`, `search`, `read`, `write`, `append`, `edit`, `copy`, `rm` -- plus a trailing `|` pipeline. A few Unix names work as aliases -- `cat` (read), `grep` (search), `dir` (ls), `cp` (copy) -- and everything else errors, `echo` and `awk` included.

`search` needs a fulltext index, which only a mounted volume has. With nothing but `/tmp` you do not have it -- filter `/tmp` with a pipeline instead.

## Paths

Write every path in full. `cd` exists, but assume it does not stick: your terminal call starts at `/` every time, so a `cd` is gone by your next call. Use an absolute path (`/cerb-docs/references/docs/kata.md`) or the `@<filesystem>/path` shorthand (`@cerb-docs/references/docs/kata.md`). A mount is read-only unless its overview says otherwise; `/tmp` is always read-write.

## Searching the mounted filesystems

The `search` command matches file CONTENTS with AND, not OR -- every word in the query must appear in the same file. Piling on keywords narrows to zero hits fast, and zero hits is NOT evidence the topic is undocumented.

- Start with the one or two most distinctive terms, then narrow with `--path` or `--ext` instead of adding more words.
- **`--terms` is the OR view, and it is how you find the right word.** It counts how many files contain EACH word, ranked, instead of searching for all of them: `search --terms school college academic university tuition`. One call, any number of synonyms, no reading. Take the highest count, require it with `+`, and throw the next set of words at it: `search --terms +college tuition fees pricing`. Repeat until the word list is good, THEN run the real `search`.
- **A wildcard or stem term lists the words it covers**, inline on one indented line beneath it: `auto*` reports `automations (476) automation (374) automatically (228) ...`. That is how you learn the vocabulary this corpus actually uses instead of guessing at it; a trailing `...` means the list was capped.
- A `+` term is echoed as a heading above the counts; every count below it is *within* the files that contain it, so numbers collapse fast. That collapse is the signal -- keep the words that survive it.
- A miss falls back to this view automatically, and keeps any `+` you already had, so a failed AND search still tells you which words exist alongside it.
- A `-` instead of a count means the index skips that word as too common (`the`, `not`, `can`, `com`, `www`, ...) -- it was never searched for, which is not the same as finding nothing.
- Run separate searches for alternatives; there is no OR.
- **A search returns the 25 most relevant files, not every match.** `--top <n>` raises or lowers that cap and `--top 0` returns every match unranked, so a full list is one flag away. Don't report a capped list as the whole answer.
- Use `find` to match names and `search` to match contents. Add `--lines` only after you've picked a file worth reading in context.
- **`find` also carries every Markdown file's frontmatter, so you can select on metadata without opening anything.** Each row has its keys under `meta`, and `--fields title,description` prints chosen ones as columns: `find *.md --fields name | files|filter(f => f.meta.name starts with "cerb")`. Listing never opens a file, so this costs nothing.
- Prefer `search`/`find` to locate a file, then `read` only the part you need (`--offset`/`--limit`).

## /tmp -- a compute surface, not an overflow buffer

`/tmp` is a read-write scratch area. Reach for it whenever you'd otherwise scan a long piece of text by hand, or need to hold intermediate work across steps.

- Write an arbitrary string to a file (`write /tmp/scratch.txt`), then read it back through a trailing Twig filter chain to count, filter, reshape, or diff it -- for example staging alternate drafts, or reducing a large document to just the lines that matter, before committing a final version anywhere.
- It is per-session, and it is NOT indexed by `search`. Filter it with a pipeline rather than searching it.
- A command whose output is too large to return is saved there and referenced by path.

## The `|` pipeline

Pipe ANY command through Twig filters with a trailing `|` (`lines`, `filter`, `map`, `length`, `column`, ...). The pipeline runs on the FULL output before any truncation, so it is how you reduce a big result to just what you need instead of paying for the whole thing in context.

```
read /tmp/scratch.txt | lines|filter(l => "field" in l)
search widgets | results|map(r => r.meta.title ?? r.path)
ls /docs | files|filter(f => not f.is_dir)|column("name")
```

`lines` preserves original line indices as keys. Each command sets its own row variable -- `lines`, `results`, `files` above -- and that is what makes a pipe iterate ROWS rather than text. For a longer transform than fits on one line, put a Twig template in `script` instead of a trailing `|`.

**This is Cerb scripting, not a pipeline language of its own** -- the same Twig, the same filters, functions and operators you would write anywhere else in Cerb. Read `@cerb-agents/skills/scripting/SKILL.md` for what is available rather than guessing at a filter name here.

## `cerb` -- the command line into this installation

Some agents also have `cerb`, a command line into THIS Cerb install rather than into files. It reports what this installation actually has, custom record types and fieldsets included, which the documentation cannot. Run `cerb help` to see what it answers, and `cerb <topic> help` for one topic's usage. Its rows pipe like any other command's.
