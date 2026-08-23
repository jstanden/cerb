---
id: "docs-automations-inputs-record"
title: "Automation Inputs: Record"
url: "https://cerb.ai/docs/automations/inputs/record/"
summary: "This page provides detailed information on configuring automation inputs for a record in Cerb, specifically focusing on tickets. It outlines the necessary parameters such as 'required', 'record_type', 'default', and 'expand'. The 'required' parameter indicates whether the input is mandatory, while 'record_type' specifies the type of record, in this case, a ticket. The 'default' parameter allows setting a default value if none is provided. The 'expand' parameter is used to specify which keys should be expanded in the record dictionary, allowing for more detailed data retrieval. The page also explains the format for input values, which can be a record ID or a URI."
tags: ["docs", "docs-automations"]
---
```
inputs:
  record/ticket:
    required@bool: yes
    record_type: ticket
    expand: owner_,customfields
    #default: 1
```

The value should be a record ID (`123`) or URI (`cerb:record_type:record_alias`).

### required:

### record\_type:

[record types](/docs/records/types/)

### default:

The default for the input if a value is omitted.

### expand:

A comma-separated string or array of keys to expand in the record dictionary.

