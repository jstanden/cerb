---
id: "solutions-integrations-gmail"
title: "Gmail"
url: "https://cerb.ai/solutions/integrations/gmail/"
summary: "This page provides a comprehensive guide on integrating Cerb with Gmail by configuring Google APIs and setting up Cerb. It details the steps to create a new project in Google Cloud, configure the OAuth consent screen, enable the Gmail API, and add necessary credentials. The guide then explains how to configure Cerb by creating a connected service and account, using the credentials obtained from Google. It concludes with next steps for authenticating a Gmail mailbox using IMAP and XOAUTH2, making it a useful resource for automating tasks with Google APIs through Cerb bots."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Configure Google APIs](#configure-google-apis)
  - [Create a new project](#create-a-new-project)
  - [Configure the consent screen](#configure-the-consent-screen)
  - [Enable Gmail API](#enable-gmail-api)
  - [Add credentials](#add-credentials)

- [Configure Cerb](#configure-cerb)
  - [Create the connected service](#create-the-connected-service)
  - [Create the connected account](#create-the-connected-account)

- [Examples](#examples)
  - [Search threads](#search-threads)
  - [Retrieve thread](#retrieve-thread)
  - [Batch retrieve threads](#batch-retrieve-threads)

- [Next steps](#next-steps)

# Introduction

In this guide we'll walk through the process of linking Cerb to Gmail. You can use the same process with any Google API from Cerb bots to automate whatever you need.

# Configure Google APIs

### Create a new project

Log in to: https://console.cloud.google.com/apis/ as a workspace user.

Click **Create Project** in the top right.

Enter:

| Project Name: | `Cerb` |
| Organization: | (your organization) |
| Location: | (your organization) |

Click the blue **Create** button.

Click **Select Project** in the notification in the top right.

### Configure the consent screen

Select **OAuth consent screen** in the left sidebar.

Enter:

| User Type: | Internal (only available in workspaces) |

Click the blue **Create** button.

Enter:

| Application name: | Cerb |
| Scopes for Google APIs: | email, profile, openid |
| Authorized domains: | _(your Cerb base URL; e.g. `cerb.me`)_ |

Click the blue **Save** button.

### Enable Gmail API

Click **Library** in the left sidebar.

Search for `Gmail` and select **Gmail API**.

Click the blue **Enable** button at the top of the page.

### Add credentials

Click **Create Credentials** in top right.

Enter:

| Select an API: | Gmail API |
| What data will you be accessing: | User data |

Click the **Next** button.

Click the **Add or Remove Scopes** button.

Select `https://mail.google.com/` (Gmail API) from the list.

Select `https://www.googleapis.com/auth/userinfo.profile` from the list.

Click the **Update** button.

Click the **Save and Continue** button.

In **Application type** select **Web application**.

Enter:

| Name: | Cerb |
| Authorized redirect URIs: | `https://YOUR-CERB-HOST/oauth/callback` |

Click the blue **Create** button.

Click the **Download** button.

Click the **Done** button.

# Configure Cerb

### Create the connected service

Navigate to **Search&nbsp;» Connected Services**.

Click **(+)** button in the right of the gray bar above the worklist.

In the **Library** tab, select the **Google** package.

Paste your **Client ID** and **Client Secret** from the credentials you downloaded earlier.

Scope:

```
https://mail.google.com/ https://www.googleapis.com/auth/userinfo.profile
```

Click the **Create** button.

### Create the connected account

Navigate to **Search&nbsp;» Connected Accounts**.

Click **(+)** button in the right of the gray bar above the worklist.

Select **Google**.

Enter:

| Name: | `Gmail (you@example.com)` |

Click the blue **Link to Google** button.

Log in with your Google account.

Click **Allow**.

Click the **Save Changes** button.

# Examples

## Search threads

https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.threads/list

```
start:
  set:
    http_params:
      q: from:team@cerb.ai
      maxResults@int: 100
  http.request/search:
    output: http_response
    inputs:
      method: GET
      url: https://gmail.googleapis.com/gmail/v1/users/me/threads?{{http_params|url_encode}}
      authentication: cerb:connected_account:gmail
```

## Retrieve thread

https://developers.google.com/workspace/gmail/api/reference/rest/v1/users.threads/get

```
start:
  set:
    thread_id: a1b2c3d4e5f6
    http_params:
      format: metadata
      metadataHeaders: Subject
  http.request/getThread:
    output: http_response
    inputs:
      method: GET
      url: https://gmail.googleapis.com/gmail/v1/users/me/threads/{{thread_id}}?{{http_params|url_encode}}
      authentication: cerb:connected_account:gmail
```

## Batch retrieve threads

https://developers.google.com/workspace/gmail/api/guides/batch

```
start:
  set:
    mime_boundary: batch_{{random_string(6)}}
    mime_body@text:
      --{{mime_boundary}}
      Content-Type: application/http
      Content-ID: <a1b2c3d4e5f6>
      
      GET /gmail/v1/users/me/threads/a1b2c3d4e5f6?format=metadata&metadataHeaders=Subject HTTP/1.1
      
      --{{mime_boundary}}
      Content-Type: application/http
      Content-ID: <b2c3d4e5f6a1>
      
      GET /gmail/v1/users/me/threads/b2c3d4e5f6a1?format=metadata&metadataHeaders=Subject HTTP/1.1
      
      --{{mime_boundary}}
      Content-Type: application/http
      Content-ID: <c3d4e5f6a1b2>
      
      GET /gmail/v1/users/me/threads/c3d4e5f6a1b2?format=metadata&metadataHeaders=Subject HTTP/1.1
      
      --{{mime_boundary}}--
      
  http.request/getThreads:
    output: http_response
    inputs:
      method: POST
      url: https://www.googleapis.com/batch/gmail/v1
      authentication: cerb:connected_account:gmail-cerb-devel
      headers:
        Content-Type: multipart/mixed; boundary={{mime_boundary}}
        Content-Length@int: {{mime_body|length}}
      body: {{mime_body}}
    on_success:
      set:
        threads@json:
          {% set response_boundary = http_response.headers['content-type']|split("boundary=")|last %}
          {{http_response.body
            |split('--' ~ response_boundary)[1:-1]
            |map((v) => json_decode(v|trim|split("\r\n\r\n")|last))
            |json_encode
          }}
```

# Next steps

See: [Authenticate a Gmail mailbox using IMAP and XOAUTH2](/guides/integrations/google/gmail-xoauth/)

