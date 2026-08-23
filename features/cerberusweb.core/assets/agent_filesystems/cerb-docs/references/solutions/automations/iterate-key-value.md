---
id: "solutions-automations-iterate-key-value"
title: "Iterate objects with key and value"
url: "https://cerb.ai/solutions/automations/iterate-key-value/"
summary: "This page explains how to iterate over objects in Cerb using the `repeat:` command. When using `key, value` in `as:`, it sets two placeholders for accessing each item subsequent commands."
tags: ["solutions", "solutions-automations"]
---
When using the [`repeat:`](/docs/automations/commands/repeat/) command in [automations](/docs/automations/), you can specify two placeholders separated by a comma in the `as:` option (`key, value`) and they will be set with the respective key and value of each item.

- [automation](#)
- [results](#)

- 
```
start:
  set:
    data:
      0:
        label: Red
      1:
        label: Green
      2:
        label: Blue
  
  repeat:
    each@key: data
    as: index, obj
    do:
      log: Index: {{index}} Value: {{obj.label}}
```
- 
 

