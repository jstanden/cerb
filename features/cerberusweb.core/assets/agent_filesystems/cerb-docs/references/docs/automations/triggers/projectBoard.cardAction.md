---
id: "docs-automations-triggers-projectboard-cardaction"
title: "projectBoard.cardAction"
url: "https://cerb.ai/docs/automations/triggers/projectBoard.cardAction/"
summary: "This page provides detailed information about the 'projectBoard.cardAction' automations in Cerb, which are triggered when a project board card moves to a new column, either manually or automatically. It explains the use of event handler KATA for executing all enabled automations and outlines the initial values in the automation dictionary, including keys for the project board, card record, column, custom input values, and the active worker. The page specifies that there are no outputs for this automation."
tags: ["docs", "docs-automations"]
---
**projectBoard.cardAction** [automations](/docs/automations/) are triggered when a [project board](/docs/project-boards/) card enters a new board column, either through the UI or procedurally.

This trigger uses [event handler](/docs/automations/#events) KATA, and all enabled automations are executed.

- [Inputs](#inputs)
- [Outputs](#outputs)

# Inputs

The automation [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `board_*` | record | The [project board](/docs/records/types/project_board/). Supports key expansion. |
| `card_*` | record | The card record. Supports key expansion. |
| `column_*` | record | The [project board column](/docs/records/types/project_board_column/). Supports key expansion. |
| `inputs` | dictionary | [Custom input](/docs/automations/#inputs) values from the caller |
| `worker_*` | record | The active [worker](/docs/records/types/worker/) |

# Outputs

(none)

