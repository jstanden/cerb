---
id: "solutions-automations-round-float-value"
title: "Round floating point numbers"
url: "https://cerb.ai/solutions/automations/round-float-value/"
summary: "This page demonstrates different ways to round floating point numbers in automation scripting. It covers basic rounding, ceiling, floor, and precision control using Pi as an example. The examples show how to use different rounding methods to achieve desired numeric formatting in a scripting context."
tags: ["solutions", "solutions-automations"]
---
## Using different rounding methods

Here are examples of rounding methods (round, ceil, floor, and precision control) for floating point numbers in automation scripting.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    number@float: 3.1415926535897932384626433832795028841971693993751
  return:
    round@int: {{number|round}}
    ceil@int: {{number|round(0, 'ceil')}}
    floor@int: {{number|round(0, 'floor')}}
    precision@float: {{number|round(5)}}
```
- 
```
__return:
  round: 3
  ceil: 4
  floor: 3
  precision: 3.14159
```

