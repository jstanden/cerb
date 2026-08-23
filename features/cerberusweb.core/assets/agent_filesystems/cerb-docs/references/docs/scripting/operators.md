---
id: "docs-scripting-operators"
title: "Scripting Reference: Operators"
url: "https://cerb.ai/docs/scripting/operators/"
summary: "This page serves as a scripting reference for operators in Cerb, detailing how to use various operators to perform comparisons and assignments in expressions. It covers assignment with the `=` operator, equality checks with `==`, inequality with `!=`, and comparisons using `<`, `<=`, `>`, and `>=`. Additionally, it explains how to check for the presence of a value in a list using `in` and `not in`. The page provides code examples for each operator to illustrate their usage in practical scenarios."
tags: ["docs", "docs-scripting"]
---
An **operator** makes comparisons between two values in an expression.

# Assignment

As you've seen with the [set](/docs/scripting/commands/#set) command, a single `=` (equals) character _assigns_ a value to a variable:

```
{% set this = 0 %}
{% set that = 1 %}
this is {{this}} and that is {{that}}
```

```
this is 0 and that is 1
```

# Equals

To check if a variable is equal to a specific value, use two equal signs (`==`):

```
{% set this = 1 %}
{% set that = 1 %}
{% if this == that %}
This and that are equal.
{% endif -%}
```

```
This and that are equal.
```

# Doesn't equal

To check that a variable isn't equal to a specific value, use `!=`:

```
{% set this = 0 %}
{% set that = 1 %}
{% if this != that %}
This doesn't equal that.
{% endif -%}
```

```
This doesn't equal that.
```

# Less than

To check if one variable is less than another, use `<` or `<=`:

```
{% set little = 5 %}
{% set big = 1000 %}
{% if little < big %}
{{little}} is less than {{big}}
{% endif -%}
```

```
5 is less than 1000
```

# Greater than

To check if one variable is greater than another, use `>` or `>=`:

```
{% set little = 5 %}
{% set big = 1000 %}
{% if big > little %}
{{big}} is greater than {{little}}
{% endif -%}
```

```
1000 is greater than 5
```

# Value in list

You can check if a value exists in a list by using the `in` test:

```
{% set colors = ['blue','green','red'] %}
{% if 'red' in colors %}
One of the colors is red.
{% endif -%}
```

```
One of the colors is red.
```

You can also negate that test with `not in`:

```
{% set colors = ['blue','green','red'] %}
{% if 'orange' not in colors %}
Orange is not one of the colors.
{% endif -%}
```

```
Orange is not one of the colors.
```

[\< Conditional Logic](/docs/scripting/conditional-logic/)

[Loops \>](/docs/scripting/loops/)

