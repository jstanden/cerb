---
id: "docs-api-authentication"
title: "API: Authentication"
url: "https://cerb.ai/docs/api/authentication/"
summary: "This page provides detailed information on how Cerb uses the OAuth 2.0 standard for authenticating API requests, replacing the older request signature method used in versions prior to 9.1. It outlines the process for creating an OAuth app within Cerb, which includes generating API credentials, defining scopes, and understanding the roles of Client ID and Client Secret. The page also explains how to authenticate API requests using the Authorization Code grant type, detailing the necessary steps and information required, such as the Authorization URL, Access Token URL, and the inclusion of access tokens in HTTP headers. The document emphasizes the importance of scopes in defining the privileges associated with access tokens and provides examples of default scopes available in Cerb."
tags: ["docs"]
---
Cerb uses the **OAuth 2.0** [1](#fn:oauth2) industry standard for authenticating API requests.

The legacy [API authentication with request signatures](/docs/api/authentication/request-signatures/) will continue to be supported for the foreseeable future, but XOAuth tokens should be used when possible.

# Creating an OAuth App

To generate API credentials, you first need to create an OAuth app in Cerb. You must be an administrator to create an app, but the credentials may then be used by third-parties they are shared with.

To create an app:

- Navigate to **Search&nbsp;» OAuth Apps**.

- Click the **(+)** icon above the worklist to create a new app.

 

Enter the following details:

- **Name:** (the name of your application or integration)
- **Callback URL:** (aka **Redirect URL**; the endpoint URL in your application that receives OAuth2 responses)
- **Website:** (the optional URL of your application's website)

### Scopes

When an application connects to an OAuth2 server it requests a set of **scopes**. These are the privileges that are associated with the access token that is returned. Scopes allow an application to request the minimal set of privileges necessary to fulfill its duties.

When creating an OAuth App you can define the scopes that are available.

The available scopes are defined in YAML:

```
"profile":
 label: Access your profile information
 endpoints:
  - workers/me: GET

"search":
 label: Search records on your behalf
 endpoints:
  - records/*/search: [GET]

"api:read-only":
 label: Make any read-only API request on your behalf
 endpoints:
  - "*": [GET]

"api":
 label: Make any API request on your behalf
 endpoints:
  - "*" #[GET, PATCH, POST, PUT, DELETE]
```

Each scope is identified by a name.

The `label:` field describes the scope when a worker is granting your application access to their account.

The `endpoints:` field is a list of API endpoints and HTTP methods that the scope provides. Wildcards are specified using an asterisk (`*`).

The default scopes document specifies four scopes:

| Scope | Description |
| --- | --- |
| `profile` | Access your profile information |
| `search` | Search records on your behalf |
| `api:read-only` | Make any read-only API request on your behalf |
| `api` | Make any API request on your behalf |

For instance, an application that uses the API for reporting could use the default `api:read-only` scope which prohibits the creation and modification of data.

### Client ID and Secret

When you create an OAuth app, a pair of secure credentials is automatically generated for you.

- The **Client ID** identifies a particular client that connects to the API using your new application. You can think of this like a username.

- As the name suggests, the **Client Secret** must be kept private within your application. You should not commit it to a public code repository. It is used to verify that an application has permission to generate access tokens.

Click the **Save Changes** button to create your app.

# Authenticating API requests with OAuth2

Cerb currently only supports the **Authorization Code** grant type in the OAuth2 authentication flow.

You will need the following information to generate an access token:

- The **Client ID** and **Client Secret** for the app you created above.

- The **Authorization URL**: `https://YOUR-CERB-HOST/oauth/authorize`

- The **Access Token URL**: `https://YOUR-CERB-HOST/oauth/access_token`

- A **Callback URL** (also called a **Redirect URL**) which must match the one specified in your OAuth app above.

- One or more **Scopes** separated by a space.

Once you receive an access token, you need to include it in the HTTP headers of API requests:

```
Authorization: Bearer <YOUR-ACCESS-TOKEN>
```

# References

1. OAuth 2.0 - https://oauth.net/2/&nbsp;[↩](#fnref:oauth2)

