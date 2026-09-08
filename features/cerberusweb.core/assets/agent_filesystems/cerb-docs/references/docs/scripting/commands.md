---
id: "docs-scripting-commands"
title: "Scripting Reference: Commands"
url: "https://cerb.ai/docs/scripting/commands/"
summary: "This page serves as a scripting reference for Cerb, detailing various commands available for use in automation scripting and snippets. It covers the functionality and usage of commands such as 'apply,' 'do,' 'for,' 'if,' 'set,' 'verbatim,' and 'with,' along with the 'spaceless' filter. Each command is explained with examples, demonstrating how to evaluate expressions, apply filters, iterate over arrays, implement conditional logic, define variables, manage whitespace, avoid parsing template syntax, and create separate variable scopes. The page provides practical insights into effectively utilizing these commands within Cerb's scripting environment."
tags: ["docs", "docs-scripting"]
---
These commands are available in automation scripting and snippets:

- [apply](#apply)
- [do](#do)
- [for](#for)
- [if](#if)
- [sandbox](#sandbox)
- [set](#set)
- [spaceless](#spaceless)
- [verbatim](#verbatim)
- [with](#with)

https://www.youtube.com/embed/JwC7rQAwv1s

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
{% do ticket_customfields %}
```

Custom fields expand with `customfields` and no trailing underscore. The trailing underscore belongs to linked record prefixes like `owner_` and `group_`, which is a different kind of placeholder. A placeholder that matches neither expands nothing and reports no error.

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

## sandbox

Twig's **sandbox** command sandboxes an included template on demand. It's accepted, but in Cerb it does nothing: every template Cerb renders is already sandboxed, so this can only re-assert what's always true.

Cerb enables Twig's sandbox **globally** rather than per-template. The commands, [filters](/docs/scripting/filters/), and [functions](/docs/scripting/functions/) documented here _are_ the sandbox policy -- there's no unsandboxed mode to opt into, and no way to widen the policy from inside a template.

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

Remove the whitespace between HTML tags in a block of text by applying the **spaceless** filter with [apply](#apply):

```
{% apply spaceless %}
<div>
  <span>This will all be on a single line.</span>
</div>
{% endapply %}
```

```
<div><span>This will all be on a single line.</span></div>
```

`spaceless` only removes whitespace that falls _between_ a `>` and a `<`, and trims the start and end of the block. It does not change the whitespace within a run of text.

This is also useful when you're using a lot of template commands (if, for) to mark up text. You won't have to add - to every tag.

There is no longer a {% spaceless %} command. It was removed in Twig 3.0 and replaced by the **spaceless** filter, which is applied with {% apply %} as shown above.

That filter is itself deprecated as of Twig 3.12 and is scheduled for removal in Twig 4.0, which would take the {% apply spaceless %} form with it since that compiles to the same filter. Both still work today. Where the extra whitespace comes from template tags rather than from the markup itself, [whitespace control](/docs/scripting/strings/#whitespace) with - does the same job and isn't deprecated.

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

