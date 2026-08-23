---
id: "docs-scripting-functions--cerbworkflowresources"
title: "Scripting Function: cerb_workflow_resources"
url: "https://cerb.ai/docs/scripting/functions/#cerbworkflowresources"
summary: "Perform runtime resource lookups and return workflow resource map"
tags: ["docs", "docs-scripting"]
---
## cerb\_workflow\_resources

Perform runtime resource lookups and return a map of workflow resources and their local record IDs. This is useful from automations, event listeners, and toolbars.

`cerb_workflow_resources(name_or_id)`

| **name\_or\_id** | The name or ID of the [workflow](/docs/workflows/). |

```
{{cerb_workflow_resources('example.workflow'|json_encode}}
```

```
{"records":{"automation/example":123}}
```
