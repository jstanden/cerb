---
id: "docs-scripting-commands"
title: "Scripting Reference: Commands"
url: "https://cerb.ai/docs/scripting/commands/"
summary: "This page serves as a scripting reference for Cerb, detailing various commands available for use in bot scripts and snippets. It covers the functionality and usage of commands such as 'do,' 'filter,' 'for,' 'if,' 'set,' 'spaceless,' 'verbatim,' and 'with.' Each command is explained with examples, demonstrating how to evaluate expressions, apply filters, iterate over arrays, implement conditional logic, define variables, manage whitespace, avoid parsing template syntax, and create separate variable scopes. The page provides practical insights into effectively utilizing these commands within Cerb's scripting environment."
tags: ["docs", "docs-scripting"]
---
These commands are available in bot scripts and snippets:

- [apply](#apply)
- [do](#do)
- [for](#for)
- [if](#if)
- [set](#set)
- [spaceless](#spaceless)
- [verbatim](#verbatim)
- [with](#with)

## apply

Apply the given filters to the enclosed block of text:

```
{% apply upper %}
All of this text will be uppercase.
{% endapply %}
```

```
ALL OF THIS TEXT WILL BE UPPERCASE.
```

## do

Evaluate a variable or expression without outputting anything:

```
{% do "This won't print" %}
```

This is primarily useful for expanding placeholders in dictionaries before serializing the object to [JSON](/docs/scripting/json/) or [XML](/docs/scripting/xml/):

```
{% do ticket_custom_ %}
```

## for

Arrays can be iterated with **for** loops:

```
{% set list_of_names = ["Jeff", "Dan", "Darren"] %}
{% for name in list_of_names %}
* {{name}}
{% endfor %}
```

```
* Jeff
* Dan
* Darren
```

## if

Conditional logic can display different content based on the result of any number of **expressions**:

```
{% set sla_expiration = '+2 weeks'|date('U') %}
{% if sla_expiration >= 'now'|date('U') %}
Your SLA coverage is active.
{% else %}
Your SLA coverage has expired.
{% endif %}
```

```
Your SLA coverage is active.
```

## set

You can make your own variables in a template using the **set** command:

```
{% set name = "Kina" %}
{% set quantity = 5 %}
{{name}} has {{quantity}} gold stars.
```

```
Kina has 5 gold stars.
```

Variables are temporary. When you define a new variable in one action, it can't be referenced from other actions. In programmer parlance, the **scope** of a variable is limited to the same template.

## spaceless

Remove whitespace between HTML tags in the enclosed block of text with **spaceless**:

```
{% spaceless %}
<div>
  <span>This will all be on a single line.</span>
</div>
{% endspaceless %}
```

```
<div><span>This will all be on a single line.</span></div>
```

This is also useful when you're using a lot of template commands (if, for) to mark up text. You won't have to add - to every tag.

## verbatim

You can avoid parsing template syntax by enclosing the code in **verbatim** tags:

```
{% verbatim %}
You can print a variable by typing {{variable_name}}
{% endverbatim %}
```

```
You can print a variable by typing {{variable_name}}
```

## with

Create a separate variable scope using the **with** command:

```
{% with %}
{% set name = 'Kina' -%}
Hi, {{name}}!
{% endwith %}
{% if name is empty %}
Where did you go?
{% endif %}
```

```
Hi, Kina!
Where did you go?
```

[\< XML](/docs/scripting/xml/)

[Functions \>](/docs/scripting/functions/)

