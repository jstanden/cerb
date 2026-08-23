---
id: "solutions-automations-extract-text-with-regexp"
title: "Extract text using regular expressions"
url: "https://cerb.ai/solutions/automations/extract-text-with-regexp/"
summary: "This page provides examples of using regular expressions in automation scripting to extract matching text. It demonstrates how to use a single capture group to extract an order ID from a string and how to use multiple capture groups to extract numerical values from a formatted string. The examples illustrate the syntax and methods for defining patterns and capturing specific parts of text using regular expressions in a scripting context."
tags: ["solutions", "solutions-automations"]
---
Here are examples of using regular expressions to extract matching text in automation scripting.

## Matching a single capture group

The pattern is a [KATA](/docs/kata/) key.

```
start:
  set:
    text: Your Amazon Order #Z-1234-5678-9 has shipped!
    pattern: /Amazon Order #([A-Z0-9\-]+)/
  return:
    order_id: {{text|regexp(pattern, 1)}}
```

We're taking the value of the `text` placeholder and applying a `|regexp` filter to it. That filter expects its first argument to be a regular expression `pattern`, and the optional second argument is a specific capture group to return (opposed to all matches as an array).

In the first argument, we're giving the pattern `/Amazon Order #([A-Z0-9\-]+)/`:

- `/` is the pattern delimiter, in the format of `/<pattern>/<flags>`.
- `Amazon Order #` matches text that starts with that phrase.
- `(...)` is a "capture group" for text after the previous match. Capture groups are defined with parentheses and you can have many of them. They can also be nested like `((\d)-(\w+))`, in which case they're numbered from the left-most opening parenthesis `(`.
- `[A-Z0-9\-]+` matches one or more consecutive characters while they are capital letters, digits, or a dash (`-`). The `[...]` brackets list the characters to match. The `+` at the end means "one or more", opposed to `*` which would mean "zero or more'.

## Setting the pattern as a variable

The pattern is a [scripting](/docs/scripting/) variable.

```
start:
  set:
    mask@text:
      {% set text = "The ticket mask that I am looking for is: KRN-69622-357 something else" %}
      {% set pattern %}/[A-Z]{3}-\d{5}-\d{3}/{% endset %}
      {{text|regexp(pattern)}}
  
  outcome/hasMask:
    if@bool: {{mask}}
    then:
      return:
        output: The ticket mask is #: {{mask}}
```

## Using multiple capture groups

The second argument to `|regexp` specifies the capture group to return.

```
start:
  set:
    text: (123,456)
    pattern: /^\((\d+),(\d+)\)$/
  return:
    x@int: {{text|regexp(pattern, 1)}}
    y@int: {{text|regexp(pattern, 2)}}
```

## Returning all matches for all capture groups

Use the [regexp\_match\_all()](/docs/scripting/functions/#regexp_match_all) function to return multiple capture groups for all matches.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    headers@text:
      X-Mailer: Cerb
      From: customer@cerb.example
      To: support@cerb.example
  return:
    results@text:
      {% set results = regexp_match_all("#^(.*?): (.*?)$#m", headers) %}
      {{results|json_encode|json_pretty}}
```
- 
```
__return:
  results: |-
    [
        [
            "X-Mailer: Cerb",
            "From: customer@cerb.example",
            "To: support@cerb.example"
        ],
        [
            "X-Mailer",
            "From",
            "To"
        ],
        [
            "Cerb",
            "customer@cerb.example",
            "support@cerb.example"
        ]
    ]
```

