---
id: "docs-automations-commands-kata-parse"
title: "Automations: kata.parse"
url: "https://cerb.ai/docs/automations/commands/kata.parse/"
summary: "This page provides detailed information on the 'kata.parse' command used in Cerb automations to parse KATA documents with placeholder substitution. It explains the syntax and components involved, such as inputs, outputs, and error handling. The inputs include a KATA document, a dictionary for placeholder values, and an optional validation schema to ensure the document's integrity. The page also describes the types of data that can be used within a KATA document, such as arrays, booleans, lists, objects, and text. Additionally, it outlines the procedures for handling simulation, success, and error scenarios, ensuring robust automation processes."
tags: ["docs", "docs-automations"]
---
The **kata.parse:** command parses an arbitrary [KATA](/docs/kata/) document with placeholder substitution.

```
start:
  kata.parse:
    inputs:
      kata@raw:
        template@text:
          Hello {{name}}! Thanks for writing to {{company}}.
      dict:
        name: Janey
        company: Cerb
    output: results

  return:
    output: {{results.template}}
```

- [Syntax](#syntax)
  - [inputs:](#inputs)
    - [kata:](#kata)
    - [dict:](#dict)
    - [schema:](#schema)
      - [Keys](#keys)
      - [types:](#types)

  - [output:](#output)
  - [on\_simulate:](#on_simulate)
  - [on\_success:](#on_success)
  - [on\_error:](#on_error)

# Syntax

## inputs:

### kata:

A [KATA](/docs/kata/) document to parse. This will generally use the [@raw](/docs/kata/#raw) annotation to prevent placeholders from being substituted by the automation prior to execution.

### dict:

A dictionary of keys/values for placeholders.

### schema:

A validation schema for the KATA document. This is useful if the document is dynamically generated or user-provided.

```
schema:
    attributes:
      automation:
        multiple@bool: yes
        types:
          object:
            attributes:
              uri:
                required@bool: yes
                types:
                  string:
              disabled:
                types:
                  bool:
              inputs:
                types:
                  list:
```

#### Keys

| Option | &nbsp; |
| --- | --- |
| `multiple@bool:` | The key may appear multiple times as siblings. Default `no`. |
| `required@bool:` | The key must be defined in the document. Default `no`. |
| `types:` | One or more types that describe acceptable values for the key. |

#### types:

Each key in a KATA document must be of one or more data **types**.

| Type | Description |
| --- | --- |
| `array:` | An array of arbitrary keys/values. |
| `bool:` | A `true` or `false` value. |
| `list:` | A list of one or more values. |
| `object:` | An object with nested `attributes:` of a given type. |
| `text:` | An arbitrary text value. |

The root of a schema is always of type `object:`.

## output:

Save the results in this placeholder.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of parsing the KATA document.

If omitted, the KATA document is parsed during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The `output:` placeholder receives a dictionary with the same structure as the KATA document.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

