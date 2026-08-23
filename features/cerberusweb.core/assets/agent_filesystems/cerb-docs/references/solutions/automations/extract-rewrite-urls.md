---
id: "solutions-automations-extract-rewrite-urls"
title: "Extract and rewrite URLs in text"
url: "https://cerb.ai/solutions/automations/extract-rewrite-urls/"
summary: "This page explains how to extract and rewrite URLs found in HTML content using Cerb. The `cerb_extract_uris()` function is used to identify these URLs along with associated metadata such as tags, attributes, and URI parts. The extracted URLs are then replaced with tokens within a template that can be modified using the `|replace` filter for purposes like click tracking. For example, in an email template, all links can be replaced with proxy URLs to track user interactions effectively. The page provides examples demonstrating how to implement this functionality, including extracting and filtering URLs, sorting them by length, combining multiple URLs into pairs, and replacing them within a template block."
tags: ["solutions", "solutions-automations"]
---
## Using cerb\_extract\_uris()

The [cerb\_extract\_uris()](/docs/scripting/functions/#cerb_extract_uris) function return an array of URLs found in HTML content, along with metadata (e.g. tag, attributes, URI parts).

In the response, URLs are replaced with tokens in the template which can be modified with the [|replace](/docs/scripting/filters/#replace) filter.

For instance, this function can be used to rewrite all links in an email template for click tracking.

- [automation](#)
- [output](#)

- 
```
start:
  set/init:
    message@text: Visit our website at https://cerb.ai/ to learn more.
    urls@json:
      {{array_unique(cerb_extract_uris(message|markdown_to_html(is_untrusted=true)).tokens)|json_encode}}
  set/filter:
    urls@json: {{urls|filter((v) => v|parse_url.host ends with 'cerb.ai')|json_encode}}
  set/sort:
    urls@json: {{urls|sort((a,b) => b|length <=> a|length)|values|json_encode}}
  set/combine:
    urls@json:
      {{array_combine(urls, urls|map((url) => 'https://click.example/?url=' ~ url|url_encode))|json_encode}}
  return:
    output@text: {{message|replace(urls)}}
```
- 
```
__return:
  output: Visit our website at https://click.example/?url=https%3A%2F%2Fcerb.ai%2F to learn more.
  message: Visit our website at https://cerb.ai/ to learn more.
  urls:
    https://cerb.ai/: https://click.example/?url=https%3A%2F%2Fcerb.ai%2F
```

