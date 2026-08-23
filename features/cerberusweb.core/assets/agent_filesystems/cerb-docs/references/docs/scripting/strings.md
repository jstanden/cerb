---
id: "docs-scripting-strings"
title: "Scripting Reference: Strings"
url: "https://cerb.ai/docs/scripting/strings/"
summary: "This page serves as a scripting reference for handling strings in Cerb. It covers the basics of defining strings using single or double quotes and demonstrates how to modify strings with filters. The page explains string concatenation using the tilde (`~`) operator to join multiple strings or variables. It also addresses managing whitespace in template text, showing how to prevent unintended spaces around commands using a dash (`-`). The content is part of a broader guide, with sections on variables, arrays, and objects indicated as adjacent topics."
tags: ["docs", "docs-scripting"]
---
A **string** is some text within a variable.

# Literal text

You enclose a string within single (`'`) or double (`"`) quotes.

You can modify a string with filters just like a variable:

```
{{"This is literal text"|truncate(7)}}
```

```
This is...
```

# Concatenation

You can join multiple strings or variables together with `~` (tilde):

```
{% set first_name = "Kina" %}
{% set last_name = "Halpue" %}
{% set full_name = first_name ~ " " ~ last_name %}
{{full_name}}
```

```
Kina Halpue
```

# Whitespace

One issue with treating all template text as output is that you can get unintended whitespace around your commands.

You can ignore whitespace at the beginning or end of a tag with a dash (`-`):

```
This text

{{-" has no leading or trailing whitespace "-}}

in it.
```

```
This text has no leading or trailing whitespace in it.
```

[\< Variables](/docs/scripting/variables/)

[Arrays and Objects \>](/docs/scripting/arrays-objects/)

