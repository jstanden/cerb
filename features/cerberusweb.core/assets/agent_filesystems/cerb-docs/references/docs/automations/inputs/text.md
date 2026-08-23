---
id: "docs-automations-inputs-text"
title: "Automation Inputs: Text"
url: "https://cerb.ai/docs/automations/inputs/text/"
summary: "This page provides detailed information on automation inputs for Cerb, specifically focusing on text inputs such as email. It outlines the requirements for these inputs, including whether they are mandatory, their default values, and their data types. The page includes a comprehensive table listing various data types supported by Cerb, such as boolean, date, decimal, email, freeform text, geopoint, IP addresses, record types, numbers, timestamps, URIs, and URLs, along with examples for each type. This serves as a guide for users to understand and implement the correct input formats in their automation processes."
tags: ["docs", "docs-automations"]
---
```
inputs:
  text/email:
    required@bool: yes
    default: you@example.com
    type: email
```

### required:

### default:

### type:

| Type | &nbsp; | Examples |
| --- | --- | --- |
| `bool` | &nbsp; | `y`/`n`, `yes`/`no`, `on`/`off`, `true`/`false`, `0`/`1` |
| `date` | &nbsp; | `noon Jan 20 2021`, `tomorrow 5pm`, `next Friday 8am`, `+2 days` |
| `decimal` | &nbsp; | `3.1415` |
| `email` | &nbsp; | `mailbox@host.example` |
| `freeform` | &nbsp; | `This is a line of arbitrary text` (default) |
| `geopoint` | &nbsp; | `44.284536706018905, 20.7861328125` |
| `ip` | &nbsp; | `1.2.3.4` or `a1b2:c3d4:e5f6:a1b2:c3d4:e5f6:a1b2:c3d4` |
| `ipv4` | &nbsp; | `1.2.3.4` |
| `ipv6` | &nbsp; | `a1b2:c3d4:e5f6:a1b2:c3d4:e5f6:a1b2:c3d4` |
| `record_type` | &nbsp; | `ticket` |
| `number` | &nbsp; | `1234` |
| `timestamp` | &nbsp; | `1606526867` |
| `uri` | &nbsp; | `some-resource-name` |
| `url` | &nbsp; | `https://cerb.ai/` |

