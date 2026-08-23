---
id: "docs-automations-events-queue-consumer"
title: "queue.consumer"
url: "https://cerb.ai/docs/automations/events/queue.consumer/"
summary: "This page documents the 'queue.consumer' automation event in Cerb. The event is dispatched by the Background Queue scheduler when an automation-backed queue has messages available to process. Placeholders include the queue record, the optional queue job that owns the batch, and an array of message dicts with their uuid, payload, available_at, and job_id. The event has no outputs -- any non-error response is treated as successful delivery of the entire batch."
tags: ["docs", "docs-automations"]
---
**queue.consumer** [automation](/docs/automations/) [events](/docs/automations/#events) are triggered by the [Background Queue scheduler](/docs/queues/#background-queue-scheduler) to process a batch of messages from an [automation-backed queue](/docs/queues/#automation-backed-queues).

The batch size is configured per [queue](/docs/records/types/queue/) – use `1` for expensive per-message operations, or `10`-`100+` to amortize batch operations.

# Placeholders

The automation event [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `queue_*` | record | The [queue](/docs/records/types/queue/) being consumed. Supports key expansion. |
| `queue_job_*` | record | The [queue job](/docs/records/types/queue_job/) that owns this batch, if any. Supports key expansion. |
| `messages` | array | An array of `{uuid, message, available_at, job_id}` dicts for the batch. |

# Outputs

(none)

Any non-error response is treated as successful delivery of the entire batch. If the automation returns an `error:` outcome, the messages are marked failed and retried per the [queue's retry policy](/docs/queues/#retries).

