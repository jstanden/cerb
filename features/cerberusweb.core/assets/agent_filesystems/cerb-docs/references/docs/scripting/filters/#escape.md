---
id: "docs-scripting-filters--escape"
title: "Scripting Filter: escape"
url: "https://cerb.ai/docs/scripting/filters/#escape"
summary: "Escape strings for HTML, JavaScript, CSS, URL, or HTML attributes"
tags: ["docs", "docs-scripting"]
---
## escape

Escape strings and variables with the following modes:

- `html`
- `js`
- `css`
- `url`
- `html_attr`

```
{{'This is "escaped" for Javascript'|escape('js')}}
{{'This is "escaped" for <b>HTML</b>'|e('html')}}
```

```
This\x20is\x20\x22escaped\x22\x20for\x20Javascript
This is &quot;escaped&quot; for <b>HTML</b>
```
