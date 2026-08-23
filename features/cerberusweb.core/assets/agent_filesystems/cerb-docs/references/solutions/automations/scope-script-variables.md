---
id: "solutions-automations-scope-script-variables"
title: "Scope scripting variables"
url: "https://cerb.ai/solutions/automations/scope-script-variables/"
summary: "This page demonstrates how to use the scripting `with` command to create isolated variable scopes. It shows how to work with variable transformations in different scopes, use variable mapping, and control access to outer scope variables using 'only'."
tags: ["solutions", "solutions-automations"]
---
## Basic variable scope with array transformations

The with command creates an isolated scope. Array operations performed inside are not visible outside.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    message@text:
      {% set numbers = range(1, 5) %}
      Outside scope numbers are: {{numbers|join(',')}}
      {% with %}
      {% set doubled = numbers|map(n => n * 2) %}
      Inside scope doubled numbers are: {{doubled|join(',')}}
      {% endwith %}
      Doubled numbers are not visible here anymore
  return:
    output@key: message
```
- 
```
__return:
  output: |-
    Outside scope numbers are: 1,2,3,4,5
    Inside scope doubled numbers are: 2,4,6,8,10
    Doubled numbers are not visible here anymore
```

## Using variable mapping

Pass variables directly in the with command using a mapping.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    message@text:
      {% set numbers = range(1, 5) %}
      {% with {tripled: numbers|map(n => n * 3)} %}
      Inside mapping scope tripled numbers are: {{tripled|join(',')}}
      {% endwith %}
  return:
    output@key: message
```
- 
```
__return:
  output: |
    Inside mapping scope tripled numbers are: 3,6,9,12,15
```

## Using only to restrict outer scope

The with command creates an isolated scope. Array operations performed inside are not visible outside.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    message@text:
      {% set outer = 'I am not visible' %}
      {% with {inner: 'I am visible'} only %}
      With only: {{inner}}, outer value is not accessible.
      {% endwith %}
  return:
    output@key: message
```
- 
```
__return:
  output: |
    With only: I am visible, outer value is not accessible.
```

## Basic variable scope with isolation

The [with](/docs/scripting/commands/#with) command creates an isolated scope. Variables defined inside are not visible in the outer scope.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    message@text:
      {% with %}
      {% set name = 'Kina' -%}
      Hi, {{name}}!
      {% endwith %}
      {% if name is empty %}
      Where did you go?
      {% endif %}
  return:
    output@key: message
```
- 
```
__return:
  output: |
    Hi, Kina!
    Where did you go?
```

