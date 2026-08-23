---
id: "docs-scripting-functions--cerbextracturis"
title: "Scripting Function: cerb_extract_uris"
url: "https://cerb.ai/docs/scripting/functions/#cerbextracturis"
summary: "Return an array of URLs found in HTML content with metadata"
tags: ["docs", "docs-scripting"]
---
## cerb\_extract\_uris

Return an array of URLs found in HTML content, along with metadata (e.g. tag, attributes, URI parts).

In the response, URLs are replaced with `tokens` in the `template` which can be modified with the [|replace](/docs/scripting/filters/#replace) filter.

For instance, this function can be used to rewrite all links in an email template for click tracking.

`cerb_extract_uris(html)`

| **html** | The HTML content to extract links from. |

```
{% set html %}
This is some <b>HTML</b> with <a href="https://cerb.ai/">links</a>.
{% endset %}
{% set results = cerb_extract_uris(html) %}
{{results|json_encode|json_pretty}}
```

```
{
    "tokens": {
        "#uri-61411f091662a": "https://cerb.ai/"
    },
    "context": {
        "#uri-61411f091662a": {
            "is_tag": true,
            "name": "a",
            "attr": "href",
            "attrs": {
                "href": "https://cerb.ai/"
            },
            "uri_parts": {
                "scheme": "https",
                "userinfo": null,
                "host": "cerb.ai",
                "port": null,
                "path": "/",
                "query": null,
                "fragment": null
            }
        }
    },
    "template": "This is some <b>HTML</b> with <a href=\"#uri-61411f091662a\">links</a>.\n"
}
```

To rewrite links:

```
{% set html %}
This is some <b>HTML</b> with <a href="https://cerb.ai/">links</a>.
{% endset %}
{% set results = cerb_extract_uris(html) %}
{% set new_urls = results.tokens|map(
  (url,token) => "https://proxy.example/click?url=" ~ url|url_encode
)%}
{{results.template|replace(new_urls)}}
```

```
This is some <b>HTML</b> with <a href="https://proxy.example/click?url=https%3A%2F%2Fcerb.ai%2F">links</a>.
```
