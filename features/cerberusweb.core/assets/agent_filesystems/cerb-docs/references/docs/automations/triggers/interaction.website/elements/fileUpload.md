---
id: "docs-automations-triggers-interaction-website-elements-fileupload"
title: "File Upload - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.website/elements/fileUpload/"
summary: "This page provides detailed information on the 'fileUpload' element used in Cerb's website interaction forms, which allows users to upload files as automation resource records. It includes examples of uploading a single file with specific validation criteria, such as file type and size, and uploading multiple files with options to convert them into attachments. The page also explains the syntax for configuring the file upload element, including optional parameters like label, accepted file types, multiple file uploads, required fields, and custom validation scripts. The examples demonstrate how to handle file uploads and manage the resulting automation resource tokens effectively."
tags: ["docs", "docs-automations"]
---
In [website interactions](/docs/automations/triggers/interaction.website/) forms, a **fileUpload** element allows a user to upload one or more files as [automation resource](/docs/records/types/automation_resource/) records.

 

# Examples

## Upload a single file

```
start:
  await:
    form:
      title: File Uploader
      elements:
        fileUpload/prompt_file:
          label: Upload an image:
          accept: .png,image/png,.jpg,image/jpeg
          multiple@bool: no
          required@bool: yes
          validation@raw:
            {% if prompt_file_mime_type != 'image/png' %}
            The file must be a PNG image ({{prompt_file_mime_type}})
            {% elseif prompt_file_size > 1024000 %}
            The file ({{prompt_file_size|bytes_pretty}}) must be smaller than 1MB.
            {% endif %}
```

The output placeholder is set to the new automation resource token.

The placeholder is also key expandable. In the above example, `prompt_file` could be expanded with `prompt_file_name` or `prompt_file_size`.

## Upload multiple files

```
start:
  await/form:
    form:
      title: Form Title
      elements:
        fileUpload/prompt_files:
          label: Upload files:
          required@bool: yes
          multiple@bool: yes
          accept: .png,image/png,.jpg,image/jpeg

  await/results:
    form:
      title: Results
      elements:
        say:
          content@text:
            You uploaded:
            {% for prompt_file in prompt_files__records %}
            * {{prompt_file.name}} ({{prompt_file.size|bytes_pretty}})
            {% endfor %}
```

The output placeholder is set to a list of automation resource tokens.

Another output placeholder is set with a `__records` suffix. This includes the full dictionaries for the resource token. In the above example, the key would be `prompt_files__records`.

## Upload multiple files and convert to attachments

```
start:
  await/form:
    form:
      title: Form Title
      elements:
        fileUpload/prompt_files:
          label: Upload files:
          required@bool: yes
          multiple@bool: yes
          accept: .png,image/png,.jpg,image/jpeg

  repeat:
    each@key: prompt_files__records
    as: prompt_file
    do:
      record.create:
        inputs:
          record_type: attachment
          fields:
            name: {{prompt_file.name}}
            mime_type: application/vnd.cerb.uri
            content: cerb:automation_resource:{{prompt_file.token}}
        output: new_file
```

# Syntax

### label:

The optional label to display above the form element.

### accept:

An optional comma-delimited list of file extensions or MIME types to accept in the browser file chooser. If omitted, all files are available for selection.

### hidden:

This form element can be conditionally hidden.

```
hidden@bool: {{expression}}
```

### multiple@bool:

If `yes`, multiple files may be uploaded at once. The result is an array of [automation resource](/docs/records/types/automation_resource/) dictionaries.

If omitted or `no`, a single file may be uploaded. The result is a single automation resource dictionary.

### required@bool:

If user input is required on this element use a value of `yes`. Otherwise, omit.

### validation:

An optional custom validation script. Any output is considered to be an error.

You can use `if...elseif` to check multiple conditions.

```
fileUpload/prompt_file:
  label: Upload a file:
  required@bool: yes
  validation@raw:
    {% if prompt_file_mime_type != 'image/png' %}
    The file must be a PNG image ({{prompt_file_mime_type}})
    {% elseif prompt_file_size > 1024000 %}
    The file ({{prompt_file_size|bytes_pretty}}) must be smaller than 1MB.
    {% endif %}
```
