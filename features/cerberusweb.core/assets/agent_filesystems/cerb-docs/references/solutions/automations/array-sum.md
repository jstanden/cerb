---
id: "solutions-automations-array-sum"
title: "Sum or multiply an array of numbers"
url: "https://cerb.ai/solutions/automations/array-sum/"
summary: "This page provides a solution for calculating the sum and product of an array of numbers using Cerb scripting. It showcases two examples, one where the script is written as part of an automation process and another where it is provided as output from a previous calculation. The solution utilizes the `array_sum()` function to calculate the sum and the `|reduce` filter to calculate the product of the input numbers."
tags: ["solutions", "solutions-automations"]
---
## Using array\_sum() and |reduce

You can use [array\_sum()](/docs/scripting/functions/#array_sum) and [|reduce](/docs/scripting/filters/#reduce) to calculate the sum or product of an array of numbers and reduce the output to that single result.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    numbers@csv: 1, 9, 2002, 4, 27, 2001
  return:
    sum@int: {{array_sum(numbers)}}
    product@int: {{numbers|reduce((carry,n) => carry * n, 1)}}
```
- 
```
__return:
  sum: 4044
  product: 3893833944
```

