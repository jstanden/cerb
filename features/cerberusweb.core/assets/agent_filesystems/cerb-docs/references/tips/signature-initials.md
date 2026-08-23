---
id: "tips-signature-initials"
title: "Initials in signatures"
url: "https://cerb.ai/tips/signature-initials/"
summary: "This page provides guidance on using the Twig templating language to manipulate text in signatures, specifically focusing on extracting initials from a worker's last name. It explains how to use substring syntax to display the first initial of a last name and offers examples of other text manipulations, such as extracting the first letter, everything except the first letter, and the last three letters of a string."
tags: ["tips"]
---
A client asked:

> How can I just use the initial of a worker's last name in their signature?

Our templating language is based on Twig, which includes a handy syntax for extracting substrings from text:

`{{"this is a string"[start:length]}}`

- `start` specifies the character to start from, with the first position being 0.
- `length` specifies how many characters to extract.

So you could display the first initial of a worker's last name using:

```
{% set first_name = "Kina" %}
{% set last_name = "Halpue" %}
{{first_name}} {{last_name[0:1]}}
```

Which would output:

```
Kina H
```

You can also do things like:

```
{% set first_name = "Kina" %}
{% set last_name = "Halpue" %}

{# Shortcut for the first letter #}
{{last_name[:1]}}

{# Everything except for the first letter #}
{{last_name[1:]}}

{# The last three letters #}
{{last_name[-3:]}}
```

Which outputs:

```
H

alpue

pue
```

