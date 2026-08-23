---
id: "docs-scripting-functions--arrayfillkeys"
title: "Scripting Function: array_fill_keys"
url: "https://cerb.ai/docs/scripting/functions/#arrayfillkeys"
summary: "Create an array with given keys, each set to a default value"
tags: ["docs", "docs-scripting"]
---
## array\_fill\_keys

Create an array with the given keys, each set to the default value.

`array_fill_keys(keys,value)`

```
{{array_fill_keys(range(1,10),true)|json_encode}}
```

```
{"1":true,"2":true,"3":true,"4":true,"5":true,"6":true,"7":true,"8":true,"9":true,"10":true}
```
