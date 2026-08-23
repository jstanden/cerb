---
id: "docs-api-authentication-request-signatures"
title: "API: Authentication with Request Signatures (Deprecated)"
url: "https://cerb.ai/docs/api/authentication/request-signatures/"
summary: "This page discusses the deprecated method of authenticating API requests in Cerb using request signatures, which involved an access key and a secret key. It explains the process of creating and using these keys, including how the secret key is used to cryptographically sign requests without being directly transmitted. The page provides guidance on creating an API key-pair, using provided libraries for automatic request signing, and manually signing requests in custom scripts. It emphasizes the importance of securely storing secret keys and suggests using OAuth2 for authentication instead. An example is provided to illustrate the process of generating a signature for authentication. The page also lists supported API libraries and references related to MD5 and replay attacks."
tags: ["docs"]
---
In earlier versions, API requests were authenticated using credentials comprised of an access key and a secret key.

We strongly recommend that you use [OAuth2 authentication](/docs/api/authentication/) instead.

You can think of the access key as a username. A single worker may have multiple access keys with varying permissions for each application or service that uses the API, and each of them would have their own secret key.

Similarly, the secret key is like a password. However, unlike a traditional password, the secret key isn't directly transmitted back to the server for verification. Instead, the secret key is combined with public details about the HTTP request (verb, path, date, query string, payload) to cryptographically _sign_ it. Since the server also knows the secret key for each access key, it can create its own signature using the same combination of secret key and HTTP request details. If the signatures match then the request is authenticated.

# Creating an API key-pair

First, [enable the Web API plugin](/guides/api/configure-plugin/) and create a key-pair.

# Authenticating with the provided libraries

The process of signing a request is automatically handled by the [libraries](#api-libraries) for PHP, Perl, Python, Apex, and Node.js.

# Authenticating from custom scripts

To sign an API request manually, create an MD5[1](#fn:md5) signature of the following string:

```
verb\n
http_date\n
url_path\n
url_query_string\n
payload\n
secret\n
```

- **verb**

- **http\_date**

- **url\_path**

- **url\_query\_string**

- **payload**

- **secret**

The generated signature should be sent with the request as a header in the following format:

```
Cerb-Auth: <access_key>:<signature>
```

If you're having trouble authenticating and you're sure that the signature is correct, verify that the current time is accurate on both the client and server.

All of these security considerations are moot if your secret key isn't stored securely. Make sure that custom scripts aren't world-readable on the server. You should always give API credentials the least amount of privileges required to perform the desired actions.

## Example

Let's look at an example signature for testing your own authentication implementation.

For this request:

```
POST /rest/tickets/search.json?show_meta=0 HTTP/1.1
Date: Wed, 08 Feb 2017 19:53:35 GMT
Content-Type: application/x-www-form-urlencoded; charset=utf-8
Host: cerb.example
Connection: close
Content-Length: 27

expand=custom_&q=status%3Ao
```

Using these credentials:

- Access key: `pjlfmn339fgh`
- Secret key: `fw4y9fjjd5tqjlsk3u9zkjjr154xbftc`

The authentication header is comprised of `<access-key>:<signature>`:

```
Cerb-Auth: pjlfmn339fgh:0cfe2f3b06552c060c8e77f7a0c875ee
```

In PHP, the signature is generated as follows:

```
$secret_hash = md5('fw4y9fjjd5tqjlsk3u9zkjjr154xbftc');

$string_to_sign = <<< EOF
POST
Wed, 08 Feb 2017 19:53:35 GMT
/rest/tickets/search.json
show_meta=0
expand=custom_&q=status%3Ao
$secret_hash

EOF;

echo md5($string_to_sign);
```

This outputs:

```
0cfe2f3b06552c060c8e77f7a0c875ee
```

# API Libraries

- [PHP](/docs/api/libraries/php/)
- [Python](/docs/api/libraries/python/)
- [Perl](/docs/api/libraries/perl/)
- [Node.js](/docs/api/libraries/nodejs/)
- [Apex](/docs/api/libraries/apex/)
- [Zapier](/docs/api/libraries/zapier/)

# References

1. Wikipedia: MD5 - http://en.wikipedia.org/wiki/MD5&nbsp;[↩](#fnref:md5)

2. Wikipedia: Replay Attack - http://en.wikipedia.org/wiki/Replay\_attack&nbsp;[↩](#fnref:replay-attack)

