---
id: "solutions-automations-filter-array-values"
title: "Filter array values"
url: "https://cerb.ai/solutions/automations/filter-array-values/"
summary: "This page demonstrates how to use the filter modifier with arrow functions in automation scripting to filter array values based on conditions. It shows how to use lambda expressions to create flexible filtering rules for lists of data."
tags: ["solutions", "solutions-automations"]
---
## Filtering multiples of 5

Here is an example of using the |filter modifier with arrow functions to filter array values based on conditions in automation scripting.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    numbers@csv: {{range(1, 100)|join(',')}}
  return:
    multiples5@csv: {{numbers|filter((n,k) => 0 == n % 5)|join(',')}}
```
- 
```
__return:
  multiples5:
  - "5"
  - "10"
  - "15"
  - "20"
  - "25"
  - "30"
  - "35"
  - "40"
  - "45"
  - "50"
  - "55"
  - "60"
  - "65"
  - "70"
  - "75"
  - "80"
  - "85"
  - "90"
  - "95"
  - "100"
```

