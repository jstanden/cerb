---
id: "guides-developers-ngrok"
title: "Share secure access to a local development environment with ngrok"
url: "https://cerb.ai/guides/developers/ngrok/"
summary: "This page provides a comprehensive guide on using ngrok to share secure access to a local development environment. It covers the installation process for ngrok on various operating systems, including Mac, Windows, Linux, and FreeBSD, and provides instructions on starting ngrok to create a temporary public URL. The guide explains how to test ngrok by accessing local applications like Cerb through the generated URLs and highlights the benefits of using ngrok for testing webhooks. It also details how to monitor ngrok connections using its web interface, which aids in debugging by allowing inspection and replaying of requests. The page concludes with information on ngrok's free and paid plans, outlining the limitations and benefits of each. References to related tools like BrowserStack and Homebrew are also included."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Install ngrok](#install-ngrok)
- [Start ngrok](#start-ngrok)
- [Test ngrok](#test-ngrok)
- [Monitor ngrok connections](#monitor-ngrok-connections)
- [Next steps](#next-steps)
- [References](#references)

# Introduction

We recommend building [automations](/docs/automations/) and [webhooks](/guides/webhooks/configure/) in a [local development environment](/docs/installation/docker/) before deploying them to production. This allows you to test without risking unexpected changes to your live data.

Your development machine may be behind a firewall or assigned a dynamic IP address. This makes it difficult to test functionality like webhooks where a remote server needs to directly access a URL on your machine. It also makes it difficult to use other testing tools like BrowserStack[1](#fn:browserstack) that run against a publicly accessible URL.

You can solve these issues with **ngrok** [2](#fn:ngrok) – one of our favorite development tools. You can use ngrok to quickly and easily create a temporary public URL for sharing secure access to your local machine.

# Install ngrok

To get started, you'll need to download ngrok from their website and unzip it. It runs on Mac, Windows, Linux, and FreeBSD.

On Linux, you can usually install ngrok from your package manager (e.g. apt, yum).

On Mac, you can also install ngrok using Homebrew[3](#fn:homebrew) (note: you will need to install via `brew install --cask ngrok`).

# Start ngrok

Starting ngrok is incredibly simple:

```
ngrok http localhost:80
```

Above, we're starting ngrok in `http` mode using `localhost` on port `80`. You can replace these values according to your needs.

When ngrok starts, it generates an HTTP and HTTPS URL for you to use. This means you don't need to hassle with configuring SSL on your local development machine.

You can copy these links from the console and use them like any other public URL.

 

# Test ngrok

Paste your ngrok HTTPS URL into your web browser. If you have a copy of Cerb running on `localhost:80` then you (and anyone else) will be able to use it as if it was installed on a public web server.

If you're testing webhooks, you can copy the ngrok version of the webhook URL from **Setup&nbsp;» Services&nbsp;» Webhooks** after logging in. You can then paste this URL into any app or service that supports webhooks.

# Monitor ngrok connections

ngrok also has a web interface that starts on `http://127.0.0.1:4040` by default.

This allows you to inspect incoming requests and outgoing responses, which is incredibly helpful when you're debugging functionality like webhooks.

You also have the option of _replaying_ a previous request from this web interface, which makes your job a lot easier when developing webhooks. You won't have to waste your time constantly triggering an event and waiting for the webhook request to show up.

 

# Next steps

ngrok is free to use, with some limitations. You're limited on the number of connections per minute to your machine, as well as the number of tunnels and processes. On free plans, your tunnels will also always use a random hostname.

They offer paid plans to increase these limits, as well as enabling the use of reserved domains and IPs, and providing priority support.

# References

1. BrowserStack: Live web-based browser testing - https://www.browserstack.com&nbsp;[↩](#fnref:browserstack)

2. ngrok: Secure tunnels to localhost - https://ngrok.com&nbsp;[↩](#fnref:ngrok)

3. HomeBrew: The missing package manager for macOS - https://brew.sh&nbsp;[↩](#fnref:homebrew)

