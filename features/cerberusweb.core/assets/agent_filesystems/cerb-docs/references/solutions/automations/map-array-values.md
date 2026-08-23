---
id: "solutions-automations-map-array-values"
title: "Map array values"
url: "https://cerb.ai/solutions/automations/map-array-values/"
summary: "This page demonstrates how to use the map modifier with arrow functions in automation scripting to transform array values. It shows how to use lambda expressions to apply mathematical operations and transformations to lists of numbers."
tags: ["solutions", "solutions-automations"]
---
## Calculating squares and cubes

Here is an example of using the [|map](/docs/scripting/filters/#map) modifier with arrow functions to transform array values.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    numbers@csv: {{range(1, 10)|join(',')}}
  return:
    squares@json: {{numbers|map((n) => n ** 2)|json_encode}}
    cubes@json: {{numbers|map((n) => n ** 3)|json_encode}}
```
- 
```
__return:
  squares:
  - 1
  - 4
  - 9
  - 16
  - 25
  - 36
  - 49
  - 64
  - 81
  - 100
  cubes:
  - 1
  - 8
  - 27
  - 64
  - 125
  - 216
  - 343
  - 512
  - 729
  - 1000
```

