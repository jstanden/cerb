---
id: "docs-custom-fieldsets"
title: "Custom Fieldsets"
url: "https://cerb.ai/docs/custom-fieldsets/"
summary: "This page explains the concept of custom fieldsets in Cerb, which allow users to group related custom fields together and add them to records as a unit. It provides an example of a 'SLA' (Service Level Agreement) fieldset for organization records, detailing how fieldsets can automate processes like ticket assignment and client reminders. The page also highlights the versatility of fieldsets in categorizing broad record types, such as using specific fieldsets for different asset types like vehicles and computers."
tags: ["docs"]
---
You can group related [custom fields](/docs/custom-fields/) together into a **fieldset**. When a fieldset is optionally added to a [record](/docs/records/), it includes all of its fields at the same time.

For instance, you could add this _"SLA"_ (Service Level Agreement) fieldset to [organization](/docs/orgs/) records:

| Field | Type | Value |
| --- | --- | --- |
| Level | picklist (Standard, Priority, Enterprise) | `Enterprise` |
| Expires | date | `2025-12-31 23:59:59 UTC` |

Every time a new ticket is opened, an [automation](/docs/automations/) could check the sender's SLA to determine the assignment and due date (but only if that obligation hasn't expired). Bots could also remind a client to renew when their SLA is about to expire.

Fieldsets can be used to further subdivide a broad record type into classifications. For instance, asset records could have custom fieldsets for _"Vehicle"_ and _"Computer"_. You could then create a list of only assets that are vehicles based on their color, mileage, passenger capacity, etc.

