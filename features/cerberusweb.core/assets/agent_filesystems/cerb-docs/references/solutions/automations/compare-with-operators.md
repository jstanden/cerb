---
id: "solutions-automations-compare-with-operators"
title: "Compare with operators"
url: "https://cerb.ai/solutions/automations/compare-with-operators/"
summary: "This page demonstrates how to use various comparison operators to evaluate and compare values. It shows examples of equality, inequality, less than, greater than or equal to, and membership testing using 'in' operators."
tags: ["solutions", "solutions-automations"]
---
## Using comparison operators

Here are examples of using different comparison [operators](/docs/scripting/operators/) to evaluate values.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    eq@bool: {{1 == 1}}
    not_eq@bool: {{1 != 2}}
    lt@bool: {{50 < 100}}
    gte@bool: {{100 >= 2}}
    in@bool: {{5 in [1,2,3,4,5,6]}}
    nin@bool: {{100 not in [1,2,3]}}
```
- 
```
__return:
  eq: true
  not_eq: true
  lt: true
  gte: true
  in: true
  nin: true
```

