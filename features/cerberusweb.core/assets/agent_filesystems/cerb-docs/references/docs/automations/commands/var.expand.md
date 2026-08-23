---
id: "docs-automations-commands-var-expand"
title: "Automations: var.expand"
url: "https://cerb.ai/docs/automations/commands/var.expand/"
summary: "This page provides detailed information on the `var.expand` command used in Cerb automations. It explains how to expand nested keys at a specified dictionary path within automation scripts. The page includes syntax details for the command, outlining required and optional keys such as `key` and `paths`, and describes how to handle different scenarios with `on_simulate`, `on_success`, and `on_error` commands. An example is provided to illustrate the command's functionality, showing how to extract and format data from a dictionary. The page serves as a guide for users looking to implement or understand the `var.expand` command in their automation workflows."
tags: ["docs", "docs-automations"]
---
The **var.expand:** command expands nested keys at a given dictionary path.

```
inputs:
  records/tickets:
    record_type: ticket
    required@bool: yes

start:
  var.expand:
    inputs:
      key: inputs:tickets
      paths: owner_,customfields
  return:
    owners@json: {{array_column(inputs.tickets,'owner__label','_label')|json_encode}}
```

Result:

```
owners:
  '[#ANB-75367-518] Always use HTML mode on replies': Kina Halpue
```

- [Syntax](#syntax)
  - [inputs:](#inputs)
  - [on\_simulate:](#on_simulate)
  - [on\_success:](#on_success)
  - [on\_error:](#on_error)

# Syntax

## inputs:

| Key | Req'd | &nbsp; |
| --- | --- | --- |
| `key:` | &nbsp; | The [key path](/docs/automations/#dictionaries) to expand, delimited with colons (`:`). This must be a dictionary or an array of dictionaries. When omitted, key expansion happens in the root dictionary |
| `paths:` | **x** | The paths to expand at the given dictionary keys. |

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of expanding the key.

If omitted, the value is appended during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

