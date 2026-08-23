---
id: "docs-scripting-loops"
title: "Scripting Reference: Loops"
url: "https://cerb.ai/docs/scripting/loops/"
summary: "This page provides a scripting reference for using loops in Cerb, specifically focusing on 'for' loops. It explains how to iterate over arrays and ranges, demonstrating with examples how to loop through a list of names and a range of numbers. The page also notes that variables defined within a loop are not accessible outside of it unless they are defined beforehand. Additionally, it briefly mentions operators and regular expressions, suggesting further topics related to scripting in Cerb."
tags: ["docs", "docs-scripting"]
---
# For

Arrays can be iterated with [for](/docs/scripting/commands/#for) loops:

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

A variable defined within a loop is not accessible outside of it. You can first define a variable before using it in the loop to change this.

# Ranges

Loop through a range of values with `..`:

```
{% for n in 1..5 %}
{{n}}...
{% endfor %}
```

```
1...
2...
3...
4...
5...
```

[\< Operators](/docs/scripting/operators/)

[Regular Expressions \>](/docs/scripting/regex/)

