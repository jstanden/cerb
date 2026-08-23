---
id: "solutions-automations-loop-range"
title: "Loop through a range of numbers"
url: "https://cerb.ai/solutions/automations/loop-range/"
summary: "This page describes the use of Cerb's `range()` function, which allows looping through a set range of numbers. The `range` function can be used in two ways: as an automation command to loop through numbers and output them, or to generate a list of numbers for return. Additionally, the `step` command is introduced, allowing users to skip certain numbers with each step."
tags: ["solutions", "solutions-automations"]
---
## Loop through a numeric range

With the [range()](/docs/scripting/functions/#range) function, you can loop through a set range of numbers.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    output@text:
      {% for n in range(1,5) %}
      {{n}}...
      {% endfor %}
```
- 
```
__return:
  output: |
    1...
    2...
    3...
    4...
    5...
```

With the `step` command, you can skip a certain number with each step. For example, `step=2` will return every second number in the range.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    output@text:
      {% for n in range(1,10,step=2) %}
      {{n}}...
      {% endfor %}
```
- 
```
__return:
  output: |
    1...
    3...
    5...
    7...
    9...
```

