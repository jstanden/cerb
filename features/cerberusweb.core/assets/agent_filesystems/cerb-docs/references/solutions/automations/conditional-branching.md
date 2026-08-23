---
id: "solutions-automations-conditional-branching"
title: "Conditional branching statements"
url: "https://cerb.ai/solutions/automations/conditional-branching/"
summary: "This page demonstrates different approaches to conditional branching, including using decision blocks with multiple outcomes and simplified dictionary-based value selection. It provides examples of both command-based branching and value-based conditional selection."
tags: ["solutions", "solutions-automations"]
---
Here are examples of different approaches to conditional branching, demonstrating if/else-if statements with decision blocks and switch statements using dictionaries.

## Using a decision block

- [automation](#)
- [output](#)

- 
```
start:
  set:
    option: 2

  decision:
    outcome/option1:
      if@bool: {{1 == option}}
      then:
        return:
          output: You picked option 1.
    outcome/option2:
      if@bool: {{2 == option}}
      then:
        return:
          output: You picked option 2.
    outcome/option3:
      if@bool: {{3 == option}}
      then:
        return:
          output: You picked option 3.
```
- 
```
__return:
  output: You picked option 2.
```

## Using dynamic dictionary keys

- [automation](#)
- [output](#)

- 
```
start:
  set:
    option: 3
    options:
      1: You picked option 1
      2: You picked option 2
      3: You picked option 3

  return:
    output: {{options[option]}}
```
- 
```
__return:
  output: You picked option 3
```

