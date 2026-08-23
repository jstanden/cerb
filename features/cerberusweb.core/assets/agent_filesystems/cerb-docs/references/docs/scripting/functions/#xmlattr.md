---
id: "docs-scripting-functions--xmlattr"
title: "Scripting Function: xml_attr"
url: "https://cerb.ai/docs/scripting/functions/#xmlattr"
summary: "Return a single attribute from an XML node"
tags: ["docs", "docs-scripting"]
---
## xml\_attr

Return a single attribute from an XML node.

`xml_attr(xml_node, attr)`

**Arguments:**

| Name | Notes |
| --- | --- |
| `xml_node` | An single XML node, usually from [xml\_xpath](#xml_xpath) |
| `attr` | The name of an attribute |

**Returns:** A string from the given XML attribute, or `false`.

```
{% set xml_string %}
<?xml version = "1.0" encoding = "UTF-8"?>
<Movies>
    <Movie rating="R">
        <Title runtime="142">The Shawshank Redemption</Title>
        <Genre>Drama</Genre>
        <Director>
            <Name highratedmovie="The Mist">
                <First>Frank</First>
                <Last>Darabont</Last>
            </Name>
        </Director>
        <Studio>Columbia Pictures</Studio>
        <Year>1994</Year>
    </Movie>
</Movies>
{% endset %}
{% set xml = xml_decode(xml_string) %}
{% set movie = xml_xpath(xml, '//Movie')|first %}
{% set runtime = xml_attr(movie.Title,'runtime') %}
The runtime of {{movie.Title}} is {{runtime ? (60*runtime)|secs_pretty : 'unknown'}}.
```

```
The runtime of The Shawshank Redemption is 2 hours, 22 mins.
```
