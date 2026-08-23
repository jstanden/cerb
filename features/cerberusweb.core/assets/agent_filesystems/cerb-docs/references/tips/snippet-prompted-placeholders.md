---
id: "tips-snippet-prompted-placeholders"
title: "Use prompted placeholders in snippets"
url: "https://cerb.ai/tips/snippet-prompted-placeholders/"
summary: "This page provides guidance on using prompted placeholders in Cerb snippets. It explains how snippets can be used to quickly insert predefined text into messages, similar to copying and pasting from a shared clipboard. The page highlights the functionality of placeholders, which automatically insert known details such as a worker's name or a ticket's subject. It also addresses the need for last-minute customization by introducing prompted placeholders, allowing users to input additional details or choose specific content before inserting the snippet. This feature enhances the flexibility and personalization of communication within Cerb."
tags: ["tips"]
---
[Snippets](/docs/tickets/#snippets) let you quickly insert predefined text into messages. You can think of snippets as copying and pasting from a giant shared clipboard.

You can also use placeholders in a snippet to automatically insert details you already know – a worker's name, a ticket's subject, custom fields, etc.

However, sometimes you need to include last minute details in a snippet, or situationally choose which paragraphs to include. We've seen snippets in various client environments with manual placeholders like `[[WRITE TEXT HERE]]`.

Cerb already handles that with _prompted placeholders_.

When creating a snippet, you can add prompted placeholders like:

 

Then when a worker selects that snippet they'll be prompted to customize it before it inserts:

 

The inserted text then incorporates the extra details:

 
