---
id: "docs-records-types-taskproject"
title: "Task Project Records"
url: "https://cerb.ai/docs/records/types/task_project/"
summary: "A task project groups related tasks together and shares ownership across them. Tasks gain a project and an active flag, and a project's permissions carry through to the tasks inside it. Task projects also back the Daily Task Board workspace tab, where each project can be given an accent color and ordered. This page documents the Records API fields, search filters including the tasks deep filter, and the Tasks distribution bar column available on task project worklists."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Task Project |
| **Name (plural):** | Task Projects |
| **Alias (uri):** | task\_project |
| **Identifier (ID):** | cerb.contexts.task.project |

- [Ownership](#ownership)
- [Closing a project](#closing-a-project)
- [The Tasks column](#the-tasks-column)
- [Daily Task Board](#daily-task-board)
- [Records API](#records-api)
- [Search Query Fields](#search-query-fields)

A **task project** groups related [tasks](/docs/records/types/task/) together and shares ownership across them. A task's permissions derive from its project, so granting someone access to a project grants it for the work inside it.

### Ownership

A project is owned by an actor – a [worker](/docs/records/types/worker/), [group](/docs/records/types/group/), [role](/docs/records/types/role/), or the application itself – using the `owner__context` and `owner_id` pair. That ownership is what the tasks inside the project inherit.

### Closing a project

`is_closed` marks a project finished. Closed and unreadable projects are dropped from the [Daily Task Board](/docs/workspaces/#daily-task-board) gracefully, rather than erroring.

### The Tasks column

Task project [worklists](/docs/worklists/) offer a 'Tasks' distribution bar column showing how the project's tasks are split between **done**, **stashed**, **todo**, and **in-progress** – so a project that has quietly stalled is visible without opening it.

### Daily Task Board

Task projects are what the [Daily Task Board](/docs/workspaces/#daily-task-board) workspace tab organizes work by. Each board picks which projects appear, gives them accent colors, and orders them; every worker then overlays a personal selection and order on top.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `id` | [number](/docs/records/fields/types/number/) | The ID of this project |
| &nbsp; | `is_closed` | [boolean](/docs/records/fields/types/boolean/) | Is this project closed? (`0` or `1`) |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this project |
| **x** | **`owner__context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of the owner |
| **x** | **`owner_id`** | [number](/docs/records/fields/types/number/) | The ID of the owner |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Search Query Fields

These [filters](/docs/search/#filters) are available in task project [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `closed` | boolean | Is the project closed? |
| `created` | date | When the record was created |
| `fieldset` | virtual | Filter by [custom fieldset](/docs/records/types/custom_fieldset/) |
| `id` | number | The record ID |
| `name` | text | The project name (partial match) |
| `owner` | virtual | Filter by the project's owner |
| `tasks` | virtual | A [deep search](/docs/search/#deep-search) on the [tasks](/docs/records/types/task/) within the project |
| `updated` | date | When the record was last modified |
| `watchers` | virtual | Filter by [watchers](/docs/watchers/) |

The `tasks:` filter matches any task filter against the project's tasks, so a project can be found by the properties of the work inside it:

```
tasks:(status:open owner:me)
```
