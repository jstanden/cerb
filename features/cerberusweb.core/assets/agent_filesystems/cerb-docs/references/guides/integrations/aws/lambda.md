---
id: "guides-integrations-aws-lambda"
title: "Run AWS Lambda functions from a Cerb bot"
url: "https://cerb.ai/guides/integrations/aws/lambda/"
summary: "This page provides a comprehensive guide on how to run AWS Lambda functions from a Cerb bot, enabling the automation of workflows that require external services beyond Cerb's built-in capabilities. It details the process of configuring Amazon Web Services (AWS) within Cerb, creating and deploying a Lambda function, and updating IAM policies to allow Cerb bots to invoke these functions. The guide includes a step-by-step walkthrough for importing and testing an AWS Lambda Bot in Cerb, which can perform DNS lookups by interacting with AWS Lambda. It also explains the underlying mechanics of the bot's behaviors, including how it handles conversations, processes inputs, and communicates with AWS Lambda through HTTP requests. The page concludes with instructions on how to extend or modify the bot for additional functionalities."
tags: ["guides"]
---
# Introduction

 

You can automate all kinds of workflows with bots in Cerb; but sometimes you need to accomplish something that isn't possible with the built-in functionality. Fortunately, with connected accounts and the **Execute HTTP Request** action, your bots can automate nearly anything by interacting with third-party services.

One of the most powerful ways to extend bot automation is with Lambda[1](#fn:aws-lambda) from Amazon Web Services (AWS).

AWS Lambda is a cloud-based service for building microservices at scale without managing any servers. You can write code in Node.js, Java, or Python, and bundle any third-party libraries. Amazon automatically takes care of deploying, scaling, and load balancing your code across multiple containers[2](#fn:aws-containers).

Generally, you send inputs to a Lambda function with an HTTP request to their API. The function does something interesting with those inputs and then returns output.

This simple concept allows you to add countless new capabilities to bots in Cerb.

For instance, you can write a Lambda function for taking form data and filling in the editable fields of a PDF file. This can be quickly accomplished using Node.js or Python and the pdftk library. Bots would send form data to the function and receive a download URL for the generated PDF.

We'll demonstrate Lambda integration in this guide by adding DNS[3](#fn:dns) lookup functionality to a conversational bot.

- [Configure the Amazon Web Services service in Cerb](#configure-the-amazon-web-services-service-in-cerb)
- [Log in to Amazon Web Services](#log-in-to-amazon-web-services)
  - [Add a new Lambda function](#add-a-new-lambda-function)
  - [Update your IAM policy](#update-your-iam-policy)

- [Import AWS Lambda Bot in Cerb](#import-aws-lambda-bot-in-cerb)
- [Test the bot](#test-the-bot)
- [Learn how the bot works](#learn-how-the-bot-works)
- [References](#references)

# Configure the Amazon Web Services service in Cerb

1. Log into Cerb as an administrator.

2. Navigate to **Search&nbsp;» Connected Accounts**.

3. If you don't have a connected account for Amazon Web Services yet, you can [follow these instructions](/solutions/integrations/aws/) to create one.

# Log in to Amazon Web Services

We'll start by logging in to the AWS Management Console.

If you don't have an AWS account, you can sign up for free at: https://aws.amazon.com

## Add a new Lambda function

Navigate to Lambda from the **Services** menu at the top.

Click the blue **Create a Lambda function** button.

Filter the runtime to **Node.js 6.10** (or latest version).

Select **Blank Function**.

Click the blue **Next** button.

In **Configure function**:

- **Name:** CerbDnsLookup
- **Description:** Resolve IPs and hostnames

In **Lambda function code**, paste the following:

```
'use strict';
const dns = require('dns');

exports.handler = (event, context, callback) => {
  if(undefined === event.mode || 0 === event.mode.length) {
    callback("The 'mode' parameter is required.");
    return;
  }

  if(undefined === event.value || 0 === event.value.length) {
    callback("The 'value' parameter is required.");
    return;
  }

  switch(event.mode) {
    // Reverse
    case 'reverse':
      dns.reverse(event.value, function(err, hostnames) {
        if(err) {
          callback(err);
          return;
        }

        callback(null, hostnames);
      });
      break;

    // MX
    case 'mx':
      dns.resolveMx(event.value, function(err, exchanges) {
        if(err) {
          callback(err);
          return;
        }

        callback(null, exchanges);
      });
      break;

    // Resolve
    default:
      dns.resolve(event.value, function(err, addresses) {
        if(err) {
          callback(err);
          return;
        }

        callback(null, addresses);
      });
      break;
  }
};
```

In **Lambda function handler and role**:

- **Role:** Choose an existing role (if one exists), or create a new one. This determines what services and resources the Lambda function can access. For this example you don't need to add anything to the defaults.

You can ignore **Advanced settings** for now. Later on you may need to configure a Lambda function to run inside an existing Virtual Private Cloud (VPC).

Click the blue **Next** button in the bottom right.

Click the blue **Create function** button in the bottom right.

Now we can give Cerb bots access to this function.

## Update your IAM policy

Navigate to IAM from the **Services** menu at the top of the page.

We need to update the _policy_ in your Amazon Web Services (AWS) account to describe the services that your Cerb bot is allowed to use. This is accomplished with the Identity Access Management (IAM) service.

We're going to add access to invoke AWS Lambda functions prefixed with **Cerb**\*

Select **Policies** in the navigation on the left.

Find your bot's policy in the list or create a new one. In the earlier [instructions](/solutions/integrations/aws/) we created a policy named **CerbBot**.

Click the **Edit Policy** button.

Select the **JSON** tab.

Add the following block to the `Statement` list:

```
{
    "Sid": "CerbLambdaInvoke",
    "Effect": "Allow",
    "Action": [
        "lambda:InvokeAsync",
        "lambda:InvokeFunction"
    ],
    "Resource": [
        "arn:aws:lambda:*:*:function:Cerb*"
    ]
}
```

Click the blue **Review policy** button in the bottom right.

Click the blue **Save changes** button in the bottom right.

# Import AWS Lambda Bot in Cerb

Now we're ready to create the bot that interacts with AWS using our connected account.

Navigate to **Setup&nbsp;» Packages&nbsp;» Import**.

Copy and paste the following behavior into the large text box:

```
{
  "package": {
  "name": "AWS Lambda Bot",
  "cerb_version": "9.1.0",
  "revision": 1,
  "requires": {
    "cerb_version": "9.1.0"
  },
  "configure": {
    "prompts": [
      {
        "type": "chooser",
        "label": "AWS Account:",
        "key": "aws_account_id",
        "params": {
          "context": "cerberusweb.contexts.connected_account",
          "query": "aws OR amazon",
          "single": true
        }
      },
      {
        "type": "text",
        "label": "AWS Region:",
        "key": "aws_region",
        "params": {
          "default": "us-west-2"
        }
      }
    ],
    "placeholders": [
    ]
  }
  },
  "bots": [
    {
      "uid": "bot_33",
      "name": "AWS Lambda Bot",
      "owner": {
        "context": "cerberusweb.contexts.app",
        "id": 0
      },
      "is_disabled": false,
      "params": {
        "config": null,
        "events": {
          "mode": "all",
          "items": []
        },
        "actions": {
          "mode": "all",
          "items": []
        }
      },
      "image": "data:image/png;base64,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",
      "behaviors": [
        {
          "uid": "behavior_180",
          "title": "DNS conversational behavior",
          "is_disabled": false,
          "is_private": true,
          "priority": 50,
          "event": {
            "key": "event.message.chat.worker",
            "label": "Conversation with worker"
          },
          "nodes": [
            {
              "type": "action",
              "title": "Hi!",
              "status": "live",
              "params": {
                "actions": [
                  {
                    "action": "send_message",
                    "message": "Hi, {{worker_first_name}}!",
                    "format": "",
                    "delay_ms": "250"
                  },
                  {
                    "action": "send_message",
                    "message": "I can perform DNS lookups for you.",
                    "format": "",
                    "delay_ms": "1000"
                  }
                ]
              }
            },
            {
              "type": "loop",
              "title": "Menu",
              "status": "live",
              "params": {
                "foreach_json": "[\"*\"]",
                "as_placeholder": "iterations"
              },
              "nodes": [
                {
                  "type": "action",
                  "title": "What would you like to look up?",
                  "status": "live",
                  "params": {
                    "actions": [
                      {
                        "action": "send_message",
                        "message": "What would you like to look up?",
                        "format": "",
                        "delay_ms": "500"
                      },
                      {
                        "action": "prompt_buttons",
                        "options": "Hostname\r\nIP\r\nMX\r\nBye",
                        "color_from": "#4795f7",
                        "color_mid": "#4795f7",
                        "color_to": "#4795f7",
                        "style": ""
                      }
                    ]
                  }
                },
                {
                  "type": "action",
                  "title": "Save mode",
                  "status": "live",
                  "params": {
                    "actions": [
                      {
                        "action": "_set_custom_var",
                        "value": "{{message}}",
                        "format": "",
                        "is_simulator_only": "0",
                        "var": "dns_mode"
                      }
                    ]
                  }
                },
                {
                  "type": "switch",
                  "title": "Action:",
                  "status": "live",
                  "nodes": [
                    {
                      "type": "outcome",
                      "title": "Hostname",
                      "status": "live",
                      "params": {
                        "groups": [
                          {
                            "any": 0,
                            "conditions": [
                              {
                                "condition": "message",
                                "oper": "is",
                                "value": "Hostname"
                              }
                            ]
                          }
                        ]
                      },
                      "nodes": [
                        {
                          "type": "action",
                          "title": "What hostname?",
                          "status": "live",
                          "params": {
                            "actions": [
                              {
                                "action": "send_message",
                                "message": "What hostname would you like to find an IP for?",
                                "format": "",
                                "delay_ms": "1000"
                              },
                              {
                                "action": "prompt_text",
                                "placeholder": "e.g. cerb.ai"
                              }
                            ]
                          }
                        },
                        {
                          "type": "action",
                          "title": "Run behavior",
                          "status": "live",
                          "params": {
                            "actions": [
                              {
                                "action": "_run_behavior",
                                "on": "_trigger_va_id",
                                "behavior_id": "{{{uid.behavior_181}}}",
                                "var_mode": "resolve",
                                "var_value": "{{message}}",
                                "run_in_simulator": "0",
                                "var": "_behavior"
                              }
                            ]
                          }
                        },
                        {
                          "type": "action",
                          "title": "parseResponse()",
                          "status": "live",
                          "params": {
                            "actions": [
                              {
                                "action": "_run_subroutine",
                                "subroutine": "parseResponse()"
                              }
                            ]
                          }
                        }
                      ]
                    },
                    {
                      "type": "outcome",
                      "title": "IP",
                      "status": "live",
                      "params": {
                        "groups": [
                          {
                            "any": 0,
                            "conditions": [
                              {
                                "condition": "message",
                                "oper": "is",
                                "value": "IP"
                              }
                            ]
                          }
                        ]
                      },
                      "nodes": [
                        {
                          "type": "action",
                          "title": "What IP?",
                          "status": "live",
                          "params": {
                            "actions": [
                              {
                                "action": "send_message",
                                "message": "What IP would you like to find a hostname for?",
                                "format": "",
                                "delay_ms": "1000"
                              },
                              {
                                "action": "prompt_text",
                                "placeholder": "e.g. 8.8.8.8"
                              }
                            ]
                          }
                        },
                        {
                          "type": "action",
                          "title": "Run behavior",
                          "status": "live",
                          "params": {
                            "actions": [
                              {
                                "action": "_run_behavior",
                                "on": "_trigger_va_id",
                                "behavior_id": "{{{uid.behavior_181}}}",
                                "var_mode": "reverse",
                                "var_value": "{{message}}",
                                "run_in_simulator": "0",
                                "var": "_behavior"
                              }
                            ]
                          }
                        },
                        {
                          "type": "action",
                          "title": "parseResponse()",
                          "status": "live",
                          "params": {
                            "actions": [
                              {
                                "action": "_run_subroutine",
                                "subroutine": "parseResponse()"
                              }
                            ]
                          }
                        }
                      ]
                    },
                    {
                      "type": "outcome",
                      "title": "MX",
                      "status": "live",
                      "params": {
                        "groups": [
                          {
                            "any": 0,
                            "conditions": [
                              {
                                "condition": "message",
                                "oper": "is",
                                "value": "MX"
                              }
                            ]
                          }
                        ]
                      },
                      "nodes": [
                        {
                          "type": "action",
                          "title": "What hostname?",
                          "status": "live",
                          "params": {
                            "actions": [
                              {
                                "action": "send_message",
                                "message": "What hostname would you like to find mail servers for?",
                                "format": "",
                                "delay_ms": "1000"
                              },
                              {
                                "action": "prompt_text",
                                "placeholder": "e.g. cerb.email"
                              }
                            ]
                          }
                        },
                        {
                          "type": "action",
                          "title": "Run behavior",
                          "status": "live",
                          "params": {
                            "actions": [
                              {
                                "action": "_run_behavior",
                                "on": "_trigger_va_id",
                                "behavior_id": "{{{uid.behavior_181}}}",
                                "var_mode": "mx",
                                "var_value": "{{message}}",
                                "run_in_simulator": "0",
                                "var": "_behavior"
                              }
                            ]
                          }
                        },
                        {
                          "type": "action",
                          "title": "parseResponse()",
                          "status": "live",
                          "params": {
                            "actions": [
                              {
                                "action": "_run_subroutine",
                                "subroutine": "parseResponse()"
                              }
                            ]
                          }
                        }
                      ]
                    },
                    {
                      "type": "outcome",
                      "title": "Bye",
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
                          "title": "Bye!",
                          "status": "live",
                          "params": {
                            "actions": [
                              {
                                "action": "send_message",
                                "message": "Bye!",
                                "format": "",
                                "delay_ms": "1000"
                              },
                              {
                                "action": "window_close"
                              }
                            ]
                          }
                        }
                      ]
                    }
                  ]
                }
              ]
            },
            {
              "type": "subroutine",
              "title": "parseResponse()",
              "status": "live",
              "nodes": [
                {
                  "type": "switch",
                  "title": "Have a response?",
                  "status": "live",
                  "nodes": [
                    {
                      "type": "outcome",
                      "title": "No, error",
                      "status": "live",
                      "params": {
                        "groups": [
                          {
                            "any": 0,
                            "conditions": [
                              {
                                "condition": "_custom_script",
                                "tpl": "{% if _behavior.response_json is iterable and _behavior.response_json.errorMessage is not empty %}true{% endif %}",
                                "oper": "is",
                                "value": "true"
                              }
                            ]
                          }
                        ]
                      },
                      "nodes": [
                        {
                          "type": "action",
                          "title": "Error!",
                          "status": "live",
                          "params": {
                            "actions": [
                              {
                                "action": "send_message",
                                "message": "I had trouble: {{_behavior.response_json.errorMessage}}",
                                "format": "",
                                "delay_ms": "1000"
                              }
                            ]
                          }
                        }
                      ]
                    },
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
                                "condition": "_custom_script",
                                "tpl": "{{_behavior.response_json}}",
                                "oper": "!is",
                                "value": ""
                              }
                            ]
                          }
                        ]
                      },
                      "nodes": [
                        {
                          "type": "switch",
                          "title": "Type:",
                          "status": "live",
                          "nodes": [
                            {
                              "type": "outcome",
                              "title": "MX",
                              "status": "live",
                              "params": {
                                "groups": [
                                  {
                                    "any": 0,
                                    "conditions": [
                                      {
                                        "condition": "_custom_script",
                                        "tpl": "{{dns_mode}}",
                                        "oper": "is",
                                        "value": "MX"
                                      }
                                    ]
                                  }
                                ]
                              },
                              "nodes": [
                                {
                                  "type": "action",
                                  "title": "Respond",
                                  "status": "live",
                                  "params": {
                                    "actions": [
                                      {
                                        "action": "send_message",
                                        "message": "{% if _behavior.response_json is iterable %}\r\n{% set exchanges = _behavior.response_json|sort %}\r\n{% for value in exchanges %}\r\n* [{{value.priority}}] {{value.exchange}}\r\n{% endfor %}\r\n{% endif %}",
                                        "format": "markdown",
                                        "delay_ms": "500"
                                      }
                                    ]
                                  }
                                }
                              ]
                            },
                            {
                              "type": "outcome",
                              "title": "(Other)",
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
                                  "title": "Respond",
                                  "status": "live",
                                  "params": {
                                    "actions": [
                                      {
                                        "action": "send_message",
                                        "message": "{% if _behavior.response_json is iterable %}\r\n{% for value in _behavior.response_json %}\r\n* {{value}}\r\n{% endfor %}\r\n{% else %}\r\n{{_behavior.response_json}}\r\n{% endif %}",
                                        "format": "markdown",
                                        "delay_ms": "500"
                                      }
                                    ]
                                  }
                                }
                              ]
                            }
                          ]
                        }
                      ]
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
                      }
                    }
                  ]
                }
              ]
            }
          ]
        },
        {
          "uid": "behavior_178",
          "title": "Get interactions for worker",
          "is_disabled": false,
          "is_private": false,
          "priority": 50,
          "event": {
            "key": "event.interactions.get.worker",
            "label": "Conversation get interactions for worker",
            "params": {
              "listen_points": "global\r\n"
            }
          },
          "nodes": [
            {
              "type": "switch",
              "title": "Point:",
              "status": "live",
              "nodes": [
                {
                  "type": "outcome",
                  "title": "global",
                  "status": "live",
                  "params": {
                    "groups": [
                      {
                        "any": 0,
                        "conditions": [
                          {
                            "condition": "point",
                            "oper": "is",
                            "value": "global"
                          }
                        ]
                      }
                    ]
                  },
                  "nodes": [
                    {
                      "type": "action",
                      "title": "Return interactions",
                      "status": "live",
                      "params": {
                        "actions": [
                          {
                            "action": "return_interaction",
                            "behavior_id": "{{{uid.behavior_179}}}",
                            "name": "DNS lookup",
                            "interaction": "dns.lookup",
                            "interaction_params_json": ""
                          }
                        ]
                      }
                    }
                  ]
                }
              ]
            }
          ]
        },
        {
          "uid": "behavior_179",
          "title": "Handle interaction with worker",
          "is_disabled": false,
          "is_private": false,
          "priority": 50,
          "event": {
            "key": "event.interaction.chat.worker",
            "label": "Conversation handle interaction with worker"
          },
          "nodes": [
            {
              "type": "switch",
              "title": "Interaction:",
              "status": "live",
              "nodes": [
                {
                  "type": "outcome",
                  "title": "dns.lookup",
                  "status": "live",
                  "params": {
                    "groups": [
                      {
                        "any": 0,
                        "conditions": [
                          {
                            "condition": "interaction",
                            "oper": "is",
                            "value": "dns.lookup"
                          }
                        ]
                      }
                    ]
                  },
                  "nodes": [
                    {
                      "type": "action",
                      "title": "Run behavior",
                      "status": "live",
                      "params": {
                        "actions": [
                          {
                            "action": "switch_behavior",
                            "return": "0",
                            "behavior_id": "{{{uid.behavior_180}}}",
                            "var": "_behavior"
                          }
                        ]
                      }
                    }
                  ]
                }
              ]
            }
          ]
        },
        {
          "uid": "behavior_181",
          "title": "Invoke CerbDnsLookup Lambda function",
          "is_disabled": false,
          "is_private": true,
          "priority": 50,
          "event": {
            "key": "event.macro.bot",
            "label": "Custom behavior on bot"
          },
          "variables": {
            "var_mode": {
              "key": "var_mode",
              "label": "Mode",
              "type": "D",
              "is_private": "0",
              "params": {
                "options": "resolve\r\nreverse\r\nmx"
              }
            },
            "var_value": {
              "key": "var_value",
              "label": "Value",
              "type": "S",
              "is_private": "0",
              "params": {
                "widget": "single"
              }
            }
          },
          "nodes": [
            {
              "type": "action",
              "title": "Invoke CerbDnsLookup Lambda function",
              "status": "live",
              "params": {
                "actions": [
                  {
                    "action": "_set_custom_var",
                    "value": "{% set json = {} %}\r\n{% set json = dict_set(json, 'mode', var_mode) %}\r\n{% set json = dict_set(json, 'value', var_value) %}\r\n{{json|json_encode}}",
                    "format": "json",
                    "is_simulator_only": "0",
                    "var": "request_json"
                  },
                  {
                    "action": "core.va.action.http_request",
                    "http_verb": "post",
                    "http_url": "https://lambda.{{{aws_region}}}.amazonaws.com/2015-03-31/functions/CerbDnsLookup/invocations",
                    "http_headers": "Date: {{'now'|date('r', 'UTC')}}\r\nX-AMZ-Client-Context: {{\"{}\"|base64_encode}}\r\nX-AMZ-Date: {{'now'|date('Ymd\\\\THis\\\\Z', 'UTC')}}",
                    "http_body": "{{request_json|json_encode}}",
                    "auth": "connected_account",
                    "auth_connected_account_id": "{{{aws_account_id}}}",
                    "options": {
                      "raw_response_body": "1"
                    },
                    "run_in_simulator": "1",
                    "response_placeholder": "_http_response"
                  },
                  {
                    "action": "_set_custom_var",
                    "value": "{{_http_response.body|json_pretty}}",
                    "format": "json",
                    "is_simulator_only": "0",
                    "var": "response_json"
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

Cerb will prompt you to link your **AWS Account:** before creating the behavior. Click the chooser button and select **AWS (Cerb)**.

You will also be prompted to enter the AWS region where you created the Lambda function. You can find this at the beginning of the ARN for your function (e.g. `arn:aws:lambda:us-west-2:`).

 

Click the **Import** button again.

 

# Test the bot

Click on the floating bot interaction button in the bottom right of the browser. If you don't see the button (e.g. this is your first bot), refresh the page.

Select **AWS Lambda Bot&nbsp;» DNS lookup**.

 

The bot will greet you and offer a selection of DNS tools:

 

Select **Hostname** and type `cerb.ai`. You should be given a list of IPs:

 

You can also do reverse lookups. Select **IP** and type `8.8.8.8` (Google's DNS service):

 

Your new bot just:

1. Asked you for some input
2. Made a decision based on your intention
3. Authorized an API request to AWS Lambda using your connected account credentials
4. Invoked the Lambda function with some input and captured the output
5. Responded to you with formatted output

You can run any Lambda function by following this process. The process of adding subsequent functions is much simpler since you've already set up the AWS plugin, IAM policy, AWS user, and connected account in Cerb.

Any bot with access to an AWS connected account can run Lambda functions.

At this point, you can rename, extend, or disable AWS Lambda Bot according to your needs.

# Learn how the bot works

Even though the bot was automatically created for you, it's useful to understand how everything works so you can make changes and create your own behaviors later.

From **Search&nbsp;» Bots**, open the card for **AWS Lambda Bot**.

 

Click on the **Behaviors** button.

You'll see four behaviors:

 

- **Get interactions for worker** lets Cerb know that this bot provides a new global interaction.
- **Handle interaction with worker** starts a conversational behavior when a worker selects the interaction from the global menu.
- **DNS conversational behavior** handles the conversation flow with the worker.
- **Invoke CerbDnsLookup Lambda function** wraps the AWS Lambda API call so it can be reused by any behavior.

Conversational bot functionality is covered in detail [here](/packages/chat-bot/), so we won't dig into the first two behaviors in this guide.

Open the [card](/docs/records/#cards) for **DNS conversational behavior**:

 

This behavior is marked _private_, so it's only accessible by this bot.

When a conversation starts, the bot greets the worker and offers them a menu of choices using a button prompt. This menu is inside of an infinite loop so that a worker can take multiple actions in a single interaction.

The bot reacts to worker's button click (e.g. Hostname, IP, MX, Bye) in the **Action:** decision. Each action is handled by a separate _outcome_.

Within those outcomes, the bot asks follow up questions and then executes the **Run behavior** action with that input:

 

This is pretty simple. The bot just runs the **Invoke CerbDnsLookup Lambda function** private behavior and passes it the **Mode:** and **Value:** arguments. The mode is determined by the outcome (resolve, reverse, mx), and the value is the last message from the worker (a hostname or IP). We're saving the behavior's final state in the `_behavior` placeholder.

In the interest of keeping this example simple we're not doing a lot of error checking on the input, but the Lambda function does return useful errors to the bot that are displayed to the worker.

Now let's look at the **Invoke CerbDnsLookup** behavior, which does the actual work to talk to the AWS Lambda service:

 

This _private_ behavior is on the **Custom behavior on bot** event, which means that it only runs when it's manually triggered by a bot (as opposed to automatically running in response to events, like new email deliveries).

It has two _public_ behavior variables that serve as inputs: **Mode** and **Value**. These are set by the conversational behavior based on the worker's responses.

The behavior only has a single **Invoke CerbDnsLookup Lambda function** action set:

 

In the **Set custom placeholder** action, we build a JSON[4](#fn:json) payload for Lambda and save it as the `request_json` placeholder. We use the `JSON` format to save the placeholder as an object rather than text (i.e. we parse the JSON). This is a really simple object with `mode` and `value` as keys, and the behavior variables as values.

In **Execute HTTP Request**, we create a `POST` request to the AWS Lambda API.

- The **URL:** includes the AWS region and function name.
- In **Request headers:** we set three headers. `Date:` and `X-AMZ-Date:` are used during authorization to combat replay attacks[5](#fn:replay-attack). The `X-AMZ-Client-Context:` header is optional and we're just setting it to a blank object in Base64[6](#fn:base64) format.
- In **Request body:** we output the `request_json` placeholder in JSON format.
- In **Authentication:** our AWS connected account is selected to securely authenticate the request.
- Under **Options:** we prevent the bot from attempting to automatically parse the response by `Content-Type:`.
- We enable **Allow execute HTTP request in simulator mode** to simplify testing. You can click the **Simulator** button at the bottom of this popup to run the behavior directly without a conversation taking place.
- Finally, we save the HTTP response data in the `_http_response` placeholder.

In the last **Set custom placeholder** action we format the Lambda response and save it to the `response_json` placeholder. This is what the calling behavior uses to respond to the worker.

That's it! You can copy this behavior to implement other Lambda functions as reusable bot behaviors in Cerb.

# References

1. Amazon Web Services: Lambda - https://aws.amazon.com/lambda/&nbsp;[↩](#fnref:aws-lambda)

2. Amazon Web Services: What are Containers? - https://aws.amazon.com/containers/&nbsp;[↩](#fnref:aws-containers)

3. Wikipedia: Domain Name System (DNS) - https://en.wikipedia.org/wiki/Domain\_Name\_System&nbsp;[↩](#fnref:dns)

4. Wikipedia: JavaScript Object Notation (JSON) - https://en.wikipedia.org/wiki/JSON&nbsp;[↩](#fnref:json)

5. Wikipedia: Replay attack - https://en.wikipedia.org/wiki/Replay\_attack&nbsp;[↩](#fnref:replay-attack)

6. Wikipedia: Base64 - https://en.wikipedia.org/wiki/Base64&nbsp;[↩](#fnref:base64)

