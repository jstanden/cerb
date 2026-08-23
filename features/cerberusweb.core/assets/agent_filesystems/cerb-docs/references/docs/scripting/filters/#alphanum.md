---
id: "docs-scripting-filters--alphanum"
title: "Scripting Filter: alphanum"
url: "https://cerb.ai/docs/scripting/filters/#alphanum"
summary: "Remove non-alphanumeric characters from a string"
tags: ["docs", "docs-scripting"]
---
## alphanum

Remove non-alphanumeric characters from a string:

```
{{"* Ignore spaces and non-alphanumeric characters+1$2%3!"|alphanum}}
```

```
Ignorespacesandnonalphanumericcharacters123
```

Also allow specific characters:

```
{{"* Ignore non-alphanumeric but allow spaces$%#!"|alphanum(' !')}}
```

```
Ignore nonalphanumeric but allow spaces!
```
