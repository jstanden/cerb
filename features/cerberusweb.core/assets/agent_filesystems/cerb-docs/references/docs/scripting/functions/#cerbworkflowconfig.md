---
id: "docs-scripting-functions--cerbworkflowconfig"
title: "Scripting Function: cerb_workflow_config"
url: "https://cerb.ai/docs/scripting/functions/#cerbworkflowconfig"
summary: "Perform runtime configuration lookups from workflow"
tags: ["docs", "docs-scripting"]
---
## cerb\_workflow\_config

Perform runtime configuration lookups from any feature that supports automation scripting (e.g. automations, workflows, snippets). For instance, you can create a workflow just for sharing values (e.g. API keys) between multiple workflows.

`cerb_workflow_config(name_or_id,key,default)`

| **name\_or\_id** | The name or ID of the [workflow](/docs/workflows/). |
| **key** | The optional config key to return. If omitted, all keys/values are returned as a map. |
| **default** | The optional default value if the key doesn't exist. |

```
{{cerb_workflow_config('example.workflow','secretCode',null)}}
```

```
sup3rs3cr3t
```
