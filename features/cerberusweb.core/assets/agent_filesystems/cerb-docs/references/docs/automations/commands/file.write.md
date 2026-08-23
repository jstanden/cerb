---
id: "docs-automations-commands-file-write"
title: "Automations: file.write"
url: "https://cerb.ai/docs/automations/commands/file.write/"
summary: "This page provides detailed information on the 'file.write' command in Cerb automations, which is used to write arbitrary bytes to a temporary automation resource record with a unique token identifier. It explains the efficiency of this method over storing large data within the automation state and describes how the command can generate a ZIP file from multiple attachments or resources. The page outlines the syntax, including inputs like content, expiration, MIME type, and URI, and details the outputs and error handling. It also provides examples of creating a simple text file, a ZIP file from mixed bytes and attachments, and an attachment from an automation resource, complete with the expected output structure for each scenario."
tags: ["docs", "docs-automations"]
---
The **file.write:** command writes arbitrary bytes to a temporary [automation resource](/docs/records/types/automation_resource/) record with a unique token identifier.

This is much more efficient than storing a large amount of data within the automation state.

The `file.write:` command can also optionally generate a ZIP file from multiple [attachments](/docs/records/types/attachment/) or automation resources.

- [Syntax](#syntax)
  - [inputs:](#inputs)
    - [content:](#content)
    - [content:zip:](#contentzip)
    - [expires:](#expires)
    - [mime\_type:](#mime_type)
    - [name:](#name)
    - [uri:](#uri)

  - [output:](#output)
  - [on\_simulate:](#on_simulate)
  - [on\_success:](#on_success)
  - [on\_error:](#on_error)

- [Examples](#examples)
  - [Create a simple text file](#create-a-simple-text-file)
  - [Create a ZIP file from mixed bytes and attachments](#create-a-zip-file-from-mixed-bytes-and-attachments)
  - [Create an attachment from an automation resource](#create-an-attachment-from-an-automation-resource)
  - [Create a ZIP with a dynamic file list](#create-a-zip-with-a-dynamic-file-list)

# Syntax

## inputs:

| Key | Req'd | Type |
| --- | --- | --- |
| `content:` | **x** | string, object |
| `expires:` | &nbsp; | timestamp |
| `mime_type:` | &nbsp; | string |
| `name:` | &nbsp; | string |
| `uri:` | &nbsp; | string |

### content:

| Key | Type | Description |
| --- | --- | --- |
| `bytes:` | string | Arbitrary bytes to write |
| `text:` | string | Arbitrary text to write |
| `zip:` | object | A collection of `files:` to compress as a ZIP archive |

### content:zip:

| Key | Type | Description |
| --- | --- | --- |
| `files:` | objects | A list of `file:` objects to compress |
| `password:` | string | An optional password to encrypt the ZIP contents |

```
zip:
    password: s3cr3t
    files:
      file/name0:
         path: /path/to/example.png
         uri: cerb:attachment:123
      file/name1:
         path: /path/to/example.txt
         bytes: This is arbitrary content
```

### expires:

An optional Unix timestamp after which the temporary resource will be deleted. Defaults to `+15 minutes`.

Use the `@date` key annotation to use human friendly date strings like `+1 hour` or `2023-12-31 5pm Europe/Berlin`.

### mime\_type:

An optional MIME type for the file. Defaults to `application/octet-stream`.

### name:

An optional name for the file.

### uri:

Append to an existing automation resource record when providing a Cerb URI. For instance, this makes it possible to do an incremental export in a loop where each page of results is exported separately.

## output:

Save the output to this key name.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of writing the record URI.

If omitted, the file is written during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The key specified in `output:` is set to a dictionary with the following structure:

| Key | Description |
| --- | --- |
| `expires_at` | The timestamp after which the resource is expired. |
| `id` | The ID of the resource record. |
| `mime_type` | The MIME type of the file or resource. For instance, `image/png`. |
| `name` | The filename of the record. |
| `size` | The total size in bytes of the file or resource. |
| `token` | The `uri` token of the automation resource. |
| `uri` | The record URI of the [automation\_resource](/docs/records/types/automation_resource/). The `uri` uses a random UUID identifier rather than an ID, which is suitable for sharing publicly. |

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

# Examples

## Create a simple text file

```
start:
  file.write:
    inputs:
      name: example.txt
      mime_type: text/plain
      expires@date: +15 mins
      content: This is some text
    output: results
```

The command returns this dictionary:

```
results:
  uri: cerb:automation_resource:247cc278-450e-11ed-8cbd-e1c6a4043a12
  name: example.txt
  mime_type: text/plain
  expires_at: 1665017156
  size: 17
  id: 123
```

## Create a ZIP file from mixed bytes and attachments

```
start:
  file.write:
    inputs:
      content:
        zip:
          password: s3cr3t
          files:
            file/name0:
               path: /path/to/example.png
               uri: cerb:attachment:123
            file/name1:
               path: /path/to/example.txt
               bytes: This is arbitrary content
      name: example.zip
      expires@date: +15 mins
    output: results
```

The command returns this dictionary:

```
results:
  uri: cerb:automation_resource:835ed6f2-4511-11ed-a716-f9b7c189003f
  mime_type: application/zip
  expires_at: 1665018603
  size: 557
  id: 123
  name: example.zip
```

## Create an attachment from an automation resource

```
start:
  record.create:
    inputs:
      record_type: attachment
      fields:
        name: example.zip
        mime_type: application/vnd.cerb.uri
        content: cerb:automation_resource:835ed6f2-4511-11ed-a716-f9b7c189003f
    output: results
```

Output:

```
results:
  _context: cerberusweb.contexts.attachment
  id: 1234
  _type: attachment
  _loaded: true
  _label: example.zip
  mime_type: application/zip
  name: example.zip
  size: 557
  storage_extension: devblocks.storage.engine.disk
  storage_key: a/b/1234
  storage_sha1hash: fb528658ed4c4f8cd1b2e4b768b583e42c5a3ec3
  updated: 1665018318
  url_download: https://cerb.example/files/1234/example.zip
```

## Create a ZIP with a dynamic file list

Use the [@kata](/docs/kata/#kata) annotation to build a file list using scripting.

```
inputs:
  records/files:
    record_type: attachment
    required@bool: yes

start:
  file.write:
    output: results
    inputs:
      name: example.zip
      mime_type: application/zip
      content:
        zip:
          files@kata:
            {% for file in inputs.files %}
            file/{{file.id}}:
              path: {{file.name}}
              uri: cerb:attachment:{{file.id}}
            {% endfor %}
```
