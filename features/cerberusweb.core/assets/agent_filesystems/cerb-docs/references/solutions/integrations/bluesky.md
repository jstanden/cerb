---
id: "solutions-integrations-bluesky"
title: "Bluesky"
url: "https://cerb.ai/solutions/integrations/bluesky/"
summary: "This page provides a step-by-step guide for integrating Cerb and Bluesky, allowing users to use Bluesky's full API in Cerb automations."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Create a Bluesky App Password.](#create-a-bluesky-app-password)
- [Create the Bluesky service in Cerb](#create-the-bluesky-service-in-cerb)
- [Examples](#examples)
  - [Get timeline](#get-timeline)
  - [Make a post](#make-a-post)

# Introduction

In this guide we'll walk through the process of linking Cerb to Bluesky. You'll be able to use Bluesky's full API in Cerb automations.

# Create a Bluesky App Password.

Log in to your Bluesky Account or sign up if you don't already have one.

Choose **Settings** in the menu and then click the **Privacy and Security&nbsp;» App Passwords**.

Click **App App Password**

Name the password (eg. `cerb`) and click **Next**.

Copy the password for use later.

# Create the Bluesky service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Bluesky**.

4. Enter the following information: 
  1. Your PDS Entryway URL (eg. `https://bsky.social`).
  2. Your Identifier (eg. `example.bsky.social`).
  3. The app password you copied earlier.

5. Click the **Create** button.

# Examples

## Get timeline

https://docs.bsky.app/docs/api/app-bsky-feed-get-timeline

```
start:
  http.request/getTimeline:
    output: http_response
    inputs:
      method: GET
      url: https://bsky.social/xrpc/app.bsky.feed.getTimeline
      authentication: cerb:connected_account:bluesky
    on_success:
      set:
        response@json: {{http_response.body}}
```

## Make a post

https://docs.bsky.app/docs/advanced-guides/posts

```
start:
  http.request/post:
    output: http_response
    inputs:
      method: POST
      url: https://bsky.social/xrpc/com.atproto.repo.createRecord
      headers:
        Content-Type: application/json
      authentication: cerb:connected_account:bluesky
      body:
        repo: example.bsky.social
        collection: app.bsky.feed.post
        record:
          text: Testing, testing, 1,2,3!
          createdAt: {{'now'|date('c')}}
```
