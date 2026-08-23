---
id: "docs-automations-triggers-automation-timer"
title: "automation.timer"
url: "https://cerb.ai/docs/automations/triggers/automation.timer/"
summary: "This page provides detailed information about the 'automation.timer' feature in Cerb, which allows automations to be executed at a future time or at various intervals using timers. It explains how these automations can pause and resume between executions through continuations. The page outlines the process of selecting an automation using events KATA on the first invocation and describes the input and output structures, including custom input values and the conditions for pausing (await) and concluding (return) the automation. It also details how the timer is rescheduled based on whether it has a recurring schedule or is a one-shot timer."
tags: ["docs", "docs-automations"]
---
**automation.timer** [automations](/docs/automations/) are executed by [timers](/docs/automations/#timers) at a future time, or at various intervals. They support [continuations](/docs/automations/#continuations) to pause and resume between executions.

 

On the first invocation of the timer, an automation is selected using [events KATA](/docs/automations/#events). This may optionally provide [inputs](/docs/automations/#inputs).

- [Inputs](#inputs)
- [Outputs](#outputs)
  - [await:](#await)
  - [return:](#return)

# Inputs

The automation [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `timer_*` | dictionary | The parent [timer](/docs/automations/#timers) dictionary |
| `inputs` | dictionary | [Custom input](/docs/automations/#inputs) values from the caller |

# Outputs

## await:

When the automation pauses in the `await` state, the timer is rescheduled to continue at a future date.

| Key | Type | Notes |
| --- | --- | --- |
| `until` | timestamp | The future UNIX timestamp to resume the automation at the current point |

```
start:
  # Run some commands
  await:
    until@date: +5 mins
```

## return:

When the automation concludes:

- If the timer has a recurring schedule, it is rescheduled for the next occurrence.

- Otherwise, a one-shot timer is disabled (or optionally deleted) at conclusion.

