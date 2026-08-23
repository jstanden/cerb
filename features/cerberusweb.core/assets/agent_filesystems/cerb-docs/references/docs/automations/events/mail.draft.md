---
id: "docs-automations-events-mail-draft"
title: "mail.draft"
url: "https://cerb.ai/docs/automations/events/mail.draft/"
summary: "This page provides detailed information about the 'mail.draft' automation events in Cerb, which allow modifications to be made to any property of a new or resumed email draft after a worker initiates a 'Compose' or 'Reply' action, but before the editor popup appears. It explains that these automations are cumulative, meaning multiple automations can alter the draft, with later changes overwriting earlier ones on the same fields. The page also describes how to use the `uri` field for setting custom fields and how new changes are merged with existing fields. Additionally, it outlines the placeholders available in the automation event dictionary, such as `draft_*` for the draft record and `is_resumed` to indicate if the draft was resumed. The outputs section details how draft parameter modifications are structured in a dictionary format."
tags: ["docs", "docs-automations"]
---
**mail.draft** [automation](/docs/automations/) [events](/docs/automations/#events) can modify any property on a new or resumed draft after a worker clicks on a 'Compose' or 'Reply' button, but before the editor popup opens.

Automations on this event are cumulative – multiple automations can modify the draft, with subsequent changes on the same fields overwriting earlier ones.

When setting `custom_fields:`, their `uri` field can be used as the key instead of IDs. New custom field changes are merged with existing fields.

# Placeholders

The automation event [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `draft_*` | record | The [draft](/docs/records/types/draft/) record. Supports key expansion. |
| `is_resumed` | bool | `true` if the draft was resumed, `false` if new |

# Outputs

| Key | Type | Notes |
| --- | --- | --- |
| `draft:params:` | dictionary | A dictionary of [draft parameter](/docs/records/types/draft/) modifications |

