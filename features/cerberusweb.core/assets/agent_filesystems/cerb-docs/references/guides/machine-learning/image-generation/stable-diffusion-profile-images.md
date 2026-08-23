---
id: "guides-machine-learning-image-generation-stable-diffusion-profile-images"
title: "Create profile images with Stable Diffusion"
url: "https://cerb.ai/guides/machine-learning/image-generation/stable-diffusion-profile-images/"
summary: "This webpage provides a comprehensive guide on using Stable Diffusion to create profile images within Cerb, requiring version 10.4.2 or later. It outlines the steps to create a Stability.ai account, generate an API key, and set up a connected service in Cerb. The guide details importing a package to facilitate the integration and modifying the toolbar to include a Stable Diffusion interaction. Users are instructed on how to generate profile images by providing text prompts, with the option to select from multiple generated images. The page also includes references to Stable Diffusion's background and additional resources."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Connect Stability.ai to Cerb](#connect-stabilityai-to-cerb)
- [Import the workflow](#import-the-workflow)
- [Generate a profile image](#generate-a-profile-image)
- [References](#references)

# Introduction

This guide requires Cerb [10.4.2](/releases/10.4.2/) or later.

**Stable Diffusion** [1](#fn:stable-diffusion) is a text-to-image model trained on billions of captioned images. It can be used to generate new images from a given text description.

In this guide we'll use the Stable Diffusion API to generate profile images for workers, groups, roles, etc.

 

# Connect Stability.ai to Cerb

Follow the [Stability.ai integration guide](/solutions/integrations/stabilityai/) to get an API key and connect it to Cerb.

# Import the workflow

Navigate to **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» (empty)** and paste the following [KATA](/docs/kata/):

```
workflow:
  name: example.services.textToImage.stabilityai
  version: 2026-08-11T00:00:00Z
  description: Generate profile images with Stability.ai's models
  website: https://cerb.ai/resources/workflows/
  requirements:
    cerb_version: >=11.0 <12.0
    cerb_plugins: cerberusweb.core, 
  config:
    chooser/account:
      label: Stability.ai Account
      record_type: connected_account
records:
  automation/generateImage:
    fields:
      name: example.services.textToImage.stabilityai
      extension_id: cerb.trigger.interaction.worker
      description@text:
      script@raw:
        inputs:
          text/text:
            required@bool: yes
            type_options:
              max_length@int: 1000
              truncate@bool: yes
        
        start:
          set:
            config@json: {{cerb_workflow_config('example.services.textToImage.stabilityai')|json_encode}}
          
          await/feedback:
            duration:
              message: Generating image....
              until: 1 second
        
          http.request/stability:
            output: http_response
            inputs:
              method: POST
              url: https://api.stability.ai/v2beta/stable-image/generate/core
              authentication: cerb:connected_account:{{config.account}}
              headers:
                Content-Type: multipart/form-data; boundary=data1b2c3d4
                Accept: image/*
              response:
                resource:
                  expires@date: 1 hour
              body@text:
                --data1b2c3d4
                Content-Disposition: form-data; name="prompt"
                
                {{inputs.text}}
                --data1b2c3d4
                Content-Disposition: form-data; name="output_format"
                
                png
                --data1b2c3d4--
            on_success:
              return:
                image_url: {{cerb_url('c=ui&a=image&token=' ~ http_response.body|split(':')|last)}}
      policy_kata@raw:
        commands:
          http.request:
            deny/url@bool: {{inputs.url is not pattern ('https://api.stability.ai/v2beta/stable-image/generate/*')}}
            allow@bool: yes
  automation/generateInteraction:
    fields:
      name: example.interaction.recordProfileImage.stabilityai
      extension_id: cerb.trigger.interaction.worker
      description@text:
      script@raw:
        start:
          set/init:
            samples@int: 4
            image_urls@list:
            
          await/prompt:
            form:
              title: AI Image Generator
              elements:
                textarea/prompt_text:
                  label: Prompt:
                  required@bool: yes
                  max_length@int: 1000
                  truncate@bool: yes
                  placeholder: A profile picture of an android tech worker in cyberpunk graphic novel style
          
          repeat/n:
            each@csv: {{range(1, samples)|join(',')}}
            as: n
            do:
              await/generate:
                interaction:
                  output: result
                  uri: cerb:automation:example.services.textToImage.stabilityai
                  inputs:
                    text@key: prompt_text
              var.set:
                inputs:
                  key: image_urls:{{image_urls|length}}
                  value:
                    url: {{result.image_url}}
        
          await/preview:
            form:
              elements:
                say:
                  content@text:
                    {{prompt_text}}
                    ---------------
                sheet/prompt_image:
                  required@bool: yes
                  data@key: image_urls
                  limit: 5
                  schema:
                    layout:
                      headings@bool: no
                      paging@bool: no
                      filtering@bool: no
                      style: grid
                    columns:
                      selection/__index:
                        params:
                          mode: single
                      text/image:
                        params:
                          value_template@raw:
                            <img src="{{url}}" style="width:256px;height:auto;">
          
          return:
            image:
              url: {{image_urls[prompt_image].url}}
      policy_kata@raw:
        commands:
          function:
            deny/uri@bool: {{uri != 'cerb:automation:example.services.textToImage.stabilityai'}}
            allow@bool: yes
  toolbar_section/generateToolbar:
    fields:
      name: Generate Profile
      toolbar_name: record.profile.image.editor
      priority@int: 50
      is_disabled: 0
      toolbar_kata@raw:
        interaction/stability:
          label: Stable Diffusion
          icon: picture
          uri: cerb:automation:example.interaction.recordProfileImage.stabilityai
```

Click the **Continue** button and select your Stability.ai connected account when prompted.

# Generate a profile image

Edit a record (worker, group, role, contact, organization).

For instance:

- Click on your name in the top right, then **Settings**. On the **Profile** tab, click the **Edit** button to the right of **Photo**.
- Edit a group from **Search&nbsp;» Groups**.

Click the new **Stable Diffusion** button in the **Image generation:** section.

 

Type a description of the image you'd like to generate in the prompt:

 

```
A profile picture of a techie woman in cyberpunk graphic novel style
```

Click the blue **Continue** button.

After a few seconds you'll be given four options from your prompt:

 

If you like an image, you can select it and press **Continue**.

Or you can generate a new set of images by clicking **Start over**.

# References

1. Wikipedia - Stable Diffusion https://en.wikipedia.org/wiki/Stable\_Diffusion&nbsp;[↩](#fnref:stable-diffusion)

