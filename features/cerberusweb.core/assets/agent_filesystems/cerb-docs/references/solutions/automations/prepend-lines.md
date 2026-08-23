---
id: "solutions-automations-prepend-lines"
title: "Prepend a prefix to a text block"
url: "https://cerb.ai/solutions/automations/prepend-lines/"
summary: "This page provides an example of how to add prefixes to each line of a given block of text in Cerb, using the `set` and `return` blocks. It also describes variations for using the output with workers, including copying it to the clipboard or pasting it into an existing message."
tags: ["solutions", "solutions-automations"]
---
## Using |indent

In this example we quote a prior email message by adding the standard `>` character to the start of each line.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    original_message@text:
      This is quoted text
      From a prior conversation
      and we want to quote it
  return:
    new_message@text:
      {{original_message|indent('> ')}}
      
      ... and this is our reply.
```
- 
```
__return:
  new_message: |-
    > This is quoted text
    > From a prior conversation
    > and we want to quote it

    ... and this is our reply.
```

