---
id: "docs-automations-commands-log"
title: "Automations: log"
url: "https://cerb.ai/docs/automations/commands/log/"
summary: "This page provides information on the 'log' command used in Cerb automations to write data to the automation log with specified severity levels. It details the syntax for logging messages, including different severity levels such as notice, warning, error, and alert. Each log entry records the automation name, node, creation date, log level, and message, which can be accessed through data queries. The page serves as a guide for using the log command to assist with error reporting and debugging in automations."
tags: ["docs", "docs-automations"]
---
The **log:** command writes data to the automation log with a given severity. Automations that exit in the `error` state also create entries to assist with error reporting and debugging.

- [Syntax](#syntax)
  - [inputs:](#inputs)
  - [output:](#output)

```
start:
  log: This is a notice
  log.warn: This is a warning
  log.error: This is an error
  log.alert: This is an alert
```

Each log entry contains:

- automation name
- automation node
- created date
- log level (severity)
- message

Log entries are accessed with [data queries](/docs/data-queries/).

# Syntax

## inputs:

A log message.

## output:

(none)

