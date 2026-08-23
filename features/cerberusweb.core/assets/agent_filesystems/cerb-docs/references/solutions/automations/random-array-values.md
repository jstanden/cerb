---
id: "solutions-automations-random-array-values"
title: "Random array values"
url: "https://cerb.ai/solutions/automations/random-array-values/"
summary: "This page demonstrates how to create an array of random numbers using range mapping and the random function in Cerb. It provides a step-by-step example, including using `range()` for array size, applying `map()` for random value generation, and JSON encoding for output format, resulting in an array filled with unique random numbers."
tags: ["solutions", "solutions-automations"]
---
## Generate random numbers

This automation generates an array of 10 random numbers between 0 and 100 (inclusive).

- [automation](#)
- [output](#)

- 
```
start:
  return:
    values@json: {{range(1, 10)|map((v) => random(0, 100))|json_encode}}
```
- 
```
__return:
  values:
  - 30
  - 18
  - 50
  - 39
  - 89
  - 73
  - 98
  - 90
  - 72
  - 37
```

