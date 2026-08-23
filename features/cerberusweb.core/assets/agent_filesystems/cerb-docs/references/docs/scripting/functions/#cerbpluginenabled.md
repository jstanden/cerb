---
id: "docs-scripting-functions--cerbpluginenabled"
title: "Scripting Function: cerb_plugin_enabled"
url: "https://cerb.ai/docs/scripting/functions/#cerbpluginenabled"
summary: "Test if a Cerb plugin is installed and enabled"
tags: ["docs", "docs-scripting"]
---
## cerb\_plugin\_enabled

Test if a Cerb plugin is installed and enabled.

For instance, this can be used to make dashboard tabs or widgets conditional on a particular plugin being enabled (e.g. project boards).

`cerb_plugin_enabled(plugin_id)`

| **plugin\_id** | The name or ID of the [workflow](/docs/workflows/). |

```
{{cerb_plugin_enabled('cerb.classifiers')}}
```

```
1
```
