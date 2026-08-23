---
id: "docs-scripting-filters--sort"
title: "Scripting Filter: sort"
url: "https://cerb.ai/docs/scripting/filters/#sort"
summary: "Sort an array with optional custom comparator"
tags: ["docs", "docs-scripting"]
---
## sort

Sort an array:

```
{% set x = [9,5,1,6,4,3] %}
{{x|sort|slice(0,6)|json_encode}}
```

```
[1,3,4,5,6,9]
```

You can also provide an arrow function as a custom comparator for advanced sorting rules. The spaceship operator (`<=>`) automatically returns in comparator format (e.g. `-1`, `0`, or `1`):

- (A \<=\> B) \< 0 is true if A \< B
- (A \<=\> B) \> 0 is true if A \> B
- (A \<=\> B) == 0 is true if A and B are equal/equivalent

```
{% set items = [
    {name: "Item C", priority: 3},
    {name: "Item A", priority: 1},
    {name: "Item B", priority: 2}
] %}
{{items|sort((a,b) => a.priority <=> b.priority)|column('name')|join(', ')}}
```

```
Item A, Item B, Item C
```
