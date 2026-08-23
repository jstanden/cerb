---
id: "solutions-integrations-gitlab"
title: "GitLab"
url: "https://cerb.ai/solutions/integrations/gitlab/"
summary: "This webpage provides a comprehensive guide on integrating Cerb with GitLab, focusing on authentication methods and automation usage. It details two authentication methods: using a personal access token and OAuth2. The personal access token method involves creating a token in GitLab and setting up a connected service and account in Cerb. The OAuth2 method requires creating an OAuth application in GitLab, setting up a GitLab service in Cerb, and linking the connected account. The guide also explains how to use the connected GitLab account in Cerb automations, providing step-by-step instructions and examples for setting up and testing these integrations."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [GitLab Authentication](#gitlab-authentication)
  - [Method 1: Personal access token](#method-1-personal-access-token)
    - [Create a personal access token at GitLab](#create-a-personal-access-token-at-gitlab)
    - [Create a connected service in Cerb](#create-a-connected-service-in-cerb)
    - [Create a connected account in Cerb](#create-a-connected-account-in-cerb)

  - [Method 2: OAuth2](#method-2-oauth2)
    - [Create an OAuth application at GitLab](#create-an-oauth-application-at-gitlab)
    - [Create the GitLab service in Cerb](#create-the-gitlab-service-in-cerb)
    - [Link the connected account to GitLab in Cerb](#link-the-connected-account-to-gitlab-in-cerb)

- [Use the connected account in Cerb automations](#use-the-connected-account-in-cerb-automations)
  - [Search issues](#search-issues)
  - [Create issues](#create-issues)

- [Use the connected account in Cerb workflows](#use-the-connected-account-in-cerb-workflows)

# Introduction

In this guide we'll walk through the process of linking Cerb to GitLab. You'll be able to use GitLab's full API from automations in Cerb to automate whatever you need.

# GitLab Authentication

There are two alternative authentication methods when using the GitLab API.

- **Personal Access Tokens:** A long-lived secret token for a specific GitLab user. Rotating the token must be done manually. This is the simplest option.

- **OAuth2:** A short-lived access token and long-lived refresh token. This allows multiple GitLab users to link their account to Cerb. It requires a more involved OAuth application setup, but is the most secure option since secret rotation is automatic and indefinite.

## Method 1: Personal access token

### Create a personal access token at GitLab

To create a personal access token, log into GitLab.

Click on your profile image in the top of the left sidebar.

Select **Preferences** from the menu.

Select **Access tokens** in the left sidebar.

In the **Personal access tokens** section, click the **Add new token** button in the top right.

The options for **Select scopes** depend on your needs. For instance: `read_api` and `read_repository`.

### Create a connected service in Cerb

In Cerb, navigate to **Search&nbsp;» Connected Services&nbsp;» (+)** and select the **Build** tab at the top.

| Field | &nbsp; |
| --- | --- |
| **Name:** | `GitLab (Access Token)` |
| **Type:** | Token Bearer |
| **Token Name:** | `Bearer` |

Click the **Save Changes** button.

### Create a connected account in Cerb

In Cerb, navigate to **Search&nbsp;» Connected Accounts&nbsp;» (+)**.

Select **GitLab (Access Token)**.

Choose any name (e.g. `GitLab (example-user)`) and URI (e.g. `gitlab-example-user`).

In **Token:** paste the personal access token you generated at GitLab in the first section above.

Click the **Save Changes** button.

[Test](#use-the-connected-account-in-automations) the connected account in automations.

## Method 2: OAuth2

### Create an OAuth application at GitLab

Log into GitLab. We recommend creating a 'Cerb' user account for automations.

Click on your profile picture in the top left and select **Preferences**.

Select **Applications** in the left sidebar.

Click the **Add new application** button in the top right.

| Field | &nbsp; |
| --- | --- |
| **Name:** | `Cerb` |
| **Redirect URI:** | `https://<YOUR-CERB-URL>/oauth/callback` |
| **Confidential:** | (checked) |
| **Scopes:** | `api`, `read_user` |

Click the blue **Save application** button.

Copy the **Application ID** and **Secret** for the next step.

### Create the GitLab service in Cerb

In Cerb, navigate to **Search&nbsp;» Connected Services&nbsp;» (+)** and select **GitLab** from the list.

Paste your **Application ID** and **Secret**.

Click the **Create** button.

### Link the connected account to GitLab in Cerb

Navigate to **Search&nbsp;» Connected Accounts&nbsp;» (+)** and select **GitLab**.

| Field | &nbsp; |
| --- | --- |
| **Name:** | `GitLab` |
| **URI:** | `gitlab` |
| **Owner:** | (Cerb) |

It's a good idea to add a suffix to the **Name** and **URI** if you plan to link multiple GitLab user accounts.

Click the blue **Link to GitLab** button.

Review the consent form and then click **Authorize Cerb**.

Click the **Save Changes** button.

# Use the connected account in Cerb automations

## Search issues

Create an [automation.function](/docs/automations/triggers/automation.function/) automation:

```
inputs:
  text/repo:
    type: freeform
    required@bool: yes
  text/query:
    type: freeform
    required@bool: yes

start:
  http.request/search:
    output: http_response
    inputs:
      method: GET
      url: https://gitlab.com/api/v4/projects/{{inputs.repo|url_encode}}/search?scope=issues&search={{inputs.query|url_encode}}
      authentication: cerb:connected_account:gitlab
    on_success:
      return:
        search_results@json: {{http_response.body}}
    on_error:
```

From the **Inputs:** section in the lower left of the automation editor, simulate with:

```
inputs:
  repo: cerb.ai/example-project
  query: tempore
```

## Create issues

Create an [automation.function](/docs/automations/triggers/automation.function/) automation:

```
inputs:
  text/repo:
    type: freeform
    required@bool: yes
  text/title:
    type: freeform
    required@bool: yes
  text/description:
    type: freeform
    required@bool: yes

start:
  http.request/search:
    output: http_response
    inputs:
      method: POST
      url: https://gitlab.com/api/v4/projects/{{inputs.repo|url_encode}}/issues?title={{inputs.title|url_encode}}&description={{inputs.description|url_encode}}
      authentication: cerb:connected_account:gltlab
    on_success:
      return:
        search_results@json: {{http_response.body}}
    on_error:
```

From the **Inputs:** section in the lower left of the automation editor, simulate with:

```
inputs:
  repo: cerb.ai/example-project
  title: test issue
  description: this is a test issue
```

# Use the connected account in Cerb workflows

Once created, you can use the connected account with the prebuilt [GitLab Issues workflow](/workflows/cerb.integrations.gitlab.issues/) to search and link GitLab issues to tickets within Cerb.

