---
name: mail-replies
description: Drafting a customer-facing email reply on a Cerb ticket -- interleaved quoting, what may and may not be asserted, the `#signature` token, and house typography. Load it before writing or revising any reply.
---

# Writing a customer reply

## Substance

- Match the worker's voice and the tone of the thread; default to clear, concise, professional, and warm.
- Only state facts supported by the ticket, the worker's instructions, or established knowledge. Never invent order numbers, policies, dates, commitments, or account details. If something you need is missing, ask the worker rather than guessing.
- Write for the customer: no internal jargon, ticket IDs, or notes-to-self in the reply body.
- If the draft contains a `#signature` token, leave it exactly as-is and keep it in place. It is a placeholder that Cerb replaces on send with the correct signature for the sending group/bucket address -- do not rewrite it, move it into a quoted block, expand it into a literal signature, or delete it. If your edit rewrites the whole body, carry the token through to the end of the new text.

## Structure: interleave

We prefer interleaved (inline) replies over top-posting. Your text goes BELOW the quote it answers -- never a block of new prose stacked above the thread. When the customer's message raises more than one point, answer it point by point:

- Keep the `>` quoted block for the point you're answering, trimmed to just enough text to make the context obvious -- usually a sentence or two, never the whole message.
- Put your answer immediately below that quote, unquoted, then move on to the next quoted point and repeat.
- Drop quoted material you aren't responding to: signatures, mail headers, pleasantries, and prior thread history. Never quote a block and leave it unanswered.
- Preserve the customer's original wording and the `>` prefix inside quotes; if you shorten within a quoted line, mark the elision ("...") rather than silently rewriting them.
- For a single-topic message with nothing to interleave, just write the reply directly -- don't manufacture a quote to answer. "Directly" means the body is your answer; quoted thread history the draft already carries stays below it, trimmed or left alone, never moved above your text.

## Formatting and typography

- Check the draft's format before you write, and match it. Plain text is the default and suits most replies; Markdown syntax in a plaintext reply is sent literally, so the customer sees `**asterisks**` and bare parenthesised URLs. If the reply genuinely calls for Markdown -- a list, a link, emphasis that carries meaning -- turn formatting on first, then write it. If it is already on, free use of Markdown is implied: use it without asking. Never turn formatting off; the worker may already have written Markdown.
- Keep headings rare in either mode. A reply is an email, not an article -- a short lead-in sentence or a blank line usually does the work an `##` heading would, and a wall of headings reads as a document dumped on the customer.
- Never hard-wrap your own text. Write each paragraph as one unbroken line, however long it runs. Cerb renders a single newline as a literal line break in Markdown and sends it unchanged in plaintext, so wrapping at a fixed width reaches the customer as a stack of short ragged lines that cannot reflow to their screen. Blank lines between paragraphs, and the breaks that separate a quoted block from your answer, are structure and stay. Quoted customer text keeps whatever wrapping it arrived with -- do not re-flow it.
- Do not use emoji unless the worker explicitly asks for them.
- Prefer plain ASCII punctuation. Write "--" instead of a Unicode em dash, and use "..." instead of a Unicode ellipsis.
- Reach for a dash at all only when it is genuinely the best punctuation for the sentence; a comma, colon, or period is usually clearer. Keep them rare.
- The same applies to quoted context you trim: mark an elision with "..." and otherwise leave the customer's own characters untouched, including any Unicode they used.
- When you show code in a chat message, wrap it in a Markdown "~~~" code fence rather than backticks. For KATA, keep proper two-space indentation inside the fence so the worker can copy and paste it directly.
