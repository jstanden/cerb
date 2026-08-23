---
id: "solutions-automations-batch-list-items"
title: "Batch list items into sets"
url: "https://cerb.ai/solutions/automations/batch-list-items/"
summary: "This page demonstrates how to use the `|batch` filter to split a list into smaller sets of a specified size. The example shows splitting a list of numbers into groups of three, using -1 as a fill value for the incomplete final batch."
tags: ["solutions", "solutions-automations"]
---
## Using |batch filter

The [|batch](/docs/scripting/filters/#batch) filter divides a list into smaller sets. These sets can be specified to a size, incomplete sets can be filled with a default value, and original array keys can be preserved or not.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    items@json: [1,2,3,4,5,6,7,8,9,10]
  return:
    batches: {{items|batch(size=3, fill=-1, preserve_keys=false)|json_encode}}
```
- 
```
__return:
  batches: '[[1,2,3],[4,5,6],[7,8,9],[10,-1,-1]]'
```

