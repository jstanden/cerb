---
id: "tips-reflow-reply-quotes"
title: "Reflow quotes in a reply"
url: "https://cerb.ai/tips/reflow-reply-quotes/"
summary: "This page provides guidance on how Cerb reflows quoted text in email replies to enhance readability, adhering to a longstanding internet convention of limiting line length to 76 characters. It explains the historical context of this practice, rooted in the limitations of older terminal displays, and highlights modern design research that supports shorter line lengths for optimal readability. The page also offers practical instructions on using Cerb's features, such as starting a paragraph with a `>` character and employing a keyboard shortcut (`<CTRL>` + `<SHIFT>` + `Q`) to automatically reflow quotes, making it easier for users to format text efficiently."
tags: ["tips"]
---
When you reply to a message, Cerb "reflows" the quoted text so that each line is no longer than 76 characters. This comes from a decades old Internet convention[1](#fn:rfc2045) intended to improve readability.

At the time, most terminals displayed around 80 characters per line and 25 lines per screen. It was important to not send lines longer than the recipient could read, because the quotes became very difficult to read. The length of each line could vary wildly.

Even though computer monitors today can display hundreds of characters per line, design research shows that the ideal line length for human readers is still around 50-60 characters[2](#fn:typographie).

When you need to paste some text into an email message and treat it as a quote, you don't need to do all this work yourself.

You can simply start a paragraph with a `>` character and then use the `<CTRL>` + `<SHIFT>` + `Q` to reflow the quotes.

For instance, this long line of text:

```
> This is a sentence with details that are pertinent to the discussion. The second sentence here is a side-hand comment that doesn't contribute to the discussion. There is one other interesting detail at the end of the third sentence.
```

Is reflowed with the keyboard shortcut to:

```
> This is a sentence with details that are pertinent to the discussion. The
> second sentence here is a side-hand comment that doesn't contribute to the
> discussion. There is one other interesting detail at the end of the third
> sentence.
```

If you delete that second sentence from the quote, you're left with:

```
> This is a sentence with details that are pertinent to the discussion. [...] interesting detail at the end of the third
> sentence.
```

You can then use the keyboard shortcut again for a nice short quoted block:

```
> This is a sentence with details that are pertinent to the discussion.
> [...] interesting detail at the end of the third sentence.
```

It takes a little bit of work on your part, but it makes things much easier for the reader.

## References

1. IETF: RFC-2045 Multipurpose Internet Mail Extensions - https://www.ietf.org/rfc/rfc2045.txt&nbsp;[↩](#fnref:rfc2045)

2. Amazon Typographie: A Manual of Design. Emil Ruder. - https://www.amazon.com/Typographie-Manual-Design-Emil-Ruder/dp/3721200438&nbsp;[↩](#fnref:typographie)

