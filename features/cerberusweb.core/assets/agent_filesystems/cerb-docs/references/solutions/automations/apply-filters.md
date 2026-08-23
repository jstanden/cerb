---
id: "solutions-automations-apply-filters"
title: "Apply filters"
url: "https://cerb.ai/solutions/automations/apply-filters/"
summary: "This page explains how to apply filters to a block of text in Cerb automation scripting. Filters can be applied using two methods: the `apply` tag, which changes the case of the text, or by chaining multiple filters together with `|`, allowing for more complex transformations such as lowercasing and indenting text. For example, applying an `upper` filter to a block of text results in all text being uppercase, while applying a combination of `lower`, `indent`, and `>` filters can be used to create a snippet that formats text with indentation and capitalization."
tags: ["solutions", "solutions-automations"]
---
You can apply filters to a block of text using automation [scripting](/docs/scripting/) in two ways.

First, you can wrap a text block in the `apply` tag.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    output@text:
      {% apply upper %}
      All of this text will be uppercase.
      On every line.
      {% endapply %}
```
- 
```
__return:
  output: |
    ALL OF THIS TEXT WILL BE UPPERCASE.
    ON EVERY LINE.
```

Alternatively, you can chain multiple filters together with the pipe (`|`) character. This can be useful when creating [snippets](/docs/snippets/).

- [automation](#)
- [output](#)

- 
```
start:
  set:
    text@text:
      ALL OF THIS TEXT WILL BE LOWER CASE.
      ON EVERY LINE.
      WITH A >.
      AT THE START OF EACH LINE.
  return:
    output: {{text|lower|indent('> ')}}
```
- 
```
__return:
  output: |
    > all of this text will be lower case
    > on every line.
    > with a >.
    > at the start of each line.
```

