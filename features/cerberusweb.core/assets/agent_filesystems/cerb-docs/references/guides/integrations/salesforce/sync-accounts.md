---
id: "guides-integrations-salesforce-sync-accounts"
title: "Synchronize Salesforce accounts to Cerb organizations"
url: "https://cerb.ai/guides/integrations/salesforce/sync-accounts/"
summary: "This webpage provides a comprehensive guide for integrating Salesforce with Cerb to synchronize account changes with Cerb organization records. It includes detailed instructions on authorizing a remote site in Salesforce, importing an example package in Cerb, creating an Apex class and trigger in Salesforce, and testing the webhook. The guide emphasizes the importance of setting up the integration in a Salesforce development sandbox before deploying it in a production environment. It also provides specific code snippets and configuration steps to ensure seamless data synchronization between Salesforce and Cerb."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Authorize the Remote Site in Salesforce](#authorize-the-remote-site-in-salesforce)
- [Import the example package in Cerb](#import-the-example-package-in-cerb)
- [Create the Apex class in Salesforce](#create-the-apex-class-in-salesforce)
- [Create the Apex trigger in Salesforce](#create-the-apex-trigger-in-salesforce)
- [Test the webhook](#test-the-webhook)

# Introduction

This guide will walk you through creating an integration where any changes to Salesforce accounts are automatically reflected in Cerb organization records.

You may want to follow this guide in your Salesforce development sandbox before deploying the changes in production.

# Authorize the Remote Site in Salesforce

If you haven't already, you'll need to add your Cerb base URL as a remote site in Salesforce. This allows Apex code to connect to Cerb.

Navigate to **Setup&nbsp;» Administer&nbsp;» Security Controls&nbsp;» Remote Site Settings**.

Click the **New Remote Site** button.

- **Remote Site Name:** `Cerb`
- **Remote Site URL:** (enter the base URL to your Cerb installation; e.g. `https://cerb.example/`)

Click the **Save** button.

# Import the example package in Cerb

Navigate to **Setup&nbsp;» Packages&nbsp;» Import**.

Paste the following package:

```
{
  "package": {
    "name": "Sync Salesforce Accounts to Cerb Orgs",
    "revision": 1,
    "requires": {
      "cerb_version": "9.0.7",
      "plugins": []
    },
    "configure": {
      "placeholders": [],
      "prompts": []
    }
  },
  "records": [
    {
      "uid": "webhook_salesforce",
      "_context": "webhook_listener",
      "name": "Salesforce Account Updated",
      "guid": "{{{random_string(40)|lower}}}",
      "extension_id": "cerb.webhooks.listener.engine.va",
      "extension_params": {
      "behavior_id": "{{{uid.behavior_webhook}}}"
      }
    }
  ],
  "bots": [
    {
      "uid": "bot_salesforce",
      "name": "Salesforce Sync Bot",
      "owner": {
        "context": "cerberusweb.contexts.app",
        "id": 0
      },
      "is_disabled": false,
      "params": {
        "config": null,
        "events": {
          "mode": "allow",
          "items": [
            "event.webhook.received"
          ]
        },
        "actions": {
          "mode": "allow",
          "items": [
            "core.bot.action.record.upsert"
          ]
        }
      },
      "image": null,
      "behaviors": [
        {
          "uid": "behavior_webhook",
          "title": "Webhook: Salesforce account updated",
          "is_disabled": false,
          "is_private": false,
          "priority": 50,
          "event": {
            "key": "event.webhook.received",
            "label": "Webhook received"
          },
          "nodes": [
            {
              "type": "switch",
              "title": "Do we have an org name in the webhook POST?",
              "status": "live",
              "nodes": [
                {
                  "type": "outcome",
                  "title": "Yes",
                  "status": "live",
                  "params": {
                    "groups": [
                      {
                        "any": 0,
                        "conditions": [
                          {
                            "condition": "http_verb",
                            "oper": "is",
                            "value": "POST"
                          },
                          {
                            "condition": "http_param",
                            "name": "name",
                            "oper": "!is",
                            "value": ""
                          }
                        ]
                      }
                    ]
                  }
                },
                {
                  "type": "outcome",
                  "title": "No",
                  "status": "live",
                  "params": {
                    "groups": [
                      {
                        "any": 0,
                        "conditions": []
                      }
                    ]
                  },
                  "nodes": [
                    {
                      "type": "action",
                      "title": "Exit",
                      "status": "live",
                      "params": {
                        "actions": [
                          {
                            "action": "set_http_status",
                            "value": "500"
                          },
                          {
                            "action": "set_http_body",
                            "value": "An organization name is required."
                          },
                          {
                            "action": "_exit",
                            "mode": ""
                          }
                        ]
                      }
                    }
                  ]
                }
              ]
            },
            {
              "type": "action",
              "title": "Upsert the org record",
              "status": "live",
              "params": {
                "actions": [
                  {
                    "action": "core.bot.action.record.upsert",
                    "context": "org",
                    "query": "{% set org_name = http_params.name|replace({'\"':''}) %}\r\nname:\"{{org_name}}\"",
                    "changeset_json": "{# Map Salesforce sobject fields to Cerb record fields #}\r\n{% set json = {\r\n\tname: http_params.name,\r\n\tstreet: http_params.billingstreet,\r\n\tcity: http_params.billingcity,\r\n\tprovince: http_params.billingstate,\r\n\tpostal: http_params.billingpostalcode,\r\n\tcountry: http_params.billingcountry,\r\n\twebsite: http_params.website,\r\n\tphone: http_params.phone,\r\n} %}\r\n{# Remove blank fields #}\r\n{% set json = array_diff(json,[null,'']) %}\r\n{# Output #}\r\n{{json|json_encode|json_pretty}}",
                    "run_in_simulator": "0",
                    "object_placeholder": "_record"
                  }
                ]
              }
            }
          ]
        }
      ]
    }
  ]
}
```

Click the **Import** button.

You should see the following confirmation:

 

# Create the Apex class in Salesforce

Navigate to **Setup&nbsp;» Build&nbsp;» Develop&nbsp;» Apex Classes**.

Click the **New** button above the view.

Paste the following code:

```
public with sharing class CerbWebhookRequest {
    @future (callout=true)
    static public void Post(String url, String body) {
        HttpRequest req = new HttpRequest();
        HttpResponse res = new HttpResponse();
        Http http = new Http();
        
        req.setEndpoint(url);
        req.setMethod('POST');
        req.setBody(body);

        try {
            res = http.send(req);
        } catch(System.CalloutException e) {
            System.debug('Callout error: '+ e);
            System.debug(res.toString());
        }
    }
}
```

Click the **Save** button above the editor.

# Create the Apex trigger in Salesforce

Navigate to **Setup&nbsp;» Build&nbsp;» Develop&nbsp;» Apex Triggers**.

Paste the following code:

```
trigger AccountChangeToCerbWebhook on Account (After Insert, After Update) {
  String webhook_url = 'https://cerb.example/webhooks/a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';
  
  for(Account a : Trigger.new) {
    Map<String, Object> fields = a.getPopulatedFieldsAsMap();
    
    string body = '_=';
    
    for(String fieldKey : fields.keySet()) {
      Object fieldValue = fields.get(fieldKey);
      String paramValue = '';
      
      if(fieldValue instanceof Datetime) {
        paramValue = String.valueOf(((DateTime)fieldValue).getTime()/1000);
      } else if(
        fieldValue instanceof Boolean
        || fieldValue instanceof Date
        || fieldValue instanceof Decimal
        || fieldValue instanceof Double
        || fieldValue instanceof ID
        || fieldValue instanceof Integer 
        || fieldValue instanceof Long
        || fieldValue instanceof String
        || fieldValue instanceof Time
        ) {
        paramValue = String.valueOf(fieldValue);
      }
      
      body += '&' + fieldKey + '=' + EncodingUtil.urlEncode(paramValue, 'UTF-8');
    }
    
    CerbWebhookRequest.Post(webhook_url, body);
  }
}
```

- Replace the value of `webhook_url` with your Cerb webhook URL. You can find this from **Search&nbsp;» Webhooks** in the **URL** column. Copy that link and paste it over the `cerb.example` here.

Click the **Save** button above the code editor.

By default, this function includes every account field with a value set, provided that value can be represented as a string of text.

# Test the webhook

Create or update an account in Salesforce.

You should see the change reflected in real-time in Cerb from **Search&nbsp;» Organizations**.

