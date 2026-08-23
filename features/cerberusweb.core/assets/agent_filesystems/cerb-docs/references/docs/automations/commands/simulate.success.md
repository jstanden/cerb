---
id: "docs-automations-commands-simulate-success"
title: "Automations: simulate.success"
url: "https://cerb.ai/docs/automations/commands/simulate.success/"
summary: "This page covers the 'simulate.success' command, which is used inside an 'on_simulate' event handler to provide mock output and trigger the 'on_success' event of the enclosing command during automation simulation. It explains the optional output keys, usage examples, and how it relates to the simulation framework in Cerb automations."
tags: ["docs", "docs-automations"]
---
The **simulate.success:** command is used inside an [on\_simulate:](/docs/automations/#simulation) event handler to provide mock output and trigger the enclosing command's `on_success:` event during [simulation](/docs/automations/#simulation).

```
start:
  http.request:
    output: http_response
    inputs:
      method: GET
      url: https://api.example/
    on_simulate:
      simulate.success:
        status_code: 200
        content_type: application/json
        body: {"result":"ok"}
    on_success:
      return:
        body@key: http_response:body
```

- [Syntax](#syntax)
  - [output keys:](#output-keys)
  - [on\_simulate:](#on_simulate)
  - [on\_success:](#on_success)
  - [on\_error:](#on_error)

- [See also](#see-also)

# Syntax

## output keys:

The optional key/value pairs provided to `simulate.success:` are merged into the `output:` placeholder of the enclosing command, exactly as if the command had run and succeeded. The expected keys depend on the command being simulated (e.g. `status_code:`, `body:` for `http.request:`).

## on\_simulate:

`simulate.success:` is itself only valid inside an `on_simulate:` event handler. It cannot be used outside of simulation context.

## on\_success:

After `simulate.success:` runs, the enclosing command's `on_success:` event is executed with the simulated output available in the `output:` placeholder.

## on\_error:

Not applicable. `simulate.success:` always triggers the `on_success:` path. Use [simulate.error:](/docs/automations/commands/simulate.error/) to trigger the `on_error:` path instead.

# See also

- [Simulation](/docs/automations/#simulation) – overview of how simulation works in automations
- [simulate.error:](/docs/automations/commands/simulate.error/) – simulate a failed command outcome

