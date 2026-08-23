---
id: "docs-automations-commands-error"
title: "Automations: error"
url: "https://cerb.ai/docs/automations/commands/error/"
summary: "This page provides information on the **error:** command used in Cerb automations. It explains that this command is used to terminate an automation unsuccessfully by setting it to an `error` state and returning a specified error message to the caller. The page includes a syntax example demonstrating how to implement the command within an automation script."
tags: ["docs", "docs-automations"]
---
The **error:** command unsuccessfully terminates an [automation](/docs/automations/) in the `error` [state](/docs/automations/#exit-states) and returns a message.

# Syntax

```
start:
  error: An unexpected error occurred!
```

The error message is returned to the caller.

