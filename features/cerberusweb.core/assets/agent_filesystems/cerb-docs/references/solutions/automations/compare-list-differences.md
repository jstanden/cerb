---
id: "solutions-automations-compare-list-differences"
title: "Compare list differences"
url: "https://cerb.ai/solutions/automations/compare-list-differences/"
summary: "This page demonstrates how to use the `array_diff()` function to find elements that exist in one list but not another. This is useful for identifying new or missing items between two sets of data."
tags: ["solutions", "solutions-automations"]
---
## Using array\_diff() function

Here is an example of using the [array\_diff()](/docs/scripting/functions/#array_diff) function to find elements that exist in the second array but not in the first.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    array1@csv: Apple, Google, Microsoft
    array2@csv: Apple, Microsoft, Cerb
    diff@csv: {{array_diff(array2, array1)|join(', ')}}
  return:
    output: These are new: {{diff|join(', ')}}
```
- 
```
__return:
  output: 'These are new: Cerb'
```

