---
id: "solutions-automations-arbitrary-records-input"
title: "Arbitrary records as input"
url: "https://cerb.ai/solutions/automations/arbitrary-records-input/"
summary: "This page demonstrates how to accept a record type and ID as input parameters in an automation, then use them to create an expandable record placeholder. This pattern is useful when you need to work with different types of records dynamically based on input parameters."
tags: ["solutions", "solutions-automations"]
---
## Dynamic record type and ID as input

You can accept any record type and ID as input parameters, then use them to create an expandable record placeholder.

- [automation](#)

- 
```
inputs:
  text/record_type:
    type: record_type
    required@bool: yes
  text/record_id:
    type: number
    required@bool: yes

start:
  set:
    record__context@key: inputs:record_type
    record_id@key: inputs:record_id
  return:
    output: {{record__label}}
```

