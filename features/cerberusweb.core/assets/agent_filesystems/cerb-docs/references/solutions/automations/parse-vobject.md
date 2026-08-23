---
id: "solutions-automations-parse-vobject"
title: "Parse VObject attachments"
url: "https://cerb.ai/solutions/automations/parse-vobject/"
summary: "This page explains how to parse VObject attachments using the `file.read:` and `vobject_parse()` functions in Cerb. It provides an example of parsing an iCal event invitation and returns it as a JSON object for use in automations, such as extracting the event details like summary, organizer, and start date."
tags: ["solutions", "solutions-automations"]
---
You can use the [file.read:](/docs/automations/commands/file.read/) automation command and [vobject\_parse()](/docs/scripting/functions/#vobject_parse) scripting function to parse the content of VObject files return them as a JSON object. This can be useful if you are sent an iCal event invite or VCard contact.

For example, if you are sent this iCal event:

```
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
BEGIN:VEVENT
UID:uid1@example.com
ORGANIZER;CN=John Doe:MAILTO:john.doe@example.com
DTSTAMP:19970701T100000Z
DTSTART:19970714T170000Z
DTEND:19970715T040000Z
SUMMARY:Bastille Day Party
GEO:48.85299;2.36885
END:VEVENT
END:VCALENDAR
```

You can parse it as follows:

- [automation](#)
- [output](#)
- [policy](#)

- 
```
start:
  file.read:
    output: results
    inputs:
      uri: cerb:attachment:4
    on_success:
      return:
        vcalendar@json: {{vobject_parse(results.bytes)|json_encode}}
```
- 
```
__exit: return
__return:
  vcalendar:
    VCALENDAR:
    - props:
        VERSION:
        - params: []
          value: "2.0"
        PRODID:
        - params: []
          value: -//hacksw/handcal//NONSGML v1.0//EN
      children:
        VEVENT:
        - props:
            UID:
            - params: []
              value: uid1@example.com
            ORGANIZER:
            - params:
                CN: John Doe
              value: MAILTO:john.doe@example.com
            DTSTAMP:
            - params: []
              value: 19970701T100000Z
            DTSTART:
            - params: []
              value: 19970714T170000Z
            DTEND:
            - params: []
              value: 19970715T040000Z
            SUMMARY:
            - params: []
              value: Bastille Day Party
            GEO:
            - params: []
              value: 48.85299;2.36885
```
- 
```
commands:
  file.read:
    allow@bool: yes
```

