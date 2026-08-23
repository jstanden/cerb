---
id: "docs-groups-signatures"
title: "Email Signatures"
url: "https://cerb.ai/docs/groups/signatures/"
summary: "This page provides information on how email signatures are managed in Cerb. It explains that signatures are automatically appended to emails using a `#signature` placeholder and can be configured for entire groups or buckets rather than individual workers. This approach ensures consistent signatures across teams and allows for easy switching between different brands and markets. The page also details how signatures can include placeholders for personalization, and how workers can adjust their preferences for signature placement or disable automatic insertion. Signature records can be created and modified through the 'Search » Email Signatures' feature."
tags: ["docs"]
---
When [workers](/docs/workers/) send email, a **signature** is automatically appended to the bottom of their message. This is represented by a `#signature` placeholder when composing the message.

You can create and modify signature records from **Search&nbsp;» Email Signatures**.

Unlike traditional email applications, signatures are configured for an entire [group](/docs/groups/) or [bucket](/docs/buckets/), rather than per-worker. This makes it easy to generate consistent signatures for your entire team, and automatically switch between signatures for various brands and markets.

If the current bucket has a signature configured then it will be selected; otherwise, the signature of the group will be used.

Signatures may contain placeholders like `and` to automatically personalize their contents for each worker.

Workers can modify their preferences to have signatures inserted above or below a quoted message. Automatic signature insertion can also be disabled; and workers can manually insert their signature by typing `#signature` on its own line.

