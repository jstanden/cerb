---
id: "solutions-integrations-stabilityai"
title: "Stability.ai"
url: "https://cerb.ai/solutions/integrations/stabilityai/"
summary: "This page provides a step-by-step guide for integrating Cerb and Stability.ai, allowing users to use Stability.ai's full API in Cerb automations for image generation. To start, users must obtain an API key from their Stability.ai account, then create the Stability.ai service in Cerb and use it to generate images using its API."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a Stability.ai API Key.](#get-a-stabilityai-api-key)
- [Create the Stability.ai service in Cerb](#create-the-stabilityai-service-in-cerb)
- [Examples](#examples)
  - [Generate an image](#generate-an-image)

- [Resources](#resources)

# Introduction

In this guide we'll walk through the process of linking Cerb to Stability.ai. You'll be able to use Stability.ai's full API in Cerb automations for image generation.

# Get a Stability.ai API Key.

Log in to your Stability.ai Account or sign up if you don't already have one.

Choose **API Keys** in the menu on the left.

Copy the default key or click **Create API Key** to create a new one.

# Create the Stability.ai service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Stability.ai**.

4. Paste the key you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Examples

## Generate an image

https://platform.stability.ai/docs/api-reference#tag/Generate/paths/~1v2beta~1stable-image~1generate~1core/post

```
start:
  http.request/generate:
    output: http_response
    inputs:
      method: POST
      url: https://api.stability.ai/v2beta/stable-image/generate/core
      authentication: cerb:connected_account:stabilityai
      headers:
        Content-Type: multipart/form-data; boundary=abcdef
        Accept: image/*
      response:
        resource:
          expires@date: 1 hour
      body@text:
        --abcdef
        Content-Disposition: form-data; name="prompt"
        
        A profile picture of a humanoid robot in cyberpunk graphic novel style
        --abcdef
        Content-Disposition: form-data; name="output-format"
        
        png
        --abcdef--
```

# Resources

- Guide: [Create profile images with Stable Diffusion](/guides/machine-learning/image-generation/stable-diffusion-profile-images/)

