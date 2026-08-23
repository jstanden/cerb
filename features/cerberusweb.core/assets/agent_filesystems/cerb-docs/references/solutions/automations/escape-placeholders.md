---
id: "solutions-automations-escape-placeholders"
title: "Escape placeholders"
url: "https://cerb.ai/solutions/automations/escape-placeholders/"
summary: "This page explains how to escape placeholders in a value using the `@raw` annotation in Cerb. It shows an example of how this annotation can be used to preserve plain text values, particularly when working with sheets. The output demonstrates that using `@raw`, placeholders are treated as literal text, rather than being evaluated or replaced. This allows for more control over the formatting and appearance of values in a sheet, making it easier to work with complex data."
tags: ["solutions", "solutions-automations"]
---
## Using @raw

Sometimes you don't want a placeholder to be evaluated in a literal value. You can do this with the @raw annotation. This is particularly useful in sheets.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    unescaped: You escape {{placeholders}} with the `@raw` annotation
    escaped@raw: You escape {{placeholders}} with the `@raw` annotation
```
- 
```
__return:
  unescaped: You escape with the `@raw` annotation
  escaped: You escape {{placeholders}} with the `@raw` annotation
```

