---
id: "docs-automations-triggers-interaction-worker-elements-filedownload"
title: "File Download - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.worker/elements/fileDownload/"
summary: "This page provides detailed information on the **fileDownload** element used in interaction web forms within Cerb. It explains how this element can be utilized to create a form button that facilitates the downloading of attachments or automation resource files, which is particularly beneficial for interactions that produce large or binary outputs such as dynamic images, CSV/JSON exports, or ZIP archives. The page includes syntax details for implementing the fileDownload element, covering parameters like `data`, `label`, `filename`, and `uri`, which define the content to download, the label displayed, the filename on the download button, and the URI for the resource, respectively."
tags: ["docs", "docs-automations"]
---
In [interaction](/docs/automations/triggers/interaction.worker/) web forms, a **fileDownload** element displays a form button that downloads an attachment or automation resource file when clicked.

This is particularly useful for interactions that generate large or binary output, like a dynamic image, a CSV/JSON export, or a ZIP archive.

```
start:
  await:
    form:
      title: Interaction Download
      elements:
        fileDownload/prompt_file:
          label: Download:
          uri: cerb:automation_resource:3b1f58c2-1234-11ed-b9e9-01791ccb5549
          filename: example.zip
```

 

# Syntax

### data:

The raw content to download. Alternative to `uri:`.

### label:

The optional label to display above the form element.

### filename:

The filename to show on the download button.

### hidden:

This form element can be conditionally hidden.

```
hidden@bool: {{not worker_is_superuser}}
```

### uri:

A URI for an [attachment](/docs/records/types/attachment/), [automation resource](/docs/records/types/automation_resource/), or [resource](/docs/records/types/resource/) to download. Alternative to `data:`.

