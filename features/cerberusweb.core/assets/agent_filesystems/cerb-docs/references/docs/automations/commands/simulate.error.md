---
id: "docs-automations-commands-simulate-error"
title: "Automations: simulate.error"
url: "https://cerb.ai/docs/automations/commands/simulate.error/"
summary: "This page covers the 'simulate.error' command, which is used inside an 'on_simulate' event handler to provide mock error output and trigger the 'on_error' event of the enclosing command during automation simulation. It explains the optional output keys, usage examples, and how it relates to the simulation framework in Cerb automations."
tags: ["docs", "docs-automations"]
---
The **simulate.error:** command is used inside an [on\_simulate:](/docs/automations/#simulation) event handler to provide mock error output and trigger the enclosing command's `on_error:` event during [simulation](/docs/automations/#simulation).

```
start:
  http.request:
    output: http_response
    inputs:
      method: GET
      url: https://api.example/
    on_simulate:
      simulate.error:
        error: Connection timed out
    on_success:
      return:
        body@key: http_response:body
    on_error:
      return:
        error@key: http_response:error
```

- [Syntax](#syntax)
  - [output keys:](#output-keys)
  - [on\_simulate:](#on_simulate)
  - [on\_success:](#on_success)
  - [on\_error:](#on_error)

- [See also](#see-also)

# Syntax

## output keys:

The optional key/value pairs provided to `simulate.error:` are merged into the `output:` placeholder of the enclosing command, exactly as if the command had run and failed. Typically this includes an `error:` key with a message string.

## on\_simulate:

`simulate.error:` is itself only valid inside an `on_simulate:` event handler. It cannot be used outside of simulation context.

## on\_success:

Not applicable. `simulate.error:` always triggers the `on_error:` path. Use [simulate.success:](/docs/automations/commands/simulate.success/) to trigger the `on_success:` path instead.

## on\_error:

After `simulate.error:` runs, the enclosing command's `on_error:` event is executed with the simulated error output available in the `output:` placeholder.

If the enclosing command has no `on_error:` handler, the automation exits in the `error` [state](/docs/automations/#exit-states).

# See also

- [Simulation](/docs/automations/#simulation) – overview of how simulation works in automations
- [simulate.success:](/docs/automations/commands/simulate.success/) – simulate a successful command outcome

