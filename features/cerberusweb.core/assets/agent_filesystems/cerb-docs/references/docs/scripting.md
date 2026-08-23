---
id: "docs-scripting"
title: "Scripting Reference"
url: "https://cerb.ai/docs/scripting/"
summary: "This page provides a scripting reference for Cerb, focusing on the use of a templating language based on Twig to generate dynamic text for various features like automations and snippets. It highlights the need for dynamic content generation in tasks such as setting record fields, sending messages, and executing HTTP requests. The page explains how Cerb's templating language simplifies text output compared to traditional programming languages by treating everything as text unless a special command is encountered. Key topics covered include variables, strings, arrays, objects, dates, conditional logic, operators, loops, regular expressions, JSON, XML, commands, functions, filters, and tests."
tags: ["docs", "docs-scripting"]
---
 

Features like [automations](/docs/automations/) and [snippets](/docs/snippets/) often need to generate dynamic text.

For instance:

- Setting record fields
- Sending email messages
- Responding to chat messages
- Sending text messages
- Executing HTTP requests
- Generating notifications
- …and so on

The content of this text may need to vary depending on any number of factors – the current worker, record, automation inputs, day of week, etc.

In a traditional programming language, you typically generate text output like:

```
print("Hello, " + firstName + "!");
```

This is cumbersome when you're dealing with a lot of text.

Instead, Cerb scripting is a full-featured templating language based on Twig[1](#fn:twig). A templating language makes the simple assumption that everything you type is text output until it encounters a special command.

The example code above would instead be written as:

```
Hello, {{first_name}}!
```

# Topics

- [Variables](/docs/scripting/variables/)
- [Strings](/docs/scripting/strings/)
- [Arrays and Objects](/docs/scripting/arrays-objects/)
- [Dates](/docs/scripting/dates/)
- [Conditional Logic](/docs/scripting/conditional-logic/)
- [Operators](/docs/scripting/operators/)
- [Loops](/docs/scripting/loops/)
- [Regular Expressions](/docs/scripting/regex/)
- [JSON](/docs/scripting/json/)
- [XML](/docs/scripting/xml/)
- [Commands](/docs/scripting/commands/)
- [Functions](/docs/scripting/functions/)
- [Filters](/docs/scripting/filters/)
- [Tests](/docs/scripting/tests/)

# References

1. Twig: The flexible, fast, and secure template engine for PHP - https://twig.symfony.com&nbsp;[↩](#fnref:twig)

