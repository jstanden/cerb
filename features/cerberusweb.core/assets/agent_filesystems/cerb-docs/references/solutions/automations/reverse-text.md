---
id: "solutions-automations-reverse-text"
title: "Reverse text or lists"
url: "https://cerb.ai/solutions/automations/reverse-text/"
summary: "This page demonstrates the use of the `|reverse` function in Cerb scripting to reverse text or list elements. The syntax can be used with a boolean value (`true`) to preserve array keys."
tags: ["solutions", "solutions-automations"]
---
You can use [|reverse](/docs/scripting/filters/#reverse) in scripting to reverse any given block of text or list. `|reverse(true)` will do so while preserving array keys. %}

- [automation](#)
- [output](#)

- 
```
start:
  return:
    reversed_list@csv: {{[1,2,3,4,5]|reverse|join(',')}}
    reversed_text: {{"This is text to reverse."|reverse}}
```
- 
```
__return:
  reversed_list:
  - "5"
  - "4"
  - "3"
  - "2"
  - "1"
  reversed_text: .esrever ot txet si sihT
```

