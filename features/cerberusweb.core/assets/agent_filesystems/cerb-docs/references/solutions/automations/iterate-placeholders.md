---
id: "solutions-automations-iterate-placeholders"
title: "Iterate through unknown placeholders"
url: "https://cerb.ai/solutions/automations/iterate-placeholders/"
summary: "This page demonstrates how to use the `cerb_placeholders_list()` function to iterate through placeholders when their keys are not known in advance. It shows how to filter placeholders by prefix and access their values dynamically, making it useful for working with dynamically generated placeholder names."
tags: ["solutions", "solutions-automations"]
---
## Creating and iterating random placeholders

Here is an example of using the [cerb\_placeholders\_list()](/docs/scripting/functions/#cerb_placeholders_list) function to iterate through placeholders with unknown keys. The optional first argument is a prefix filter.

- [automation](#)
- [output](#)

- 
```
start:
  repeat:
    each@csv: {{range(1, 10)|join(',')}}
    as: i
    do:
      var.set:
        inputs:
          key: random_{{random_string(6)}}
          value: {{random_string(6)}}
  
  return:
    output@text:
      {% for key, value in cerb_placeholders_list('random_') %}
      random_{{key}}: {{value}}
      {% endfor %}
```
- 
```
__return:
  output: |
    random_9QVUFG: 9UA12E
    random_3518J5: P42E5N
    random_5AP3CY: 4FDYTB
    random_LXYYY2: 5Q2Q66
    random_VCHQPD: F8HRM3
    random_TFSNM3: 1JL253
    random_1D62R3: VTYPX5
    random_FUDWUC: QJVB8C
    random_DQFJPH: QTCUM3
    random_N6P8AK: 8TQR3U
```

