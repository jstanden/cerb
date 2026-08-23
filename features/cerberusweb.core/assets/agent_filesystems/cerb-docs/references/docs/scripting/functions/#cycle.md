---
id: "docs-scripting-functions--cycle"
title: "Scripting Function: cycle"
url: "https://cerb.ai/docs/scripting/functions/#cycle"
summary: "Round-robin through a sequence"
tags: ["docs", "docs-scripting"]
---
## cycle

Round-robin through a sequence.

```
{% set options = ['odd','even'] %}
{% for n in 1..10 %}
* {{cycle(options, n)}}
{% endfor %}
```

```
* even
* odd
* even
* odd
* even
* odd
* even
* odd
* even
* odd
```
