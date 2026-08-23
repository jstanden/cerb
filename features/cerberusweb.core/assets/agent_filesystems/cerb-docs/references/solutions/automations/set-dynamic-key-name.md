---
id: "solutions-automations-set-dynamic-key-name"
title: "Set a dynamic key name"
url: "https://cerb.ai/solutions/automations/set-dynamic-key-name/"
summary: "This page demonstrates how to set dynamic key names using Cerb's `var.set:` command, which allows for flexible and non-standard key names that are not valid in KATA scripting. It provides examples of setting random key names, using special characters like `@`, and changing the delimiter from the standard `:` if needed."
tags: ["solutions", "solutions-automations"]
---
## Using scripting in a key name

You can't use scripting in a KATA key, but if you want to set a dynamic key name, you can do so with var.set:

- [automation](#)

- 
```
start:
  var.set/random:
    inputs:
      key: random_{{random_string(6)}}
      value: {{random_string(6)}}
```

## Using forbidden characters in a key name

This approach can also be used if you want a key name that isn't valid in KATA, such as a `:` or `@`.

- [automation](#)

- 
```
start:
  var.set/email:
    inputs:
      key@text: customer@cerb.example
      value: allow
```

## Changing the delimiter for key paths

You can also use the `delimiter:` field to change the delimiter from the standard `:` if necessary. This allows you to use `:` in a key name.

- [automation](#)

- 
```
start:
  var.set/colon:
    inputs:
      key@text: og:image
      delimiter: ::
      value: https://example.com/images/social.png
```

