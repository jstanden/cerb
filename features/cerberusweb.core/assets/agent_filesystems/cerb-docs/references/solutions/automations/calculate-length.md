---
id: "solutions-automations-calculate-length"
title: "Calculate length"
url: "https://cerb.ai/solutions/automations/calculate-length/"
summary: "This page documents the use of the `|length` syntax in Cerb automation scripting, which allows you to calculate the length of any list or text string."
tags: ["solutions", "solutions-automations"]
---
With [|length](/docs/scripting/filters/#length) you can calculate the length of any list or text string.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    list_len@int: {{[1,2,3,4,5,6,7,8,9,10]|length}}
    text_len@int: {{'This is some text'|length}}
```
- 
```
__return:
  list_len: 10
  text_len: 17
```

