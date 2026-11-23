---
id: "docs-setup-configure-queues"
title: "Queues"
url: "https://cerb.ai/docs/setup/configure/queues/"
summary: "This page documents Setup -- Configure -- Queues, a live view of the concurrency pool an installation runs background work in. It covers how the pool divides into a fast lane, a slow lane, and the commons both may draw from, drawn one block per slot so the overlap between the lanes is visible; the Slot lanes and In use panels and the sentences they state in words, including how many slots can run agent turns and how many sessions each carries; the live occupancy row showing which slots are busy this second; the countdown ring that doubles as the refresh interval picker and the toggle that turns refreshing off; why an installation whose slot usage cannot be read says so instead of drawing an empty pool; and how a licensed installation sizes the pool and divides it between the lanes. It explains the difference between this page, which answers what the capacity is doing, and the Subscription page, which answers how much capacity there is."
tags: ["docs"]
---
**Setup » Configure » Queues** shows what the concurrency pool is divided into, and what's running in it right now.

 

- [Two pages, two questions](#two-pages-two-questions)
- [The lane split](#the-lane-split)
  - [What the panel says in words](#what-the-panel-says-in-words)

- [Live occupancy](#live-occupancy)
- [Configuring the pool](#configuring-the-pool)
- [What the numbers mean](#what-the-numbers-mean)
- [Related](#related)

# Two pages, two questions

**Setup » Configure » [Subscription](/docs/setup/configure/license/)** answers _how much capacity do we have_. This page answers _what is that capacity doing_.

Both draw the lane split from the same component, so the two can never come to disagree about the shape of the pool.

This page is limited to administrators.

# The lane split

[Concurrency slots](/docs/queues/#concurrency-slots) are divided into a **fast lane**, a **slow lane**, and a **commons** that either may draw from. Work that holds a slot for as long as a model takes to answer – an [AI agent](/docs/agents/) turn, a [scheduler](/docs/setup/configure/scheduler/) job that runs in parallel – draws from the slow lane; work that yields every batch – a bulk update, an import, an export – draws from the fast one. See [lanes](/docs/queues/#lanes) for why the axis is how long a slot is _held_ rather than how important the work is.

The split is drawn **one block per slot**, so it's read directly rather than inferred from a percentage. The page labels the two rows by the work rather than by the lane: **Batch jobs** is the fast lane and **Agent turns** is the slow one. Each is one contiguous range of slots, and the range they share is the commons – drawn as the place the two rows overlap rather than as a third row, which would suggest a third kind of work where there are only two.

The rows are deliberately **wider** than the matching numbers in the configuration form, because they're a different quantity. **Batch jobs** covers every slot batch work may take – its dedicated reserve _and_ the commons – while the form's **Batch only** field is the reserve alone. On a 25-slot pool at the default split, **Batch only** is `6`, but the **Batch jobs** row spans slots 1 through 19. The commons gets no label of its own on purpose: it's the region the two rows overlap, not a third kind of slot.

A job names its lane in code and is handed a slot or nothing; that part isn't configurable. The lane _sizes_ are, on a licensed installation – see [Configuring the pool](#configuring-the-pool) below.

## What the panel says in words

The panel is headed **Slot lanes**, under _"Which slots each kind of background work may take."_ Above the diagram it states the agent-turn ceiling as a sentence:

```
22 of 25 slots can run agent turns (6 sessions each)
```

The first number is the slow lane plus the commons, not the whole pool – only those slots run agent turns. The second is the pool. **This page stops there.** The [Subscription](/docs/setup/configure/license/) page renders the same sentence with the product on the end – _(6 sessions each, max 132)_ – because that page's question is how much capacity exists, and this one's is what the capacity is doing.

Beneath the diagram sits the plainest description of the split in the product:

> Batch work is imports, exports, bulk updates and reindexing; it releases a slot every batch. Agent turns hold one for as long as the model takes to answer. Shared slots serve either.

# Live occupancy

Beneath the lane split, a second panel headed **In use** – _"The slots held right now, across every lane"_ – marks the slots that are **busy this second**, over a count reading `9 of 25 slots in use`.

That count is filled in by the browser after the first reading lands. Its server-rendered value is a literal `-`, so a page caught mid-load reads `- of 25 slots in use`. That's the count arriving, not a pool that can't be read – an unreadable pool says so in words instead, as below.

The reading is polled rather than pushed, so the page carries its own **countdown ring** showing when the next one lands. The ring doubles as the interval picker – the same control a workspace [worklist](/docs/worklists/) tab uses – and the toggle beside it turns refreshing off entirely.

The ring shows **two different numbers**: the figure at its centre is the seconds remaining until the next poll, counting down, while the smaller one beneath is the interval you've selected, which doesn't move. A ring reading `3s` over `5S` is three seconds _from_ the next poll on a five-second cycle, not two values of one thing.

**Held slots are scattered rather than packed to the left.** The allocator shuffles, so the positions that light up carry no meaning – neither an order of use nor a fill direction. Read the row as a count, not as a shape.

The occupancy row is drawn in **a single color**, rather than tinting a busy slot by the row that took it. That's not an oversight: the reservation records only that a slot is _held_, not which kind of work holds it, so coloring it would mean inventing an attribution the lock doesn't carry.

Two details worth knowing:

- The countdown **holds while the tab is hidden**, rather than draining in the background and firing a burst of catch-up polls when you come back to it.
- The next cycle starts when an answer **lands**, not when the request went out, so a slow reading doesn't compress the following one.

**An unreadable pool says so.** An idle installation and a failed read are indistinguishable in the numbers -- both are zero busy slots. When the usage can't be read, the page reports that rather than drawing an empty pool, because quietly showing zero would be the most reassuring possible way to be wrong.

# Configuring the pool

On a licensed installation, the **Slot lanes** section is also where you size the pool and decide how it divides. There's no configuration file to edit and no restart.

Batch work is imports, exports, bulk updates, and reindexing; it releases a slot every batch. Agent turns hold one for as long as the model takes to answer. Shared slots serve either.

| Field | What it does |
| --- | --- |
| **Slots** | The size of the pool. The floor is three, and there's no ceiling |
| **Batch only** | Slots reserved for batch work. At least one |
| **Shared** | What's left over – slots either kind of work may take. Derived, not entered |
| **Agent turns only** | Slots reserved for AI agent turns. At least one |

You set three numbers: the pool size and the two **dedicated** lanes. **Shared** is whatever the pool has left after those two, which is why the form labels it _What's left_. That's also why raising the slot count lands in **Shared** – you haven't reserved the new slots for either kind of work, so both may take them.

**Auto** resets the split to a quarter for each dedicated lane, with the rest shared.

Below the fields, a line of feedback tracks what you're typing. It has four states, and only the last is the steady one:

| What it says | What it means |
| --- | --- |
| `A pool needs at least 3 slots.` | **Slots** is below the floor |
| `Every lane needs at least one slot.` | A dedicated lane has been emptied |
| `The lanes total X, but the pool is N.` | The two reserves don't fit the pool you've asked for |
| `X for batch, Y shared, Z for agent turns.` | The split is valid – this is what will be saved |

It's validation feedback rather than a reading of the pool, so it follows the fields as you edit them rather than the installation as it runs. A **Community** installation never shows it at all – there's nothing to validate when the fields are locked.

A **Community** installation sees the same four fields, filled in and locked: **Slots** `3`, with `1` in each lane. The panel is headed **Locked without a subscription**, and it has no Save and no **Auto** – a subscription is what makes the fields editable, not what makes them appear.

On [Cerb Cloud](/pricing/) the lanes are yours to configure, but **Slots** isn't a field at all – the count is shown as a value, because the platform provisions the compute and sets it. A greyed-out input would still read as something you could change with the right permission, and on Cloud the pool isn't editable here at any tier.

# What the numbers mean

Every installation has a floor of **three** slots, licensed or not.

# Related

- [Queues](/docs/queues/) – what queues are, and how lanes and concurrency slots work
- [Subscription](/docs/setup/configure/license/) – what a subscription raises
- [Scheduler](/docs/setup/configure/scheduler/) – jobs that draw from the same pool

