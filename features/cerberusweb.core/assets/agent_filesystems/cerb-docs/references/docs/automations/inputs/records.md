---
id: "docs-automations-inputs-records"
title: "Automation Inputs: Records"
url: "https://cerb.ai/docs/automations/inputs/records/"
summary: "This page provides detailed information on configuring automation inputs for records in Cerb. It explains how to define inputs for records, specifying whether they are required, the type of records, and default values if inputs are omitted. Additionally, it describes the use of the 'expand' option, which allows for the expansion of specific keys in the record dictionaries. The page is intended for users looking to set up or customize automation inputs by specifying record IDs or URIs, with a focus on address record types."
tags: ["docs", "docs-automations"]
---
```
inputs:
  records/participants:
    required@bool: yes
    record_type: address
    expand: owner_,customfields
    #default@csv: 1,2,3
```

The value should be an array of record IDs (`123`) or URIs (`cerb:record_type:record_alias`).

### required:

### record\_type:

[record types](/docs/records/types/)

### default:

The default for the input if a value is omitted.

### expand:

A comma-separated string or array of keys to expand in the record dictionaries.

