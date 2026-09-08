---
id: "docs-scripting-filters--raw"
title: "Scripting Filter: raw"
url: "https://cerb.ai/docs/scripting/filters/#raw"
summary: "Mark a value as markup that is already safe so it isn't escaped"
tags: ["docs", "docs-scripting"]
---
## raw

Mark a value as markup that is already safe, so it isn't escaped.

Cerb doesn't escape template output in most places – automations, snippets, email signatures, and mail templates all emit values as they are – so on those surfaces `raw` makes no difference to what you see:

```
{% set s = "<b> <i>x</i> </b>" %}
{{s}}
{{s|raw}}
```

```
<b> <i>x</i> </b>
<b> <i>x</i> </b>
```

Where it does matter is with filters that escape their own input before they run, and they don't all fail the same way.

[spaceless](/docs/scripting/commands/#spaceless) matches on the `>` and `<` around whitespace, which are the characters escaping replaces. It finds nothing to collapse and returns the text unchanged, with no error.

[nl2br](#nl2br) matches on line breaks, which escaping leaves alone, so it still inserts its `<br />` – but the markup around it comes back escaped.

Piping through `raw` first prevents both:

```
{% set s = "<b> <i>x</i> </b>" %}
{{s|spaceless}}
{{s|raw|spaceless}}
```

```
&lt;b&gt; &lt;i&gt;x&lt;/i&gt; &lt;/b&gt;
<b><i>x</i></b>
```

[Sheet](/docs/sheets/) cells and HTML and JavaScript widget templates _do_ escape their output. On those surfaces raw suppresses that escaping, but generally only when it comes last in the chain, because a later filter can produce a new value that is escaped again.
