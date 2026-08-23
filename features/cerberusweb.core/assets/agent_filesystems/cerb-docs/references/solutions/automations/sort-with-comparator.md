---
id: "solutions-automations-sort-with-comparator"
title: "Sort with comparator"
url: "https://cerb.ai/solutions/automations/sort-with-comparator/"
summary: "This page demonstrates how to use the sort filter with arrow functions to create custom sorting rules for complex data structures. It shows how to sort arrays of objects using specific object properties."
tags: ["solutions", "solutions-automations"]
---
## Sort objects by key property

Here is an example of using the |sort filter with arrow functions to create custom sorting rules.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    example_data@json: 
      [
        {"name": "Item 1", "key": "ZZZ"},
        {"name": "Item 2", "key": "MMM"},
        {"name": "Item 3", "key": "AAA"}
      ]
  return:
    sorted: {{example_data|sort((a,b)=> a.key<=>b.key)|column('name')|join(', ')}}
```
- 
```
__return:
  sorted: Item 3, Item 2, Item 1
```

