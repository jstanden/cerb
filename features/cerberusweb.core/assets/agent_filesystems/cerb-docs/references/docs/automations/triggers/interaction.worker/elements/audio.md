---
id: "docs-automations-triggers-interaction-worker-elements-audio"
title: "Audio - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.worker/elements/audio/"
summary: "This page provides details on the audio interaction form element. It explains how to incorporate an audio element into web forms, allowing sound files to be played. The page outlines the syntax for configuring the audio element, including options for labels, autoplay, controls, looping, and specifying the audio source. The source can be a base64-encoded MPEG data URI or a Cerb automation resource token. The default settings are autoplay enabled, controls visible, and no looping."
tags: ["docs", "docs-automations"]
---
In [interaction](/docs/automations/triggers/interaction.worker/) web forms, an **audio** element plays a sound file.

```
start:
  await:
    form:
      title: Audio Example
      elements:
        audio/prompt_audio:
          label: Play audio:
          autoplay@bool: yes
          controls@bool: yes
          source:
            blob: data:audio/mpeg;base64,...
            #uri: cerb:resource:...
```

 

# Syntax

### label:

The optional label to display above the form element.

### autoplay:

If `no`, the audio only starts when the play button is pressed. The default is `yes`.

### controls:

If `no`, the player controls are hidden. The default is `yes`.

### hidden:

This form element can be conditionally hidden.

```
hidden@bool: {{not worker_is_superuser}}
```

### loop:

If `no` (default), the audio plays once and stops. If `yes` the audio repeats indefinitely.

### source:

Must be one of:

| Source | Description |
| --- | --- |
| `blob:` | A data URI with base64-encoded MPEG data (`data:audio/mpeg;base64,...`) |
| `uri:` | A `cerb:automation_resource:<token>` token. This can be created with `file.write:` or `http.request:` |

