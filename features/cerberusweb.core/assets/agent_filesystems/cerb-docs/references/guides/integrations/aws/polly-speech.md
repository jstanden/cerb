---
id: "guides-integrations-aws-polly-speech"
title: "Give Cerb bots the power of speech with Amazon Polly"
url: "https://cerb.ai/guides/integrations/aws/polly-speech/"
summary: "This page provides a comprehensive guide on integrating Amazon Polly with Cerb to enable text-to-speech capabilities for bots. It details the process of creating a connected account with Amazon Polly, setting up a delegate bot named Polly Bot to manage credentials and provide text-to-speech services, and using this bot to generate audio streams from text. The guide includes steps for configuring Amazon Web Services, updating IAM policies, and creating and testing the Polly Bot within Cerb. It also explains how to use Polly Bot in conversational bots to respond with speech directly in a web browser, and offers insights into customizing and testing the behavior using a bot simulator. The page concludes with references to AWS documentation for further customization and understanding of Polly's capabilities."
tags: ["guides"]
---
# Introduction

In this guide you'll learn how to generate audio streams of speech from arbitrary text using bots in Cerb.

First, we'll create a new [connected account](/docs/connected-accounts/) to securely integrate with Amazon Polly – a fast, inexpensive, and lifelike text-to-speech service from Amazon Web Services.

Next, we'll create a delegate named _Polly Bot_ to secure your credentials and provide text-to-speech as a simple service for any other bot in Cerb. Your team's bots will be able to give Polly Bot some text and a preferred voice (gender/accent), and receive back a secure time-limited URL that you can share and play anywhere.

Finally, we'll demonstrate how to use Polly Bot as a delegate from a conversational bot to respond to [workers](/docs/workers/) with speech directly in their web browser.

- [Configure the Amazon Web Services service in Cerb](#configure-the-amazon-web-services-service-in-cerb)
- [Log in to Amazon Web Services](#log-in-to-amazon-web-services)
  - [Update your IAM policy](#update-your-iam-policy)

- [Create Polly Bot in Cerb](#create-polly-bot-in-cerb)
- [Using Polly Bot from a conversational bot](#using-polly-bot-from-a-conversational-bot)
  - [Learn how the behavior works](#learn-how-the-behavior-works)
  - [Test the behavior with the bot simulator](#test-the-behavior-with-the-bot-simulator)

- [References](#references)

# Configure the Amazon Web Services service in Cerb

1. Log into Cerb as an administrator.

2. Navigate to **Search&nbsp;» Connected Accounts**.

3. If you don't have a connected account for Amazon Web Services yet, you can [follow these instructions](/solutions/integrations/aws/) to create one.

# Log in to Amazon Web Services

First, we need to create a new user in your Amazon Web Services (AWS) account and attach a _policy_ that describes the services that they are allowed to use. This is accomplished with the Identity Access Management (IAM) service. This user will receive credentials that you can use to interact with AWS from Cerb.

Log in to the AWS Management Console and navigate to the IAM service.

If you don't have an AWS account, you can sign up for free at: https://aws.amazon.com

## Update your IAM policy

Navigate to IAM from the **Services** menu at the top of the page.

We need to update the _policy_ in your Amazon Web Services (AWS) account to describe the services that your Cerb bot is allowed to use. This is accomplished with the Identity Access Management (IAM) service.

We're going to update the IAM policy to provide:

- Read-only access to Amazon Polly

Select **Policies** in the navigation on the left.

Find your bot's policy in the list or create a new one. In the earlier [instructions](/solutions/integrations/aws/) we created a policy named **CerbBot**.

Click the **Edit Policy** button.

Select the **JSON** tab.

Add the following block to the `Statement` list:

```
{
  "Effect": "Allow",
  "Action": [
    "polly:DescribeVoices",
    "polly:GetLexicon",
    "polly:ListLexicons",
    "polly:SynthesizeSpeech"
  ],
  "Resource": [
    "*"
  ]
}
```

Click the blue **Review policy** button in the bottom right.

Click the blue **Save changes** button in the bottom right.

# Create Polly Bot in Cerb

Now we're ready to create the bot that interacts with AWS using our connected account.

Navigate to **Setup&nbsp;» Packages&nbsp;» Import**.

Copy and paste the following behavior into the large text box:

```
{
  "package": {
    "name": "Polly Bot",
    "revision": 1,
    "requires": {
      "cerb_version": "9.1.0"
    },
    "configure": {
      "placeholders": [

      ],
      "prompts": [
        {
          "type": "chooser",
          "label": "AWS Account:",
          "key": "prompt_aws_account_id",
          "params": {
            "context": "cerberusweb.contexts.connected_account",
            "query": "aws OR amazon",
            "single": true
          }
        },
        {
          "type": "text",
          "label": "AWS Region:",
          "key": "prompt_aws_region",
          "params": {
            "default": "us-west-2"
          }
        }
      ]
    }
  },
  "bots": [
    {
      "uid": "bot_polly",
      "name": "Polly Bot",
      "owner": {
        "context": "cerberusweb.contexts.app",
        "id": 0
      },
      "is_disabled": false,
      "params": {
        "events": {
          "mode": "all",
          "items": [

          ]
        },
        "actions": {
          "mode": "all",
          "items": [

          ]
        },
        "interactions": [

        ]
      },
      "image": "data:image/png;base64,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",
      "behaviors": [
        {
          "uid": "behavior_66",
          "title": "Get interactions",
          "is_disabled": false,
          "is_private": false,
          "priority": 50,
          "event": {
            "key": "event.interactions.get.worker",
            "label": "Conversation get interactions for worker",
            "params": {
              "listen_points": "global"
            }
          },
          "nodes": [
            {
              "type": "action",
              "title": "Return",
              "status": "live",
              "params": {
                "actions": [
                  {
                    "action": "return_interaction",
                    "behavior_id": "{{{uid.behavior_68}}}",
                    "name": "Say something",
                    "interaction": "say",
                    "interaction_params_json": "{}"
                  }
                ]
              }
            }
          ]
        },
        {
          "uid": "behavior_67",
          "title": "Get presigned speech URL",
          "is_disabled": false,
          "is_private": false,
          "priority": 1,
          "event": {
            "key": "event.macro.bot",
            "label": "Custom behavior on bot"
          },
          "variables": {
            "var_text": {
              "key": "var_text",
              "label": "Text",
              "type": "S",
              "is_private": "0",
              "params": {
                "widget": "single"
              }
            },
            "var_voice": {
              "key": "var_voice",
              "label": "Voice",
              "type": "D",
              "is_private": "0",
              "params": {
                "options": "Brian\r\nGeraint\r\nJoanna"
              }
            }
          },
          "nodes": [
            {
              "type": "action",
              "title": "Get presigned URL",
              "status": "live",
              "params": {
                "actions": [
                  {
                    "action": "wgm.aws.bot.action.get_presigned_url",
                    "http_verb": "get",
                    "http_url": "{% set query = {\t\"OutputFormat\": \"mp3\",\t\"VoiceId\": var_voice,\t\"TextType\": \"text\",\t\"Text\": var_text} %}https://polly.{{{prompt_aws_region}}}.amazonaws.com/v1/speech?{{query|url_encode}}",
                    "http_headers": "",
                    "http_body": "",
                    "expires_secs": "60",
                    "auth_connected_account_id": "{{{prompt_aws_account_id}}}",
                    "response_placeholder": "polly_speech_url"
                  }
                ]
              }
            }
          ]
        },
        {
          "uid": "behavior_68",
          "title": "Handle interactions",
          "is_disabled": false,
          "is_private": false,
          "priority": 50,
          "event": {
            "key": "event.interaction.chat.worker",
            "label": "Conversation handle interaction with worker"
          },
          "nodes": [
            {
              "type": "action",
              "title": "Set bot name",
              "status": "live",
              "params": {
                "actions": [
                  {
                    "action": "set_bot_name",
                    "name": "Polly"
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
                    "action": "switch_behavior",
                    "return": "0",
                    "behavior_id": "{{{uid.behavior_69}}}",
                    "var": "_behavior"
                  }
                ]
              }
            }
          ]
        },
        {
          "uid": "behavior_69",
          "title": "Repeat after me",
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
              "title": "What would you like me to say?",
              "status": "live",
              "params": {
                "actions": [
                  {
                    "action": "send_message",
                    "message": "What would you like me to say?",
                    "format": "",
                    "delay_ms": "1000"
                  },
                  {
                    "action": "prompt_text",
                    "placeholder": ""
                  }
                ]
              }
            },
            {
              "type": "action",
              "title": "Get pre-signed URL",
              "status": "live",
              "params": {
                "actions": [
                  {
                    "action": "_run_behavior",
                    "on": "_trigger_va_id",
                    "behavior_id": "{{{uid.behavior_67}}}",
                    "var_text": "{{message}}",
                    "var_voice": "Brian",
                    "run_in_simulator": "0",
                    "var": "_behavior"
                  }
                ]
              }
            },
            {
              "type": "action",
              "title": "Speak",
              "status": "live",
              "params": {
                "actions": [
                  {
                    "action": "send_script",
                    "script": "<script>\r\nDevblocks.playAudioUrl('{{_behavior.polly_speech_url}}');\r\n</script>"
                  }
                ]
              }
            },
            {
              "type": "action",
              "title": "Again?",
              "status": "live",
              "params": {
                "actions": [
                  {
                    "action": "send_message",
                    "message": "Say something else?",
                    "format": "",
                    "delay_ms": "2000"
                  },
                  {
                    "action": "prompt_buttons",
                    "options": "yes\r\nno",
                    "color_from": "#ffffff",
                    "color_mid": "#ffffff",
                    "color_to": "#ffffff",
                    "style": ""
                  }
                ]
              }
            },
            {
              "type": "switch",
              "title": "Yes/No",
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
                            "condition": "message",
                            "oper": "is",
                            "value": "yes"
                          }
                        ]
                      }
                    ]
                  },
                  "nodes": [
                    {
                      "type": "action",
                      "title": "Repeat",
                      "status": "live",
                      "params": {
                        "actions": [
                          {
                            "action": "switch_behavior",
                            "return": "0",
                            "behavior_id": "{{{uid.behavior_69}}}",
                            "var": "_behavior"
                          }
                        ]
                      }
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
                        "conditions": [

                        ]
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

# Using Polly Bot from a conversational bot

We've included an example of a speech-enabled conversational behavior.

1. Start a chat by clicking on the bot icon in the lower right of your browser:

2. Select **Polly Bot** in the menu.

3. Click the **Say something** option in the menu.

4. Type anything you would like the bot to say and press the `<ENTER>` key.

The bot will speak what you typed.

You can use this example behavior to add speech to your other conversations.

## Learn how the behavior works

Even though the behavior was automatically created for you, it's useful to understand how everything works so you can make changes and create your own behaviors later.

1. Navigate to **Search&nbsp;» Bots** and open the card for **Polly Bot**.

2. Click on the **Behaviors** button.

3. Open the card for the **Get presigned speech URL** behavior.

On the behavior's card you'll see its _decision tree_ at the bottom.

Click the **Custom bot behavior** _node_ and select **Edit Behavior** from the menu:

 

You should see:

 

Here we've defined two _public behavior variables_ named **Text** and **Voice**. These variables will be provided by other bots when they want Polly Bot to generate speech and return a pre-signed URL to the audio stream.

You can close the edit popup without saving it (click the **(x)** icon in the top right of the popup).

So that's the behavior itself. Let's look at the action node, where the magic actually happens.

Click on the **Get presigned URL** node in the decision tree. You should see:

 

This action uses your **AWS: Cerb (Polly)** connected account to _pre-sign_ a `POST` request to the Amazon Polly API. In the **Request body:** we're sending a JSON payload that asks for an MP3 audio stream for the text defined in `var_text` using the voice defined in `var_voice`. We've also set the pre-signed URL to expire in `60` seconds.

Most importantly, the URL for the audio stream will be saved in a new `polly_speech_url` placeholder.

That's it! Polly Bot is rather straightforward, but it will save you a lot of work when you need to add speech to several other bots. All of your interaction with AWS Polly is in a single place.

## Test the behavior with the bot simulator

We've clicked around a lot. By now you're probably eager to actually hear a bot speak.

While you still have the action editor popup open, click the **Simulator** button in the bottom toolbar.

Enter some text that you want the bot to say. You can optionally choose the voice to use.

We've included a few of our favorite voices, but you can use any of the voices or languages supported by Polly[1](#fn:polly-voices) by [editing the behavior](#understanding-how-the-behavior-works) and adding them to the public behavior variables. You can even use the SSML[2](#fn:ssml) format instead for fine-tuning pronunciation, pitch curves, etc.

Click the **Simulate** button.

You won't hear speech yet, but in the log at the bottom of the simulator you should should see the audio stream URL under `>>> Saving pre-signed URL to {{polly_speech_url}}:`

 

Copy the (very long) URL to your clipboard and paste it into the location bar in a new browser window or tab. You should hear the text you typed being spoken in the selected voice.

For example, we received this audio stream for the example above:

Your browser does not support HTML5 audio.

You can do anything with the audio stream URL at this point. For instance:

- A bot could send the URL to a web browser and play it.
- A bot could download it as a new attachment record.
- You could use it in an interactive voice response system during a phone call (e.g. Twilio, Asterisk).

In a conversational bot behavior, you can use the **Respond with script** action to speak the URL:

```
<script>
Devblocks.playAudioUrl('{{_behavior.polly_speech_url}}');
</script>
```

# References

1. AWS Developer Guide: Polly Voices http://docs.aws.amazon.com/polly/latest/dg/API\_Voice.html&nbsp;[↩](#fnref:polly-voices)

2. AWS: Using SSML - http://docs.aws.amazon.com/polly/latest/dg/ssml.html&nbsp;[↩](#fnref:ssml)

