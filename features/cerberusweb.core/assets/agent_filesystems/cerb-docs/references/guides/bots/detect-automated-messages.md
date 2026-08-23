---
id: "guides-bots-detect-automated-messages"
title: "Detect automated messages"
url: "https://cerb.ai/guides/bots/detect-automated-messages/"
summary: "This page provides a comprehensive guide on detecting automated messages in Cerb to prevent unnecessary auto-replies and potential mail loops. It explains the importance of identifying automated responses, such as 'Out of Office' messages, and outlines how to import and implement a reusable bot behavior to check for common headers indicating an auto-reply. The guide includes detailed instructions on importing the behavior, understanding its decision tree, and integrating it with other bots to ensure they do not respond to automated messages. Additionally, it references RFC-3834 for best practices in handling automatic email responses."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Importing the behavior](#importing-the-behavior)
- [Understanding how the behavior works](#understanding-how-the-behavior-works)
- [Using the behavior from another bot](#using-the-behavior-from-another-bot)
- [References](#references)

# Introduction

One of the most common bot behaviors in Cerb is [sending an automatic reply](/guides/bots/send-automatic-replies/) back to the sender of a new message. This is a good practice, because it confirms your receipt of a client's message, and it lets them know what to expect in terms of your support hours, SLA obligations, etc.

However, on occasion, a new message that you just received is itself an automatic reply from somewhere else. You should avoid sending an automatic reply back to it. At best, it's pointless and won't be read by a human. At worst, two misbehaving bots can endlessly send auto-replies back and forth to each other, creating a _mail loop_.

RFC-3834[1](#fn:rfc-3834) recommends that all automated responses contain a header like `Auto-Submitted: auto-replied` so they can be easily identified, and you should include that header in any automated messages that your bots send.

You can also check the `Auto-Submitted:` header on incoming mail to identify automated messages, but different mail senders also indicate this in other ways.

In this example, we'll create a reusable bot behavior that can check the most common headers on a message to see if it was sent automatically. If so, we can skip sending our own automatic response to it. For completeness, we'll also add checks for subjects that mention terms like _"Out of office"_.

If you're sending automatic replies from various bots (e.g. per group), then they can all share this detection behavior, and you can manage the rules in a single place.

This example should catch most automatic responses, but you can continue to expand on it to meet your own needs. Feel free to share your improvements in the comments at the bottom of the page.

In Cerb 11.0+ you can use the built-in [Auto Responder workflow](/workflows/cerb.auto_responder/) instead.

# Importing the behavior

You can quickly import the example behavior to get started.

From **Search&nbsp;» Bots**, create a new bot or choose an existing one. The bot should be owned by Cerb and able to create new behaviors on the **Custom message behavior** event.

Open the bot's card and click on the **Behaviors** button.

Click the **(+)** icon above the worklist to add a new behavior.

 

Select the **Import** option and paste the following behavior:

```
{
	"behavior": {
		"title": "Check if a message is an auto-reply",
		"is_disabled": false,
		"is_private": false,
		"priority": 1,
		"event": {
			"key": "event.macro.message",
			"label": "Custom message behavior"
		},
		"nodes": [
			{
				"type": "switch",
				"title": "Does the message have auto-reply characteristics?",
				"status": "live",
				"nodes": [
					{
						"type": "outcome",
						"title": "Yes, it's a bounce",
						"status": "live",
						"params": {
							"groups": [
								{
									"any": 1,
									"conditions": [
										{
											"condition": "sender_address",
											"oper": "like",
											"value": "postmaster@*"
										},
										{
											"condition": "sender_address",
											"oper": "like",
											"value": "mailer-daemon@*"
										}
									]
								}
							]
						},
						"nodes": [
							{
								"type": "action",
								"title": "true()",
								"status": "live",
								"params": {
									"actions": [
										{
											"action": "_run_subroutine",
											"subroutine": "true()"
										}
									]
								}
							}
						]
					},
					{
						"type": "outcome",
						"title": "Yes, it has an Auto-Submitted header",
						"status": "live",
						"params": {
							"groups": [
								{
									"any": 0,
									"conditions": [
										{
											"condition": "headers",
											"header": "Auto-Submitted",
											"oper": "!is",
											"value": ""
										},
										{
											"condition": "headers",
											"header": "Auto-Submitted",
											"oper": "!is",
											"value": "no"
										}
									]
								}
							]
						},
						"nodes": [
							{
								"type": "action",
								"title": "true()",
								"status": "live",
								"params": {
									"actions": [
										{
											"action": "_run_subroutine",
											"subroutine": "true()"
										}
									]
								}
							}
						]
					},
					{
						"type": "outcome",
						"title": "Yes, it has Preference/Precedence headers",
						"status": "live",
						"params": {
							"groups": [
								{
									"any": 1,
									"conditions": [
										{
											"condition": "headers",
											"header": "Preference",
											"oper": "is",
											"value": "auto_reply"
										},
										{
											"condition": "headers",
											"header": "Precedence",
											"oper": "is",
											"value": "bulk"
										}
									]
								}
							]
						},
						"nodes": [
							{
								"type": "action",
								"title": "true()",
								"status": "live",
								"params": {
									"actions": [
										{
											"action": "_run_subroutine",
											"subroutine": "true()"
										}
									]
								}
							}
						]
					},
					{
						"type": "outcome",
						"title": "Yes, it has X-Precedence, X-Autorespond, X-Autogenerated, or X-AutoReply-From headers",
						"status": "live",
						"params": {
							"groups": [
								{
									"any": 1,
									"conditions": [
										{
											"condition": "headers",
											"header": "X-Autogenerated",
											"oper": "!is",
											"value": ""
										},
										{
											"condition": "headers",
											"header": "X-AutoReply",
											"oper": "!is",
											"value": ""
										},
										{
											"condition": "headers",
											"header": "X-AutoReply-From",
											"oper": "!is",
											"value": ""
										},
										{
											"condition": "headers",
											"header": "X-Autorespond",
											"oper": "!is",
											"value": ""
										},
										{
											"condition": "headers",
											"header": "X-Precedence",
											"oper": "is",
											"value": "auto_reply"
										}
									]
								}
							]
						},
						"nodes": [
							{
								"type": "action",
								"title": "true()",
								"status": "live",
								"params": {
									"actions": [
										{
											"action": "_run_subroutine",
											"subroutine": "true()"
										}
									]
								}
							}
						]
					},
					{
						"type": "outcome",
						"title": "Yes, it has an X-Auto-Response-Suppress header",
						"status": "live",
						"params": {
							"groups": [
								{
									"any": 1,
									"conditions": [
										{
											"condition": "headers",
											"header": "X-Auto-Response-Suppress",
											"oper": "!is",
											"value": ""
										},
										{
											"condition": "headers",
											"header": "X-Auto-Response-Suppress",
											"oper": "contains",
											"value": "AutoReply"
										}
									]
								}
							]
						},
						"nodes": [
							{
								"type": "action",
								"title": "true()",
								"status": "live",
								"params": {
									"actions": [
										{
											"action": "_run_subroutine",
											"subroutine": "true()"
										}
									]
								}
							}
						]
					},
					{
						"type": "outcome",
						"title": "Yes, it has an Out of Office subject",
						"status": "live",
						"params": {
							"groups": [
								{
									"any": 1,
									"conditions": [
										{
											"condition": "headers",
											"header": "Subject",
											"oper": "contains",
											"value": "Out of Office"
										},
										{
											"condition": "headers",
											"header": "Subject",
											"oper": "contains",
											"value": "is out of the office"
										}
									]
								}
							]
						},
						"nodes": [
							{
								"type": "action",
								"title": "true()",
								"status": "live",
								"params": {
									"actions": [
										{
											"action": "_run_subroutine",
											"subroutine": "true()"
										}
									]
								}
							}
						]
					},
					{
						"type": "outcome",
						"title": "Yes, it has an Auto Response subject",
						"status": "live",
						"params": {
							"groups": [
								{
									"any": 1,
									"conditions": [
										{
											"condition": "headers",
											"header": "Subject",
											"oper": "contains",
											"value": "Auto Response"
										},
										{
											"condition": "headers",
											"header": "Subject",
											"oper": "contains",
											"value": "AutoReply"
										}
									]
								}
							]
						},
						"nodes": [
							{
								"type": "action",
								"title": "true()",
								"status": "live",
								"params": {
									"actions": [
										{
											"action": "_run_subroutine",
											"subroutine": "true()"
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
								"title": "false()",
								"status": "live",
								"params": {
									"actions": [
										{
											"action": "_run_subroutine",
											"subroutine": "false()"
										}
									]
								}
							}
						]
					}
				]
			},
			{
				"type": "subroutine",
				"title": "true()",
				"status": "live",
				"nodes": [
					{
						"type": "action",
						"title": "Exit: The message is an auto-reply",
						"status": "live",
						"params": {
							"actions": [
								{
									"action": "_set_custom_var",
									"value": "true",
									"format": "",
									"is_simulator_only": "0",
									"var": "result"
								},
								{
									"action": "_exit",
									"mode": ""
								}
							]
						}
					}
				]
			},
			{
				"type": "subroutine",
				"title": "false()",
				"status": "live",
				"nodes": [
					{
						"type": "action",
						"title": "Exit: The message is not an auto-reply",
						"status": "live",
						"params": {
							"actions": [
								{
									"action": "_set_custom_var",
									"value": "false",
									"format": "",
									"is_simulator_only": "0",
									"var": "result"
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
	}
}
```

Click the **Save Changes** button.

# Understanding how the behavior works

If you open the new behavior's [card](/docs/cards/), you'll see the following decision tree:

 

A decision determines whether or not the message has auto-reply characteristics.

The **"Yes"** outcomes check the most common headers that are used to indicate that a message was automatically sent. If any of these outcomes match then the behavior runs the `true()` subroutine and exits early.

If none of the positive outcomes match, the **"No"** outcome is selected as a default, and the `false()` subroutine runs.

You can add new outcomes to detect automated messages in another way. For instance, there are two additional checks that look at the subject line for situations like _"Out of office"_ messages.

Both of these subroutines set a `result` placeholder to either `true` or `false`. This is what we'll check when we use the behavior from somewhere else.

# Using the behavior from another bot

Now that you have the behavior and you understand how it works, let's use it from a bot that sends auto-replies so it can ignore responding to automated messages.

From **Search&nbsp;» Bots**, find an auto-reply bot and open its card.

Click the **Behaviors** button and select a behavior that sends auto-replies.

Somewhere in that behavior you should add a decision to ignore automated messages. The best place for that will depend on your behavior, but the general process is as follows.

Add a **Behavior run** action to check the current **Message**:

 

Then add a decision that checks the result of that behavior, with **Yes** and **No** outcomes:

 

There are many ways you can react from these outcomes. One easy option is just exiting the behavior early if you detect an automatic message.

That's it! You can repeat these last few steps on your other auto-reply bots.

# References

1. IETF: RFC-3834: Automatic Email Responses - https://tools.ietf.org/html/rfc3834&nbsp;[↩](#fnref:rfc-3834)

