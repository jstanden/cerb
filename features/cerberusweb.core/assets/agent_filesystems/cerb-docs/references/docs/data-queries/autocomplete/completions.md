---
id: "docs-data-queries-autocomplete-completions"
title: "Data Queries: Autocomplete Completions"
url: "https://cerb.ai/docs/data-queries/autocomplete/completions/"
summary: "This page provides information on how to use autocomplete.completions data queries to retrieve details about available autocomplete options on automations and automation events."
tags: ["docs"]
---
# autocomplete.completions

`autocomplete.completions` data queries return autocomplete suggestions for a KATA document.

This functionality is used by various [interactions](/docs/interactions/) in [automation](/docs/automations/) editors.

### Inputs

| `schema:` | The type of record (`automation` or `automation_event`). |
| `params:trigger:` | The extension ID of the automation trigger (e.g. `cerb.trigger.automation.function`). |
| `params:path:` | The optional colon-delimited KATA key path to autocomplete (e.g. `start:http.request:inputs:`). This defaults to the root. |

### Response Formats

- **dictionaries** (default) returns a table-based format suitable for [sheets](/docs/sheets/) and API results.

### Examples

- [query](#)
- [response](#)

- 
```
type:autocomplete.completions
schema:automation
params:(
  trigger:cerb.trigger.automation.function
  path:"start:"
)
format:dictionaries
```
- 
```
{
  "data": {
    "0": {
      "caption": "decision:",
      "snippet": "decision/${1:key}:\n\t${2:}",
      "description": "Run commands only in the first matching outcome",
      "score": 1000
    },
    "1": {
      "caption": "outcome:",
      "snippet": "outcome/${1:key}:\n\tif@bool: {{${2:condition}}}\n\tthen:\n\t\t${3:}",
      "description": "Run commands when conditions are true",
      "score": 1000
    },
    "2": {
      "caption": "error:",
      "snippet": "error:\n\t",
      "description": "Exit and return an error response",
      "score": 1000
    },
    "3": {
      "caption": "repeat:",
      "snippet": "repeat:\n\t",
      "description": "Repeat commands for every collection item",
      "score": 1000
    },
    "4": {
      "caption": "while:",
      "snippet": "while/${1:key}:\n\tif@bool: {{${2:condition}}}\n\tdo:\n\t\t${3:}",
      "description": "Repeat commands while conditions are true",
      "score": 1000
    },
    "5": {
      "caption": "return:",
      "snippet": "return:\n\t",
      "description": "Exit and return a successful response",
      "score": 1000
    },
    "6": {
      "caption": "set:",
      "snippet": "set:\n\t${1:key}: ${2:value}\n",
      "description": "Set one or more keys",
      "score": 1000
    },
    "7": {
      "caption": "api.command:",
      "snippet": "api.command:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Invoke low-level API commands",
      "score": 1000
    },
    "8": {
      "caption": "data.query:",
      "snippet": "data.query:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Get results from a data query",
      "interaction": "ai.cerb.automationBuilder.action.dataQuery",
      "score": 1000
    },
    "9": {
      "caption": "decrypt.pgp:",
      "snippet": "decrypt.pgp:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Decrypt a block of PGP encrypted text",
      "interaction": "ai.cerb.automationBuilder.action.pgpDecrypt",
      "score": 1000
    },
    "10": {
      "caption": "email.parse:",
      "snippet": "email.parse:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Parse an email message in RFC-5322 format",
      "interaction": "ai.cerb.automationBuilder.action.emailParser",
      "score": 1000
    },
    "11": {
      "caption": "encrypt.pgp:",
      "snippet": "encrypt.pgp:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Encrypt a block of text using PGP public keys",
      "interaction": "ai.cerb.automationBuilder.action.pgpEncrypt",
      "score": 1000
    },
    "12": {
      "caption": "file.read:",
      "snippet": "file.read:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Read attachment content",
      "score": 1000
    },
    "13": {
      "caption": "file.write:",
      "snippet": "file.write:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Write attachment content",
      "score": 1000
    },
    "14": {
      "caption": "function:",
      "snippet": "function:\n\turi: ${1:}\n\tinputs:\n\t\t${2:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Run an automation function",
      "interaction": "ai.cerb.automationBuilder.action.function",
      "score": 1000
    },
    "15": {
      "caption": "http.request:",
      "snippet": "http.request:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Execute a request to an HTTP endpoint",
      "interaction": "ai.cerb.automationBuilder.action.httpRequest",
      "score": 1000
    },
    "16": {
      "caption": "kata.parse:",
      "snippet": "kata.parse:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Parse a KATA tree and substitute placeholders",
      "score": 1000
    },
    "17": {
      "caption": "log:",
      "snippet": "log: ${1:This is a debug message}",
      "description": "Log a message with debug severity",
      "score": 1000
    },
    "18": {
      "caption": "log.error:",
      "snippet": "log.error: ${1:This is an error message}",
      "description": "Log a message with error severity",
      "score": 1000
    },
    "19": {
      "caption": "log.warn:",
      "snippet": "log.warn: ${1:This is a warning message}",
      "description": "Log a message with warning severity",
      "score": 1000
    },
    "20": {
      "caption": "metric.increment:",
      "snippet": "metric.increment:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Record new samples on a metric",
      "interaction": "ai.cerb.automationBuilder.action.metricIncrement",
      "score": 1000
    },
    "21": {
      "caption": "queue.pop:",
      "snippet": "queue.pop:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Read messages from a queue",
      "score": 1000
    },
    "22": {
      "caption": "queue.push:",
      "snippet": "queue.push:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Write messages to a queue",
      "score": 1000
    },
    "23": {
      "caption": "record.create:",
      "snippet": "record.create:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Create a new record of a given type",
      "interaction": "ai.cerb.automationBuilder.action.recordCreate",
      "score": 1000
    },
    "24": {
      "caption": "record.delete:",
      "snippet": "record.delete:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Delete a target record by type and ID",
      "interaction": "ai.cerb.automationBuilder.action.recordDelete",
      "score": 1000
    },
    "25": {
      "caption": "record.get:",
      "snippet": "record.get:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Read a target record by type and ID",
      "interaction": "ai.cerb.automationBuilder.action.recordGet",
      "score": 1000
    },
    "26": {
      "caption": "record.search:",
      "snippet": "record.search:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Search a record type with a query and return matches",
      "interaction": "ai.cerb.automationBuilder.action.recordSearch",
      "score": 1000
    },
    "27": {
      "caption": "record.update:",
      "snippet": "record.update:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Update a target record by type and ID",
      "interaction": "ai.cerb.automationBuilder.action.recordUpdate",
      "score": 1000
    },
    "28": {
      "caption": "record.upsert:",
      "snippet": "record.upsert:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Insert or update a record based query matches",
      "interaction": "ai.cerb.automationBuilder.action.recordUpsert",
      "score": 1000
    },
    "29": {
      "caption": "storage.delete:",
      "snippet": "storage.delete:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Delete a persistent key",
      "interaction": "ai.cerb.automationBuilder.action.storageDelete",
      "score": 1000
    },
    "30": {
      "caption": "storage.get:",
      "snippet": "storage.get:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Read a persistent value by key",
      "interaction": "ai.cerb.automationBuilder.action.storageGet",
      "score": 1000
    },
    "31": {
      "caption": "storage.set:",
      "snippet": "storage.set:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Write a persistent value by key",
      "interaction": "ai.cerb.automationBuilder.action.storageSet",
      "score": 1000
    },
    "32": {
      "caption": "var.expand:",
      "snippet": "var.expand:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Expand a dictionary by key path",
      "score": 1000
    },
    "33": {
      "caption": "var.push:",
      "snippet": "var.push:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Append to a list by key path",
      "score": 1000
    },
    "34": {
      "caption": "var.set:",
      "snippet": "var.set:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Set a key by path",
      "score": 1000
    },
    "35": {
      "caption": "var.unset:",
      "snippet": "var.unset:\n\tinputs:\n\t\t${1:}\n\toutput: results\n\t#on_simulate:\n\t#on_success:\n\t#on_error:\n",
      "description": "Unset a key by path",
      "score": 1000
    }
  },
  "_": {
    "type": "autocomplete.completions",
    "schema": "automation",
    "format": "dictionaries"
  }
}
```

