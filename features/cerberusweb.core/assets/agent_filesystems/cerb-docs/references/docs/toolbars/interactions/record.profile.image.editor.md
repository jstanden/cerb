---
id: "docs-toolbars-interactions-record-profile-image-editor"
title: "record.profile.image.editor"
url: "https://cerb.ai/docs/toolbars/interactions/record.profile.image.editor/"
summary: "This page provides detailed information about the profile image editor toolbar in Cerb. It explains how the toolbar facilitates the creation of profile images through interaction.worker automations, allowing for the generation of images from text, emojis, file uploads, or external APIs like Stable Diffusion. The page outlines the configuration process, including how to navigate to the toolbar settings and add custom interactions using KATA. It also describes the available placeholders and the inputs and outputs for interactions, emphasizing the ability to customize image creation with features like profanity and adult content filters."
tags: ["docs"]
---
The profile image editor [toolbar](/docs/toolbars/) is displayed when editing a record's profile image, and allows [interaction.worker](/docs/interactions/) automations to generate profile images.

For instance, creating images from text/emoji, file uploads, or APIs like Stable Diffusion.

Interactions must return an `image:url:` or `image:text:` key.

Built-in interactions are provided for text-based images, file uploads, and fetching images from an external URL.

This improves on the previous built-in functionality by allowing admins to add custom logic to profile image creation – for instance, profanity and adult content filters.

 

# Configuration

Navigate to **Search&nbsp;» Toolbars**.

Edit the record for `record.profile.image.editor`.

Add [interactions](/docs/automations/triggers/interaction.worker/) using [toolbar KATA](/docs/toolbars/#kata).

```
interaction/stability:
  label: Stable Diffusion
  icon: picture
  uri: cerb:automation:example.interaction.recordProfileImage.stabilityai
```

The following **placeholders** are available in KATA:

| Key | &nbsp; |
| --- | --- |
| `image_height` | The height of the image to be generated. |
| `image_width` | The width of the image to be generated. |
| `record_*` | The [record](/docs/records/types/) profile being viewed. Supports [key expansion](/docs/guide/developers/dictionaries/#key-expansion). The `record__type` placeholder is the type (e.g. `ticket`). |
| `worker_*` | The active [worker](/docs/records/types/worker/) record. Supports [key expansion](/docs/guide/developers/dictionaries/#key-expansion). |

# Interactions

Caller: `cerb.toolbar.record.profile`

### Inputs

The following `caller_params` are passed to the [interaction](/docs/automations/triggers/interaction.worker/):

| Key | Type | &nbsp; |
| --- | --- | --- |
| `image_height` | The height of the image to be generated. | &nbsp; |
| `image_width` | The width of the image to be generated. | &nbsp; |
| **`record_`** | record | The [record](/docs/records/types/) dictionary |

### Output

An interaction should return one of:

| Key | Type |
| --- | --- |
| `image:text:` | Text or emoji to convert into an image. |
| `image:url:` | An image URL to load. This can be `/ui/image/<token>` in the case of [automation resources](/docs/records/types/automation_resource/). |

### after:

| Key | Type | &nbsp; |
| --- | --- | --- |
| **`refresh_toolbar@bool:`** | boolean | Refresh the current [toolbar](/docs/toolbars/). |

