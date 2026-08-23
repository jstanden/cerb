---
id: "docs-scripting-functions--cerbcurrentworker"
title: "Scripting Function: cerb_current_worker"
url: "https://cerb.ai/docs/scripting/functions/#cerbcurrentworker"
summary: "Return a dictionary for the currently logged in worker"
tags: ["docs", "docs-scripting"]
---
## cerb\_current\_worker

Return a dictionary for the currently logged in worker. This returns an empty dictionary when used outside a browser session.

`cerb_current_worker(expand)`

| **expand** | An optional comma-delimited string or array of dictionary keys to expand. |

```
Hello {{cerb_current_worker().first_name}}!
```

```
Hello Kina!
```
