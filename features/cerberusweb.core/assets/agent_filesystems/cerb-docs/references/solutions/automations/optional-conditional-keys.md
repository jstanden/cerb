---
id: "solutions-automations-optional-conditional-keys"
title: "Optional conditional keys"
url: "https://cerb.ai/solutions/automations/optional-conditional-keys/"
summary: "This page demonstrates how to use the `@optional` annotation to conditionally include or omit dictionary keys based on their values. When a key with the `@optional` annotation evaluates to null, it is removed from the output."
tags: ["solutions", "solutions-automations"]
---
## Using @optional

Here is an example of using the @optional annotation to conditionally include dictionary keys based on their values. The key is omitted when the value is empty or false.

The `gdpr:` key will be removed when the region is not `EU`.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    record:
      name: Kina Halpue
      email: kina@cerb.example
      region: US
  return:
    output:
      name: {{record.name}}
      email: {{record.email}}
      gdpr@optional,bool: {{'EU' == record.region ? true}}
```
- 
```
__return:
  output:
    name: Kina Halpue
    email: kina@cerb.example
```

