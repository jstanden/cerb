---
id: "docs-scripting-functions--vobjectparse"
title: "Scripting Function: vobject_parse"
url: "https://cerb.ai/docs/scripting/functions/#vobjectparse"
summary: "Parse text in VObject format like vCard or iCal"
tags: ["docs", "docs-scripting"]
---
## vobject\_parse

Parse a block of text in VObject format (e.g. vCard, iCal).

`vobject_parse(text)`

**Arguments:**

| Name | Notes |
| --- | --- |
| `text` | The VOBJECT text to parse |

**Returns:** An object with properties and parameters.

```
{% set vcard %}
begin:vcard
source:ldap://cn=Meister%20Berger,o=Universitaet%20Goerlitz,c=DE
name:Meister Berger
fn:Meister Berger
n:Berger;Meister
bday;value=date:1963-09-21
o:Universit=E6t G=F6rlitz
title:Mayor
title;language=de;value=text:Burgermeister
note:The Mayor of the great city of
  Goerlitz in the great country of Germany.
email;internet:mb@goerlitz.de
home.tel;type=fax,voice,msg:+49 3581 123456
home.label:Hufenshlagel 1234\n
 02828 Goerlitz\n
 Deutschland
end:vcard
{% endset %}
{{vobject_parse(vcard)|json_encode|json_pretty}}
```

```
{
    "VCARD": [
        {
            "props": {
                "SOURCE": [
                    {
                        "params": [],
                        "value": "ldap://cn=Meister%20Berger,o=Universitaet%20Goerlitz,c=DE"
                    }
                ],
                "NAME": [
                    {
                        "params": [],
                        "value": "Meister Berger"
                    }
                ],
                "FN": [
                    {
                        "params": [],
                        "value": "Meister Berger"
                    }
                ],
                "N": [
                    {
                        "params": [],
                        "value": "Berger;Meister"
                    }
                ],
                "BDAY": [
                    {
                        "params": {
                            "value": "date"
                        },
                        "value": "1963-09-21"
                    }
                ],
                "O": [
                    {
                        "params": [],
                        "value": "Universit=E6t G=F6rlitz"
                    }
                ],
                "TITLE": [
                    {
                        "params": [],
                        "value": "Mayor"
                    },
                    {
                        "params": {
                            "language": "de",
                            "value": "text"
                        },
                        "value": "Burgermeister"
                    }
                ],
                "NOTE": [
                    {
                        "params": [],
                        "value": "The Mayor of the great city of Goerlitz in the great country of Germany."
                    }
                ],
                "EMAIL": [
                    {
                        "params": {
                            "internet": ""
                        },
                        "value": "mb@goerlitz.de"
                    }
                ],
                "HOME.TEL": [
                    {
                        "params": {
                            "type": "fax,voice,msg"
                        },
                        "value": "+49 3581 123456"
                    }
                ],
                "HOME.LABEL": [
                    {
                        "params": [],
                        "value": "Hufenshlagel 1234\n02828 Goerlitz\nDeutschland"
                    }
                ]
            }
        }
    ]
}
```
