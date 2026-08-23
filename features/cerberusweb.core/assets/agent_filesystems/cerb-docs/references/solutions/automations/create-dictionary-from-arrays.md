---
id: "solutions-automations-create-dictionary-from-arrays"
title: "Create a dictionary from arrays"
url: "https://cerb.ai/solutions/automations/create-dictionary-from-arrays/"
summary: "This page demonstrates how to use the `array_combine()` function to create a dictionary by pairing two arrays - one for keys and one for values. It's useful for converting parallel arrays into associative arrays or dictionaries."
tags: ["solutions", "solutions-automations"]
---
## Using array\_combine()

Here's an example of using `array_combine()` to create a dictionary from separate key and value lists.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    keys@csv: Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec
    values@csv: 88,45,8,88,76,82,31,100,30,91,19,54
  return:
    report@json: {{array_combine(keys,values)|json_encode}}
```
- 
```
__return:
  report:
    Jan: 88
    Feb: 45
    Mar: 8
    Apr: 88
    May: 76
    Jun: 82
    Jul: 31
    Aug: 100
    Sep: 30
    Oct: 91
    Nov: 19
    Dec: 54
```

