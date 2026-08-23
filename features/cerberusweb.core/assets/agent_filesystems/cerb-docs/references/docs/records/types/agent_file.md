---
id: "docs-records-types-agentfile"
title: "Agent File Records"
url: "https://cerb.ai/docs/records/types/agent_file/"
summary: "An agent file is a single file stored inside an agent filesystem volume. Files are created and modified through the filesystem command set -- by an AI agent using its agent_terminal tool, or by a worker in the Setup Agent Filesystem Terminal -- rather than edited directly as records. Each file tracks its name, extension, size, content, and optional frontmatter parsed from the document. This page documents the Records API fields and search filters available on agent file records."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Agent File |
| **Name (plural):** | Agent Files |
| **Alias (uri):** | agent\_file |
| **Identifier (ID):** | cerb.contexts.agent.file |

- [Frontmatter](#frontmatter)
  - [What parses](#what-parses)
  - [When it doesn't parse](#when-it-doesnt-parse)
  - [Reading it back](#reading-it-back)

- [Bulk import](#bulk-import)
- [Records API](#records-api)
- [Search Query Fields](#search-query-fields)

An **agent file** is a single file stored inside an [agent filesystem](/docs/records/types/agent_filesystem/) volume.

In normal use you don't create or edit these records directly. Files are written through the filesystem command set – by an [AI agent](/docs/agents/) using its `agent_terminal` tool, or by a worker in **Setup » Developers » Agent Filesystem Terminal**. The record type exists so files are searchable, linkable, and reportable like any other record.

### Frontmatter

A Markdown file can open with a `---` fenced YAML block. It's parsed and stored separately in `frontmatter_json`, which lets a volume of documents carry structured metadata – a title, tags, an owner – that can be listed without opening each file.

`/runbooks/refund.md`:

```
---
title: Refund a customer
description: How to issue a refund and notify the customer.
tags:
  - billing
  - support
version: 3
reviewed: 2026-07-14
is_public: false
owner:
  team: Support
  email: support@example.com
---

# Refund a customer

1. Open the ticket and confirm the charge in the billing portal.
2. Issue the refund.
```

That file's `frontmatter_json` (stored minified, shown here expanded):

```
{
  "title": "Refund a customer",
  "description": "How to issue a refund and notify the customer.",
  "tags": ["billing", "support"],
  "version": 3,
  "reviewed": "2026-07-14",
  "is_public": false,
  "owner": { "team": "Support", "email": "support@example.com" }
}
```

Values keep their YAML types: `version` is a number, `is_public` is a boolean, a list becomes an array, and a nested map becomes a nested object. Dates are the exception – `reviewed` stays a string.

**`content` is the whole file, frontmatter block included.** Nothing is stripped, so reading this file returns the `---` fences and the YAML along with the body, and `size` counts them. `frontmatter_json` is an index _alongside_ the content, not a slice taken out of it -- which means an agent rewriting a file has to re-emit the block, or it's gone.

#### What parses

| Rule | Detail |
| --- | --- |
| Extension | Only `.md` and `.markdown`. Any other file stores nothing, however valid its block |
| Position | The opening `---` must be the very first thing in the file |
| Fences | Exactly three dashes. The closing fence may have trailing spaces, nothing else |
| Structure | The block must be a mapping or a list – a lone scalar is rejected |
| Size | A parsed block over 64KB is discarded rather than truncated |

#### When it doesn't parse

Nothing happens, and that's the thing to know: a malformed block, a block below line 1, or an unsupported extension stores `frontmatter_json` as null. **The file still saves normally, with no error and no warning.** If you added frontmatter and nothing came of it, this is why – check the extension first, then the position.

**A horizontal rule at the top of a file can be captured by accident.** Two `---` rules with text between them look exactly like a frontmatter block, and if that text happens to parse as YAML it's stored as metadata. A document opening with `---`, then `Note: this is prose.`, then `---` quietly gains a `Note` key. Rare, but silent when it happens.

#### Reading it back

There's no `frontmatter:` search filter, and no `content:` filter either, so this metadata isn't something you can query a worklist by. It's read through the [filesystem commands](/docs/agents/#filesystem-commands) instead:

```
ls /runbooks --fields title,tags
```

`--fields` prints per-key values as columns without opening any file, on both `ls` and `find`. A list joins with commas; a nested map joins its values. A missing key prints empty – and so does a `false` boolean, which makes the two indistinguishable in a listing.

Piped rows carry the decoded map as `meta`, which is always a map and empty rather than absent when a file has no frontmatter – so a fallback is safe across a volume where only some files carry it:

```
search refund | results|map(r => r.meta.title ?? r.path)
```

In [scripting](/docs/scripting/), the `frontmatter_json` placeholder is the **raw JSON string** rather than a map, so there's no per-key access through it.

The field is also writable through the [Records API](/docs/api/endpoints/records/), and an explicit value wins over the one parsed from the content – so it's possible, deliberately or otherwise, for the stored metadata to disagree with the file it describes.

### Bulk import

Files arrive in bulk by uploading a ZIP archive to a volume, which is imported in the background as a [queue job](/docs/records/types/queue_job/). Each import is tracked so a volume's card can show the history of past imports.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `content` | [text](/docs/records/fields/types/text/) | The file's contents |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `file_extension` | [text](/docs/records/fields/types/text/) | The file's extension |
| &nbsp; | `filesystem_id` | [number](/docs/records/fields/types/number/) | The ID of the parent [agent filesystem](/docs/records/types/agent_filesystem/) |
| &nbsp; | `frontmatter_json` | [text](/docs/records/fields/types/text/) | The file's parsed frontmatter, as JSON |
| &nbsp; | `id` | [number](/docs/records/fields/types/number/) | The ID of this file |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The file's name |
| &nbsp; | `size` | [number](/docs/records/fields/types/number/) | The file's size, in bytes |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Search Query Fields

These [filters](/docs/search/#filters) are available in agent file [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created` | date | When the record was created |
| `fieldset` | virtual | Filter by [custom fieldset](/docs/records/types/custom_fieldset/) |
| `file_extension` | text | The file's extension |
| `filesystem.id` | number | The parent [agent filesystem](/docs/records/types/agent_filesystem/) |
| `id` | number | The record ID |
| `name` | text | The file name (partial match) |
| `size` | number | The file's size, in bytes |
| `updated` | date | When the record was last modified |
| `watchers` | virtual | Filter by [watchers](/docs/watchers/) |

