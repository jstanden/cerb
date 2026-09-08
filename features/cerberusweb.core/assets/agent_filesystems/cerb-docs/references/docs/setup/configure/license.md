---
id: "docs-setup-configure-license"
title: "Subscription"
url: "https://cerb.ai/docs/setup/configure/license/"
summary: "Self-hosted Cerb is free, with unlimited workers and unlimited simultaneous logins. What a subscription raises is concurrency -- how much background work and how many AI agent turns the installation can run at once. Every installation has a floor of three concurrency slots, and a subscription carrying fewer than three grants three. A subscription also raises how many agent turns a single background worker carries at once, from two to six. An expired subscription drops concurrency back to the community floor while every feature keeps working and workers stay unlimited. This page documents where the subscription lives in Setup, what the concurrency numbers mean, why a licensed self-hosted installation sees no concurrency panel while Community and Cerb Cloud do, how the seat usage panel reports the number a subscription is priced on, and why a Cerb Cloud subscription does not show the key-entry form."
tags: ["docs"]
---
**Self-hosted Cerb is free.** Create as many [workers](/docs/workers/) as you like, and sign in as many of them at once as you like – neither is limited, with or without a subscription.

Your subscription is entered at **Setup » Configure » Subscription**.

- [What a subscription raises](#what-a-subscription-raises)
- [Seat usage](#seat-usage)
- [When a subscription lapses](#when-a-subscription-lapses)
- [Cerb Cloud](#cerb-cloud)

### What a subscription raises

A subscription raises **concurrency**: how much work your installation runs at the same time.

| What | Community | With a subscription |
| --- | --- | --- |
| Workers | Unlimited | Unlimited |
| Simultaneous logins | Unlimited | Unlimited |
| Concurrency slots | 3 | As many as it carries |
| Agent turns per slot | 2 | 6 |

A **Concurrency** panel draws what _this_ installation runs at once, wherever there's a ceiling worth drawing. Each slot in the pool is one block, on two rows – **Batch jobs** and **Agent turns** – and the slots that both rows cover are the ones either kind of work may take. This is a **Community** installation's, with its three slots:

 

**A licensed self-hosted installation doesn't see that panel.** A subscription removes the ceiling entirely, so there's nothing left to draw here, and the pool it sizes for itself is drawn on **Setup » Configure » [Queues](/docs/setup/configure/queues/)** alongside the controls that set it. The panel appears where a real ceiling exists – a **Community** installation, whose three slots are a limit, and **Cerb Cloud**, whose pool is provisioned rather than self-sized.

[Concurrency slots](/docs/queues/#concurrency-slots) are what background work draws from – [queue](/docs/queues/) drains, bulk updates, imports, exports, [scheduler](/docs/setup/configure/scheduler/) jobs that run in parallel, and [AI agent](/docs/agents/) turns. More slots means more of that work happening at once rather than in sequence. Every installation has a floor of **three**, and a subscription carrying fewer than three grants three.

**Agent turns per slot** is a multiplier on top of that. An agent turn spends nearly all of its life waiting on a model provider rather than working, so one slot can hold several turns at once and take the next as each finishes. That's usually the number a busy installation feels most.

The two multiply into the ceiling the panel states in words, which on a three-slot installation reads _2 of 3 slots can run agent turns (2 sessions each, max 4)_. Note it's **2 of 3**, not 3: only the [slow lane](/docs/queues/#lanes) and the commons run agent turns, so the multiplier applies to the slots that can take one rather than to the whole pool.

It's a ceiling rather than a reservation. Scheduled jobs draw from the same lane, and a provider's own rate limits apply on top of it.

The turn multiplier follows the subscription and isn't configurable. The slot pool is: fixed at **three** on a Community installation, yours to set on **Setup » Configure » [Queues](/docs/setup/configure/queues/)** with a licensed subscription, and set by the platform on Cerb Cloud.

This page answers how much capacity you have. **Setup » Configure » [Queues](/docs/setup/configure/queues/)** answers what that capacity is doing right now – the same lane split, with a live view of which slots are busy.

### Seat usage

Cerb reports your **seat count** on this page, so it's never something to work out by hand. The panel is headed with the figure itself – _N seats average over M months_ – and draws one row per month below it, with one block for each seat that month against a fixed track.

A seat is a **person who signed in**, counted once for the month whether they worked one day of it or all of them. Two things mark a worker active – signing in, and sending mail – so a worker who drives Cerb entirely through the [API](/docs/api/), and never holds a session, still counts as the worker it acts as.

The average divides by the months **on record**, not by twelve. A month with no recorded activity produces no row and isn't counted against you, and a new installation averages over the months it has actually run. A track is at least ten cells wide, so a small installation reads as small rather than as full.

Seats are how a subscription is [priced](/pricing/#seats), never something enforced. Nothing here blocks a login, ends a session, or warns you for going over – the number is reported so you can price against it, and running above it interrupts nobody.

This panel is shown on every installation, including **Community** and **Cerb Cloud**.

A brand new installation shows no rows at all, and says so -- worker activity fills in as the scheduler runs.

### When a subscription lapses

A subscription is **time-based**. Concurrency follows the subscription term rather than the version you installed, so it ends when the term does, on whatever version you're running.

An expired subscription drops concurrency back to the community floor – three slots, at two agent turns each. **Every feature keeps working**, workers and logins stay unlimited, and no data is touched – the installation simply runs less at once until the subscription is renewed.

### Cerb Cloud

If you have a [Cerb Cloud](/pricing/) subscription, what you won't see is the **key-entry form** – your subscription is managed with your account rather than with a key, and Cerb refuses the change server-side rather than merely hiding the form. Everything else on the page is there: the plan chip, the Concurrency panel drawing the pool we provision for you, and your seat usage.

