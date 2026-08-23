---
id: "docs-automations-triggers-interaction-worker-elements-fileupload"
title: "File Upload - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.worker/elements/fileUpload/"
summary: "This page provides detailed information on the 'fileUpload' element used in interaction web forms within Cerb. It explains how this element facilitates file uploads by creating an attachment or automation resource record and returning its ID or token. The document outlines the syntax for using the 'fileUpload' element, including setting a placeholder with the element name, handling key expansion for file fields, and specifying the record type to create with the uploaded file. It also covers optional features such as labeling the form element, requiring user input, and implementing custom validation scripts to ensure the uploaded file meets specific criteria, such as being a valid image or not exceeding a certain size."
tags: ["docs", "docs-automations"]
---
In [interaction](/docs/automations/triggers/interaction.worker/) web forms, a **fileUpload** element displays a file upload prompt. This creates an [attachment](/docs/records/types/attachment/) or [automation resource](/docs/records/types/automation_resource/) record and returns its ID or token.

The element name (e.g. `fileUpload/photo` in the example below) is used to set a placeholder.

If uploading an attachment and the name ends with `_id`, then a corresponding `__context` key will be added with the same prefix. Otherwise, keys for `_id` and `__context` will be added with the element name as the prefix. This allows key expansion of the file's fields (e.g. `name`, `size`) directly on the placeholder, as well as from within a `validation@raw:` script.

```
start:
  await:
    form:
      elements:
        fileUpload/photo:
          label: Upload a photo:
          as: automation_resource
```

 

# Syntax

### as:

The record type to create with the uploaded file.

| Record | &nbsp; |
| --- | --- |
| `attachment` | (default) A long-term file [attachment](/docs/records/types/attachment/). This returns a record dictionary. |
| `automation_resource` | A temporary [automation resource](/docs/records/types/automation_resource/). This returns the resource token. |

### label:

The optional label to display above the form element.

### hidden:

This form element can be conditionally hidden.

```
hidden@bool: {{not worker_is_superuser}}
```

### required@bool:

If user input is required on this element use a value of `yes`. Otherwise, omit.

### validation:

An optional custom validation script. Any output is considered to be an error.

You can use `if...elseif` to check multiple conditions.

```
start:
  await:
    form:
      elements:
        fileUpload/prompt_image:
          label: File
          required@bool: yes
          as: attachment
          validation@raw:
            {% if prompt_image__context is empty or prompt_image_id is empty %}
            The file must be a valid image.
            {% elseif prompt_image_mime_type is not prefixed ('image/') %}
            The file must be an image ({{prompt_image_mime_type}}).
            {% elseif prompt_image_size > 50000 %}
            The image must be smaller than 50KB ({{prompt_image_size|bytes_pretty}}).
            {% endif %}
```
