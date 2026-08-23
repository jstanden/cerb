---
id: "solutions-automations-append-array-list"
title: "Append values to arrays and lists"
url: "https://cerb.ai/solutions/automations/append-array-list/"
summary: "This page demonstrates various techniques for appending values to arrays and lists in Cerb automations, including using var.push, var.set, merge filters, and CSV concatenation. Each method offers different benefits depending on your use case."
tags: ["solutions", "solutions-automations"]
---
Here are examples of different methods for appending values to arrays and lists in automation scripting.

## Using var.push:

```
start:
  set:
    emails@csv: boss@cerb.example, customer@cerb.example, accounting@cerb.example
  var.push:
    inputs:
      key: emails
      value: marketing@cerb.example
```

## Using var.set:

```
start:
  set:
    emails@csv: boss@cerb.example, customer@cerb.example, accounting@cerb.example
  var.set:
    inputs:
      key: emails:{{emails|length}}
      value: marketing@cerb.example
```

## Using |merge:

```
start:
  set:
    emails@csv: boss@cerb.example, customer@cerb.example, accounting@cerb.example
  set/append:
    emails@json: {{emails|merge(['marketing@cerb.example'])|json_encode}}
```

## Using |csv and string concatenation:

```
start:
  set:
    emails@csv: boss@cerb.example, customer@cerb.example, accounting@cerb.example
  set/append:
    emails@csv: {{emails|join(',')}}, marketing@cerb.example
```
