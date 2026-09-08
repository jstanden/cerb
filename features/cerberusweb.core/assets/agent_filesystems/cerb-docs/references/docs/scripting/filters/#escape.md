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
This\u0020is\u0020\u0022escaped\u0022\u0020for\u0020Javascript
This is &quot;escaped&quot; for &lt;b&gt;HTML&lt;/b&gt;
```

`e` is a shorthand alias for `escape`, as in the second line above. Both are available.
