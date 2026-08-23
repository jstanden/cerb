---
id: "workflows-cerb-integrations-deepl-translate"
title: "Translate (DeepL)"
url: "https://cerb.ai/workflows/cerb.integrations.deepl.translate/"
summary: "This webpage provides a comprehensive guide on integrating Cerb with DeepL to facilitate the translation of inbound and outbound email messages. It includes detailed instructions on setting up a DeepL account, creating a connected account in Cerb, importing and configuring the necessary workflow, and utilizing the translation features. The guide covers translating both incoming and outgoing emails, with step-by-step processes for setting up the translation workflow, selecting target languages, and using the translation tools within Cerb's interface. The integration supports a wide range of languages and offers options for both free and production API endpoints."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
  - [DeepL](#deepl)
  - [Cerb](#cerb)
    - [Create a DeepL connected account](#create-a-deepl-connected-account)
    - [Import the workflow](#import-the-workflow)
    - [Configure the workflow](#configure-the-workflow)

- [Usage](#usage)
  - [Translate incoming email messages](#translate-incoming-email-messages)
  - [Translate outgoing email messages](#translate-outgoing-email-messages)

# Introduction

This workflow integrates Cerb with DeepL for translating inbound and outbound email messages into various languages.

# Installation

## DeepL

Create a free DeepL account.

Click your name in the top right and select **Account** from the menu.

Select the **API Keys** tab.

Click the **Create key** button in the top right. Use a descriptive name like "Cerb".

Save the API key so you can use it in the following step.

## Cerb

### Create a DeepL connected account

In Cerb, navigate to **Search&nbsp;» Connected Services&nbsp;» (+)** and select **DeepL** from the **Library**.

 

Paste your **API Key** and click the **Create** button.

### Import the workflow

Navigate to **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Empty**.

Paste the following KATA into the large text box:

```
workflow:
  name: cerb.integrations.deepl.translate
  description: Translate inbound and outbound email messages using the DeepL API
  website: https://cerb.ai/workflows/cerb.integrations.deepl.translate/
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core
  config:
    picklist/base_url:
      label: API Endpoint:
      multiple@bool: no
      default: https://api-free.deepl.com
      options@list:
        https://api-free.deepl.com
        https://api.deepl.com
    chooser/account_id:
      label: DeepL Account:
      record_type: connected_account
      record_query: service:(deepl)
      multiple@bool: no
  version: 2025-02-21T00:00:00Z
records:
  automation/translateFunc:
    fields:
      name: cerb.integrations.deepl.translate.function
      extension_id: cerb.trigger.automation.function
      description@text:
      script@raw:
        inputs:
          text/text:
            type: freeform
            required@bool: yes
          text/target_lang:
            type: freeform
            required@bool: no
        
        start:
          set/init:
            config@json: {{cerb_workflow_config('cerb.integrations.deepl.translate')|json_encode}}
          
          http.request/translate:
            output: http_response
            inputs:
              method: POST
              url: {{config.base_url}}/v2/translate
              authentication: cerb:connected_account:{{config.account_id}}
              headers:
                Content-Type: application/json
              body:
                target_lang: {{inputs.target_lang|default('EN')}}
                text:
                  0: {{inputs.text}}
            on_success:
              outcome/ok:
                if@bool: {{200 == http_response.status_code}}
                then:
                  set:
                    response_body@json: {{http_response.body}}
                  return:
                    source_lang: {{response_body.translations|first.detected_source_language}}
                    target_lang: {{inputs.target_lang|default('EN')}}
                    text: {{response_body.translations|first.text}}
      policy_kata@raw:
        commands:
          http.request:
            deny/url@bool:
              {{
                inputs.url is not prefixed (cerb_workflow_config('cerb.integrations.deepl.translate','base_url'))
              }}
            allow@bool: yes
  automation/translateInteraction:
    fields:
      name: cerb.integrations.deepl.translate.interaction
      extension_id: cerb.trigger.interaction.worker
      description@text:
      script@raw:
        inputs:
          text/text:
            type: freeform
            default: {{caller_params.selected_text}}
        
        start:
          set:
            languages:
              BG:
                language: BG
                name: Bulgarian
                supports_formality@bool: no
              ZH:
                language: ZH
                name: Chinese (simplified)
                supports_formality@bool: no
              ZH-HANS:
                language: ZH-HANS
                name: Chinese (simplified)
                supports_formality@bool: no
              CS:
                language: CS
                name: Czech
                supports_formality@bool: no
              DA:
                language: DA
                name: Danish
                supports_formality@bool: no
              NL:
                language: NL
                name: Dutch
                supports_formality@bool: yes
              EN-US:
                language: EN-US
                name: English (American)
                supports_formality@bool: no
              EN-GB:
                language: EN-GB
                name: English (British)
                supports_formality@bool: no
              ET:
                language: ET
                name: Estonian
                supports_formality@bool: no
              FI:
                language: FI
                name: Finnish
                supports_formality@bool: no
              FR:
                language: FR
                name: French
                supports_formality@bool: yes
              DE:
                language: DE
                name: German
                supports_formality@bool: yes
              EL:
                language: EL
                name: Greek
                supports_formality@bool: no
              HU:
                language: HU
                name: Hungarian
                supports_formality@bool: no
              ID:
                language: ID
                name: Indonesian
                supports_formality@bool: no
              IT:
                language: IT
                name: Italian
                supports_formality@bool: yes
              JA:
                language: JA
                name: Japanese
                supports_formality@bool: yes
              KO:
                language: KO
                name: Korean
                supports_formality@bool: no
              LV:
                language: LV
                name: Latvian
                supports_formality@bool: no
              LT:
                language: LT
                name: Lithuanian
                supports_formality@bool: no
              NB:
                language: NB
                name: Norwegian (Bokmål)
                supports_formality@bool: no
              PL:
                language: PL
                name: Polish
                supports_formality@bool: yes
              PT-BR:
                language: PT-BR
                name: Portuguese (Brazilian)
                supports_formality@bool: yes
              PT-PT:
                language: PT-PT
                name: Portuguese (European)
                supports_formality@bool: yes
              RO:
                language: RO
                name: Romanian
                supports_formality@bool: no
              RU:
                language: RU
                name: Russian
                supports_formality@bool: yes
              SK:
                language: SK
                name: Slovak
                supports_formality@bool: no
              SL:
                language: SL
                name: Slovenian
                supports_formality@bool: no
              ES:
                language: ES
                name: Spanish
                supports_formality@bool: yes
              SV:
                language: SV
                name: Swedish
                supports_formality@bool: no
              TR:
                language: TR
                name: Turkish
                supports_formality@bool: no
              UK:
                language: UK
                name: Ukrainian
                supports_formality@bool: no
          
          outcome/noSelection:
            if@bool: {{inputs.text is empty}}
            then:
              set:
                message__context: message
                message_id: {{caller_params.message_id}}
              var.set:
                inputs:
                  key: inputs:text
                  value: {{message_content}}
          
          await/input:
            form:
              title: Translate Text
              elements:
                textarea/prompt_text:
                  label: Text to translate:
                  required@bool: yes
                  default: {{inputs.text}}
                sheet/prompt_target_lang:
                  label: Target language:
                  data@key: languages
                  required@bool: yes
                  limit: 40
                  default: EN-US
                  schema:
                    layout:
                      style: grid
                      filtering@bool: no
                      paging@bool: no
                      headings@bool: no
                    columns:
                      selection/language:
                        params:
                          mode: single
                      text/name:
                        params:
                          bold@bool: yes
                submit:
                  buttons:
                    continue/translate:
                      label: Translate
                      icon: circle-ok
                      icon_at: start
                    reset/startOver:
                      label: Start over
                      icon_at: start
                      icon: restart
                      style: secondary
          
          function/translate:
            uri: cerb:automation:cerb.integrations.deepl.translate.function
            output: results
            inputs:
              text: {{prompt_text}}
              target_lang: {{prompt_target_lang}}
          
          await/output:
            form:
              elements:
                say:
                  content@text:
                    {{results.source_lang}} -> {{results.target_lang}}
                editor:
                  line_numbers@bool: no
                  readonly@bool: yes
                  syntax: text
                  default: {{results.text}}
          
          return:
            snippet: {{results.text}}
      policy_kata@raw:
        commands:
          function:
            deny/uri@bool: {{uri != 'cerb:automation:cerb.integrations.deepl.translate.function'}}
            allow@bool: yes
  toolbar_section/mailReadToolbar:
    fields:
      name: DeepL Translate
      toolbar_name: mail.read
      priority@int: 100
      is_disabled: 0
      toolbar_kata@raw:
        interaction/translate:
          label: Translate
          uri: cerb:automation:cerb.integrations.deepl.translate.interaction
          icon: translate
  toolbar_section/mailReplyToolbar:
    fields:
      name: DeepL Translate
      toolbar_name: mail.reply
      priority@int: 100
      is_disabled: 0
      toolbar_kata@raw:
        interaction/translate:
          label: Translate
          uri: cerb:automation:cerb.integrations.deepl.translate.interaction
          icon: translate
```

Click the **Continue** button.

### Configure the workflow

| Field | &nbsp; |
| --- | --- |
| **API Endpoint:** | Choose between the free or production API endpoints. |
| **DeepL Account:** | A DeepL [connected account](/solutions/integrations/deepl/). |

Click the **Continue** button twice.

# Usage

### Translate incoming email messages

Navigate to the profile of a ticket in a foreign language.

Alternatively, use **Setup&nbsp;» Mail&nbsp;» Incoming&nbsp;» Import** to import a test message like the following:

```
From: Lukas Müller <lukas.mueller@cerb.example>
To: support@cerb.example
Subject: Test: Überprüfung der E-Mail-Benachrichtigung
Message-ID: <13579@cerb.example>
Date: Fri, 18 Oct 2024 10:15:32 +0200
MIME-Version: 1.0
Content-Type: text/plain; charset="UTF-8"
Content-Transfer-Encoding: 8bit

Hallo Support-Team,

dies ist eine Test-E-Mail, um die Funktionalität und Zustellung unserer E-Mail-Benachrichtigungen zu überprüfen. Bitte ignorieren Sie diese Nachricht.

Vielen Dank für Ihre Unterstützung!

Mit freundlichen Grüßen,  
Lukas Müller
```

On the ticket profile, a new **Translate** button is in the message toolbar.

 

You can optionally highlight a passage from the message before clicking the button.

Select a target language (default American English) and click the **Translate** button.

 

The translated text is displayed:

 

### Translate outgoing email messages

When replying to a message, click the **Translate** button in the editor toolbar.

 

Type a message, select a target language, then click the **Translate** button.

 

The translated text will be displayed. You can manually copy/paste it, or click the **Continue** button to automatically insert it into the outgoing message.

 
