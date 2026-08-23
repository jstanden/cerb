---
id: "solutions-integrations-n8n"
title: "n8n"
url: "https://cerb.ai/solutions/integrations/n8n/"
summary: "This guide provides a step-by-step tutorial on integrating Cerb with n8n workflows. It covers the creation of a custom Cerb workflow that utilizes n8n as an external service."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Create an Access Token in Cerb](#create-an-access-token-in-cerb)
- [Create Cerb Credential in n8n](#create-cerb-credential-in-n8n)
- [Examples](#examples)
  - [In n8n](#in-n8n)
  - [In Cerb](#in-cerb)

# Introduction

In this guide we'll walk through the process of linking Cerb to n8n. You'll be able to use Cerb's API in n8n workflows.

If you're using Docker, you may need to change the hostname `localhost` to `host.docker.internal` in the example URLs.

# Create an Access Token in Cerb

1. In Cerb, navigate to **Search&nbsp;» Oauth Apps**.

2. Click the **(+)** icon in the top right of the list.

3. Name the app (eg `n8n`) and input any Callback URL found in n8n, (usually `http://YOUR-N8N-HOST/rest/oauth2-credential/callback`).

4. Click **Save Changes**.

5. Navigate to **Setup&nbsp;» Developers&nbsp;» Oauth2 Token Generator**.

6. Choose the n8n OAuth App you made earlier, select your scopes and expiry period and click **Create**.

7. Copy the `Access Token` for use in n8n.

# Create Cerb Credential in n8n

1. Log in to your n8n instance.

2. Click the **(+)** icon in the top left.

3. Select **Credential**.

4. Pick **Header Auth** from the list.

5. Name the Credential at the top (eg. `Cerb`).

6. In the **Name** field, put `Authorization`. In the **Value** field put `Bearer <your Cerb access token>`.

# Examples

In this example, we will create a button on ticket profiles that will send information about the ticket and worker to n8n. A n8n workflow will then gather information about the ticket via an API call and leave a comment on the ticket mentioning the worker.

## In n8n

Create a new workflow and paste the following JSON.

```
{
  "nodes": [
    {
      "parameters": {
        "path": "fcce0f03-ca9a-4d72-be69-e71022e81022",
        "responseMode": "lastNode",
        "options": {}
      },
      "type": "n8n-nodes-base.webhook",
      "typeVersion": 2,
      "position": [
        0,
        0
      ],
      "id": "3a58055b-acca-463f-bde5-9d377d05161f",
      "name": "Webhook",
      "webhookId": "fcce0f03-ca9a-4d72-be69-e71022e81022"
    },
    {
      "parameters": {
        "method": "POST",
        "url": "=http://YOUR-CERB-HOST/rest/records/comment/create.json ",
        "authentication": "genericCredentialType",
        "genericAuthType": "httpHeaderAuth",
        "sendBody": true,
        "contentType": "form-urlencoded",
        "bodyParameters": {
          "parameters": [
            {
              "name": "fields[comment]",
              "value": "=Hello @{{ $('Webhook').item.json.body.worker_mention }}! Your ticket submission is succesful. The importance value of this ticket is {{ $json.importance }}."
            },
            {
              "name": "fields[author__context]",
              "value": "app"
            },
            {
              "name": "fields[author_id]",
              "value": "0"
            },
            {
              "name": "fields[target__context]",
              "value": "ticket"
            },
            {
              "name": "fields[target_id]",
              "value": "={{ $json.id }}"
            }
          ]
        },
        "options": {}
      },
      "type": "n8n-nodes-base.httpRequest",
      "typeVersion": 4.2,
      "position": [
        460,
        0
      ],
      "id": "16acaa37-8cc5-45b5-8b45-40c6ae7221ad",
      "name": "HTTP Request (create comment)",
      "credentials": {
        "httpHeaderAuth": {
          "id": "fXkmmVxS64zoLLon",
          "name": "Cerb"
        }
      }
    },
    {
      "parameters": {
        "url": "=http://YOUR-CERB-HOST/rest/records/ticket/{{ $json.body.record_id }}.json ",
        "authentication": "genericCredentialType",
        "genericAuthType": "httpHeaderAuth",
        "options": {}
      },
      "type": "n8n-nodes-base.httpRequest",
      "typeVersion": 4.2,
      "position": [
        240,
        0
      ],
      "id": "2bae0d84-4616-40b6-b05b-69ca75248bb5",
      "name": "HTTP Request (lookup ticket)",
      "credentials": {
        "httpHeaderAuth": {
          "id": "fXkmmVxS64zoLLon",
          "name": "Cerb"
        }
      }
    }
  ],
  "connections": {
    "Webhook": {
      "main": [
        [
          {
            "node": "HTTP Request (lookup ticket)",
            "type": "main",
            "index": 0
          }
        ]
      ]
    },
    "HTTP Request (create comment)": {
      "main": [
        []
      ]
    },
    "HTTP Request (lookup ticket)": {
      "main": [
        [
          {
            "node": "HTTP Request (create comment)",
            "type": "main",
            "index": 0
          }
        ]
      ]
    }
  },
  "pinData": {},
  "meta": {
    "templateCredsSetupCompleted": true,
    "instanceId": "32a373f991343b9745dc16729efab279d0b4a376b145832c8ea25cccda5a6f6e"
  }
}
```

1. Correct the `HTTP Request` nodes, replacing `YOUR-CERB-HOST` with the appropriate information. Then open the `Webhook Trigger` node and copy down the URL for use later.

2. Click the **Test Workflow** button to prepare for the next step.

## In Cerb

1. Navigate to **Search&nbsp;» Workflows**.

2. Click the **(+)** icon in the top right of the list.

3. Select (Empty) and paste the following Workflow KATA:

```
workflow:
  name: wgm.integrations.n8n
  version: 2025-03-29T04:45:37Z
  description: A sample integration with n8n workflows
  website: https://cerb.ai/resources/workflows/
  requirements:
    cerb_version: >=11.0 <11.2
    cerb_plugins: cerberusweb.core, 
  config:
    text/url:
      label: Your n8n webhook url
records:
  automation/ticketInteraction:
    fields:
      name: wgm.n8n.interaction
      extension_id: cerb.trigger.interaction.worker
      description: worker interaction to send ticket info to n8n
      script@raw:
        start:
          set:
            config@json: {{cerb_workflow_config('wgm.integrations.n8n')|json_encode}}
          await/sending:
            duration:
              message: Sending to n8n...
              until: 1 second
          http.request/n8n:
            output: http_response
            inputs:
              method: GET
              url: {{config.url}}
              body:
                record_id: {{caller_params.record_id}}
                worker_id: {{worker_id}}
                worker_mention: {{worker_at_mention_name}}
          
      policy_kata@raw:
        commands:
          http.request:
            deny/method@bool: {{inputs.method not in ['GET']}}
            allow@bool: yes
  toolbar_section/n8nToolbar:
    fields:
      name: n8n
      toolbar_name: record.profile
      priority@int: 50
      is_disabled: 0
      toolbar_kata@raw:
        interaction/n8nPrompt:
          label: Send to n8n
          icon: electricity
          uri: cerb:automation:wgm.n8n.interaction
          hidden@bool: {{record__type is not pattern ("ticket")}}
```

Put your n8n webhook URL in the box when prompted.

This will place a **Send to n8n** button on the toolbar at the top of every ticket profile. Click it and a comment should be added to that ticket. If not, check the n8n workflow for any errors.

From here, you can edit or add to the Cerb and n8n workflows to accomplish whatever task you desire.

