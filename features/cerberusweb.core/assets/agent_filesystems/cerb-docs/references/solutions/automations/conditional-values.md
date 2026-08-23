---
id: "solutions-automations-conditional-values"
title: "Conditional values"
url: "https://cerb.ai/solutions/automations/conditional-values/"
summary: "This page explains how to use ternary operators in Cerb automations to create conditional logic. You can chain multiple conditions together to check various states and return different values based on those conditions."
tags: ["solutions", "solutions-automations"]
---
## Using the ternary operator

The **ternary operator** provides a compact way to write an if/else condition: `{{expression ? if_true : if_false}}`

```
start:
  set:
    is_admin@bool: true
  return:
    is_admin: {{is_admin ? 'yes' : 'no'}}
```
