---
id: "docs-automations-commands-http-request"
title: "Automations: http.request"
url: "https://cerb.ai/docs/automations/commands/http.request/"
summary: "This page provides a detailed guide on using the `http.request` command in Cerb automations to send data to HTTP endpoints and handle responses. It explains how to perform both simple and complex HTTP requests, including `GET` and `POST` methods, and how to manage headers, body content, and authentication. The page also covers handling binary and large responses, streaming large file uploads directly from attachment records, and downloading into attachment records. It includes syntax details for various inputs like method, URL, headers, body, timeout, and authentication, as well as output handling and error management. Examples demonstrate practical applications, such as streaming large uploads from attachments, ensuring efficient data handling without memory limitations."
tags: ["docs", "docs-automations"]
---
The **http.request:** command sends data to an HTTP endpoint and returns the response.

The command also supports streaming large file uploads directly from attachment records, and downloads directly into attachment records.

A simple `GET` request:

```
start:
  http.request/get:
    output: http_response
    inputs:
      url: https://api.example/employee/123
    on_success:
      return:
        body@key: http_response:body
```

A more complex `POST` request:

```
start:
  http.request/post:
    output: http_response
    inputs:
      method: POST
      url: https://api.example/employee/add
      headers@text:
        Content-Type: application/json
      body:
        person:
          name: Kina
          title: Customer Support Manager
    on_simulate:
      set:
        http_response:
          status_code@int: 200
          content_type: application/json
          body@text:
            { "status": true, "id": 123 }
  set:
    body@key,json: http_response:body
  return:
    employee_id@int: {{body.id}}
```

- [Syntax](#syntax)
  - [inputs:](#inputs)
    - [method:](#method)
    - [url:](#url)
    - [headers:](#headers)
    - [body:](#body)
      - [Binary request bodies](#binary-request-bodies)

    - [timeout:](#timeout)
    - [authentication:](#authentication)
    - [response:](#response)

  - [output:](#output)
    - [Binary responses](#binary-responses)
    - [Large responses](#large-responses)

  - [on\_simulate:](#on_simulate)
  - [on\_success:](#on_success)
  - [on\_error:](#on_error)

- [Redirects](#redirects)
- [Examples](#examples)
  - [Stream a large upload from an attachment](#stream-a-large-upload-from-an-attachment)

https://www.youtube.com/embed/3yn_WWPzAoU

# Syntax

## inputs:

### method:

The HTTP method to use for the request.

| Method | Body |
| --- | --- |
| `DELETE` | &nbsp; |
| `GET` | &nbsp; |
| `HEAD` | &nbsp; |
| `OPTIONS` | &nbsp; |
| `PATCH` | **x** |
| `POST` | **x** |
| `PUT` | **x** |

```
method: POST
```

### url:

The URL of the HTTP endpoint to use for the request.

```
url: https://api.example/employee/123
```

### headers:

A set of HTTP headers to include with the request.

Headers should be described as a set of `name: value` pairs.

```
headers:
  Content-Type: application/json
  X-Requester: Cerb
```

The headers can optionally also be defined as a `@text` block.

```
headers@text:
  Content-Type: application/json
  X-Requester: Cerb
```

### body:

The body of the HTTP request (if applicable).

```
body@text:
  This is the body content
  on multiple indented lines.
```

If the body is defined as a dictionary of `key: value` pairs, then it will automatically be encoded based on the `Content-Type:` header:

- JSON (`application/json`)
- YAML (`application/x-yaml`/`text/yaml`)
- URL-encoded (`application/x-www-form-urlencoded`); or if the `Content-Type:` is omitted

This removes the need for extraneous `set:` commands to prepare the HTTP request.

```
headers:
  Content-Type: application/json
body:
  person:
    name: Kina Halpue
    title: Customer Service Manager
```

#### Binary request bodies

To send binary content, add a `@base64` [annotation](/docs/kata/#annotation-reference) to the `body:` key. The value is decoded before the request is sent.

```
headers:
  Content-Type: image/png
body@base64: {{image_data}}
```

When the base64 is written directly in the script as an indented block, combine it with `@text` so the block is read as text. The order of the two annotations doesn't matter.

```
headers:
  Content-Type: image/png
body@text,base64:
  iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlE
  QVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==
```

Always set an explicit `Content-Type:` header for a binary body. A string body with no content type is sent as `application/x-www-form-urlencoded`.

The `body:` is limited to 16MB. For anything larger, stream it from a record instead – see [Stream a large upload from an attachment](#stream-a-large-upload-from-an-attachment).

### timeout:

The optional timeout in seconds. Decimal values are allowed (e.g. `0.5` for 500ms).

```
timeout: 0.5
```

This is the timeout for the request as a whole – connecting, sending, and reading the response. When omitted it's 25 seconds. Establishing the connection has its own separate 10 second limit.

A `timeout:` of `0` doesn't disable the timeout. It's ignored, and the 25 second default applies.

Raising this above 25 seconds usually means raising the automation's [time limit](/docs/automations/#time-limit) to match:

```
settings:
  time_limit_ms: 60000
```

The automation's time limit is checked between commands rather than during one, so it never interrupts a request that's already in flight. A request that runs longer than the time limit finishes, and then the automation exits with an `Execution timed out` error – discarding the response it just waited for.

### authentication:

The optional URI of a [connected account](/docs/records/types/connected_account/) to use for authenticating this HTTP request.

The account's [service provider](/docs/plugins/extensions/points/cerb.connected_service.provider/) knows how its API expects to be authenticated, and it modifies the outgoing request for you. You don't build the credential yourself, and the secret never appears in the automation.

```
authentication: cerb:connected_account:my-oauth2-account
```

These service providers authenticate an outgoing request:

| Service Provider | What it adds to the request |
| --- | --- |
| [Amazon Web Services](/docs/plugins/extensions/cerb.service.provider.aws/) | A SigV4 signature in `Authorization:`, plus `X-Amz-Date:` (and `x-amz-content-sha256:` for S3) |
| API Key | The key, in a header named by the service or appended to the query string |
| AT Protocol | `Authorization: Bearer` with a session token, created and refreshed as needed |
| [Cerb API (Legacy Signatures)](/docs/plugins/extensions/cerb.service.provider.cerb.api.legacy/) | A signature in `Cerb-Auth:`, and a `Date:` header if one isn't set |
| [Facebook Pages](/docs/plugins/extensions/wgm.facebook.pages.service.provider/) | `Authorization: Bearer` with a page access token |
| [HTTP Basic Authentication](/docs/plugins/extensions/cerb.service.provider.http.basic/) | HTTP Basic credentials |
| [OAuth1 Provider](/docs/plugins/extensions/cerb.service.provider.oauth1/) | An OAuth1 signature in `Authorization:` |
| [OAuth2 Provider](/docs/plugins/extensions/cerb.service.provider.oauth2/) | `Authorization: Bearer` with the access token |
| Telegram Bot | The bot token in the URL path rather than a header |
| [Token Bearer](/docs/plugins/extensions/cerb.service.provider.token.bearer/) | `Authorization:` with a configurable scheme and token |

The **HTTP Basic Authentication** and **API Key** providers only sign requests to the hosts configured on their [connected service](/docs/records/types/connected_service/). A request to any other host fails to authenticate and runs the [on\_error:](#on_error) event rather than sending an unauthenticated request.

The [LDAP](/docs/plugins/extensions/cerb.service.provider.ldap/), [OpenID Connect](/docs/plugins/extensions/cerb.service.provider.oidc/), and [SAML](/docs/plugins/extensions/cerb.service.provider.saml.idp/) providers sign workers in to Cerb. They don't authenticate outgoing requests. Using one here succeeds without adding any credential to the request.

Don't set your own `Authorization:` header when using `authentication:`. The service provider runs after your headers are applied and replaces it.

### response:

When `response:resource:` is set, the response is always returned as an [automation resource](/docs/records/types/automation_resource/) record, regardless of its size.

The key only needs to exist. This is the whole minimal form:

```
inputs:
  response:
    resource:
```

| Key | Type | Notes |
| --- | --- | --- |
| `resource:` | dictionary | Its presence forces a resource response. May be empty. |
| `resource:expires:` | date | When the automation resource record expires. Defaults to 15 minutes from now. |

The `expires:` value needs a `@date` [annotation](/docs/kata/#annotation-reference) to be read as a date. Relative values are allowed:

```
response:
  resource:
    expires@date: 1 hour
```

Without the annotation, or with a value that can't be read as a date, the expiration silently becomes `0` and the record is already expired when it's created.

## output:

Save the results in this placeholder.

The `output:` key is **required**.

### Binary responses

A binary HTTP response body is automatically converted to a base64-encoded `data:` URI, and `output:is_data_uri:` is `true`.

This keeps the automation state serializable when the response contains unprintable characters (e.g. during simulation).

The body is a complete `data:` URI, not bare base64 – `data:image/png;base64,iVBORw0KGgo...` – so a `@base64` annotation alone won't decode it. Strip the prefix first:

```
set:
  bytes: {{http_response.body|split(',')|last|base64_decode}}
```

You should always use the `http.request:on_success:` handler to verify an HTTP response before reading its body.

### Large responses

A large HTTP response body (\>1MB) will now be returned as an [automation resource](/docs/records/types/automation_resource/) record for further processing.

These bytes are streamed directly to a file to avoid memory limitations in the automation (e.g. video processing).

When this occurs:

- `output:is_cerb_uri:` is `true`
- `output:content_type:` is replaced with `application/vnd.cerb.uri`
- `output:content_type_original:` contains the original content type
- The HTTP body is a Cerb record URI (e.g. `cerb:automation_resource:c10028f0-1cad-11ec-81e5-59d4c4af2d7`)

The new [file.read:](/docs/automations/commands/file.read/) command can be used to process the file in chunks.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of the HTTP request.

If omitted, the HTTP request is executed during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run when the server answered.

**Any** HTTP status code is a success, including `404` and `503`. A server that returns an error still answered the request, so the response arrives here with its status code and body intact, and the automation decides what to do with it. Test the status code yourself:

```
on_success:
  outcome/ok:
    if@bool: {{200 == http_response.status_code}}
    then:
      # ...
```

The `output:` placeholder receives a dictionary with these keys:

| Key | Notes |
| --- | --- |
| `status_code` | The HTTP status code (e.g. `200`) |
| `url` | The URL of the HTTP endpoint. |
| `content_type` | The content type of the HTTP response (e.g. `application/json`). |
| `headers` | A dictionary of headers from the HTTP response. Keys are lowercase, dashes are preserved (e.g. `content-type`). |
| `body` | The body of the HTTP response. |
| `is_data_uri` | `true` when the body was converted to a `data:` URI. See [Binary responses](#binary-responses). |
| `is_cerb_uri` | `true` when the body is an automation resource URI. See [Large responses](#large-responses). |
| `content_type_original` | The original content type, when `content_type` was replaced. See [Large responses](#large-responses). |

## on\_error:

The [commands](/docs/automations/#commands) to run when the request never completed. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

This event means Cerb couldn't get an answer – not that the server said no. It runs when:

- The connection failed: DNS failure, connection refused, TLS failure, a timeout, or too many [redirects](#redirects).
- The request was never sent: the automation's [policy](/docs/automations/#policies) denied it, an input failed validation, or a `cerb:` URI in the body couldn't be loaded.

The `output:` placeholder receives a dictionary with these keys:

| Key | Notes |
| --- | --- |
| `error` | The error message. Always present. |
| `url` | The URL of the HTTP endpoint. Always present. |
| `status_code` | The HTTP status code. |
| `content_type` | The content type of the HTTP response. |
| `headers` | A dictionary of headers from the HTTP response. |
| `body` | The body of the HTTP response. |

Only `error` and `url` are always set. The response keys are included when the failure carried a response, which for most failures it doesn't – a refused connection has no status code to report.

# Redirects

Redirects are followed automatically, up to five of them, for `http` and `https` only. Exceeding that limit runs the [on\_error:](#on_error) event.

The automation's [policy](/docs/automations/#policies) is evaluated once, before the request is sent, so it only sees the URL your script wrote. A permitted host that redirects to a denied one is still followed. Where that matters, verify `output:url:` – it holds the URL that actually answered.

The `Authorization:` and `Cookie:` headers are dropped when a redirect crosses to a different origin, so credentials added by a [connected account](#authentication) aren't leaked to the new host. The exceptions are the two providers that don't put the credential in a header: **API Key** in its query-string form, and **Telegram Bot**, which puts the token in the URL path.

# Examples

## Stream a large upload from an attachment

The `http.request:` action can directly stream large attachment/resource uploads for PUT and POST HTTP requests.

Set the `Content-Type:` header to `application/vnd.cerb.uri` and set the HTTP body to a record URI like `cerb:attachment:123`.

The automation will take care of streaming the bytes to the HTTP endpoint, which avoids memory issues with loading large attachment content into an automation variable.

```
start:
  http.request/post:
    output: http_response
    inputs:
      method: POST
      url: https://api.example/file/upload
      headers@text:
        Content-Type: application/vnd.cerb.uri
      body@text:
        cerb:attachment:123
```
