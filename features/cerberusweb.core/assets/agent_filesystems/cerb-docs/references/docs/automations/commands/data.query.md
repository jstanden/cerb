---
id: "docs-automations-commands-data-query"
title: "Automations: data.query"
url: "https://cerb.ai/docs/automations/commands/data.query/"
summary: "This page provides detailed information on the 'data.query' command used in Cerb automations. It explains how to execute a data query and handle the response, including the syntax for inputs, outputs, and handling different states such as success, simulation, and error. The page outlines how to structure the query, manage query parameters, and process the results or errors through specified commands. It serves as a guide for implementing data queries within Cerb's automation framework, ensuring users can effectively retrieve and manage data."
tags: ["docs", "docs-automations"]
---
The **data.query:** command executes a [data query](/docs/data-queries/) and returns the response.

```
start:
  data.query:
    output: results
    inputs:
      query@text:
        type:worklist.records
        of:ticket
        format:dictionaries
    on_success:
      return:
        records@key: results:data
```

- [Syntax](#syntax)
  - [inputs:](#inputs)
  - [output:](#output)
  - [on\_simulate:](#on_simulate)
  - [on\_success:](#on_success)
  - [on\_error:](#on_error)

# Syntax

## inputs:

| Key | &nbsp; |
| --- | --- |
| `query@text:` | A [data query](/docs/data-queries/) to run. |
| `query_params` | Query parameters with untrusted user input as keys/values. Reference these as `${param}` in queries. |

## output:

Save the results in this placeholder.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of the data query.

If omitted, the data query is executed during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `data` | The response from the query. |
| `_` | The metadata from the query. |

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

