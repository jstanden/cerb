---
id: "solutions-automations-extract-zip-files"
title: "Extract ZIP files"
url: "https://cerb.ai/solutions/automations/extract-zip-files/"
summary: "This page explains how to read and extract specific files from a ZIP attachment using Cerb's `data.query:` and `file.read:` commands. It provides step-by-step examples of how to read the manifest of a `.zip` attachment record, filter extracted files based on filename patterns, extract a specific file path, and decompress gzip files, as well as implement deny policies for these commands to restrict access."
tags: ["solutions", "solutions-automations"]
---
Using data.query: and file.read: you can read and extract specific files from a ZIP attachment.

## Read a ZIP archive manifest

- [automation](#)
- [policy](#)

- 
```
start:
  data.query/zip:
    inputs:
      query@text:
        type:attachment.manifest
        id:1234
        filter:*.txt
        format:dictionaries
    output: results
```

The optional `filter:` key matches a filename pattern with `*` as wildcards.

- 
```
commands:
  data.query:
    deny/type@bool: {{query.type != 'attachment.manifest'}}
    allow@bool: yes
```

## Extract a specific file path from a ZIP archive

- [automation](#)
- [policy](#)

- 
```
start:
  file.read:
    output: file_contents
    inputs:
      uri: cerb:attachment:1234
      extract: README.txt
      filters:
        gzip.decompress:
```
- 
```
commands:
  file.read:
    allow@bool: yes
```

