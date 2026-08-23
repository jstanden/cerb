---
id: "solutions-automations-create-zip"
title: "Create a ZIP attachment"
url: "https://cerb.ai/solutions/automations/create-zip/"
summary: "This page explains how to create a ZIP attachment using the `file.write` command in Cerb. It shows an example of how to write a ZIP archive with two files (a README file and a Dockerfile) and then use `record.create` to create an attachment that links to the newly created ZIP file."
tags: ["solutions", "solutions-automations"]
---
## Create a ZIP attachment using file.write

The file.write: command can create a ZIP archive with files from arbitrary text, temporary automation resources, or existing file attachments. You can then use record.create: to create an attachment to link to other records like tickets, messages, etc.

- [automation](#)
- [policy](#)

- 
```
start:
  file.write:
    output: tmp_file
    inputs:
      mime_type: application/zip
      content:
        zip:
          files:
            file/readme:
              path: README.txt
              bytes@text:
                This is the README file.
            file/docker:
              path: docker/Dockerfile
              bytes@text:
                FROM ubuntu:24.04
  
  record.create/file:
    output: new_file
    inputs:
      record_type: attachment
      fields:
        name: example.zip
        mime_type: application/vnd.cerb.uri
        content: {{tmp_file.uri}}
```
- 
```
commands:
  file.write:
    allow@bool: yes
  record.create:
    deny/type@bool: {{inputs.record_type is not record type ('attachment')}}
    allow@bool: yes
```

