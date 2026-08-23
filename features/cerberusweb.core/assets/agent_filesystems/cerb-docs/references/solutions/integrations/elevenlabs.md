---
id: "solutions-integrations-elevenlabs"
title: "ElevenLabs"
url: "https://cerb.ai/solutions/integrations/elevenlabs/"
summary: "This page provides a step-by-step guide for integrating Cerb with ElevenLabs, a text-to-speech and speech-to-text API. To begin, create an ElevenLabs API key by logging in to your account, selecting 'API Keys', and creating a new key, which can then be pasted into the Cerb service creation process. In Cerb, navigate to Connected Services > Create, select ElevenLabs, paste the API key, and click Create. The guide includes examples of how to use the ElevenLabs API in Cerb, such as listing voices and converting text to speech, demonstrating the full capabilities of the integration."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a ElevenLabs API Key.](#get-a-elevenlabs-api-key)
- [Create the ElevenLabs service in Cerb](#create-the-elevenlabs-service-in-cerb)
- [Examples](#examples)
  - [List voices](#list-voices)
  - [Text to speech](#text-to-speech)

# Introduction

In this guide we'll walk through the process of linking Cerb to ElevenLabs, a text-to-speech and speech-to-text API. You'll be able to use ElevenLabs's full API for any automations you wish to make.

# Get a ElevenLabs API Key.

Log in to your ElevenLabs Account or sign up if you don't already have one.

Click your user icon in the bottom left, choose **API Keys** and click the **Create API Key** button.

Name the key, click **Create** and then **Copy to Clipboard**

# Create the ElevenLabs service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **ElevenLabs**.

4. Paste the key you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Examples

## List voices

https://elevenlabs.io/docs/api-reference/voices/get-all

```
start:
  http.request/listVoices:
    output: http_response
    inputs:
      method: GET
      url: https://api.elevenlabs.io/v1/voices
      authentication: cerb:connected_account:elevenlabs
```

## Text to speech

https://elevenlabs.io/docs/api-reference/text-to-speech/convert

```
start:
  set:
    voice_id: 9BWtsMINqrJLrRacOk9x
  http.request/getVoices:
    output: http_response
    inputs:
      method: POST
      url: https://api.elevenlabs.io/v1/text-to-speech/{{voice_id}}?output_format=mp3_44100_128
      authentication: cerb:connected_account:elevenlabs
      headers:
        Content-Type: application/json
      body:
        text: This is speech from a Cerb automation.
        model_id: eleven_multilingual_v2
      #response:
      # resource:
      # expires@date: 1 hour
```
