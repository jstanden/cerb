---
id: "docs-scripting-functions--xmlattrs"
title: "Scripting Function: xml_attrs"
url: "https://cerb.ai/docs/scripting/functions/#xmlattrs"
summary: "Return all attributes from an XML node"
tags: ["docs", "docs-scripting"]
---
## xml\_attrs

Return all attributes from an XML node.

`xml_attrs(xml_node)`

**Arguments:**

| Name | Notes |
| --- | --- |
| `xml_node` | An single XML node, usually from [xml\_xpath](#xml_xpath) |

**Returns:** An array of attribute keys and values.

```
{% set xml_string %}
<?xml version = "1.0" encoding = "UTF-8"?>
<Movies>
    <Movie rating="R">
        <Title runtime="177">The Godfather</Title>
        <Genre> Crime Drama </Genre>
        <Director>
            <Name>
                <First>Francis Ford</First>
                <Last>Coppola</Last>
            </Name>
        </Director>
        <Studio>Paramount Pictures</Studio>
        <Year>1972</Year>
    </Movie>
    <Movie rating= "R">
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
{% set movies = xml_xpath(xml, '//Movie') %}
{{xml_attrs(movies[1])|json_encode|json_pretty}}
```

```
{
    "rating": "R"
}
```
