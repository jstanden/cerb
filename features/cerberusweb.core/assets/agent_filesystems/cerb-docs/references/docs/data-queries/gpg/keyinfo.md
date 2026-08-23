---
id: "docs-data-queries-gpg-keyinfo"
title: "Data Queries: PGP Key Info"
url: "https://cerb.ai/docs/data-queries/gpg/keyinfo/"
summary: "This page provides information on how to use `gpg.keyinfo` data queries to retrieve details about a PGP Public Key. It outlines the necessary inputs, such as the public key fingerprint and optional filters for user IDs or subkeys. The response format is primarily in dictionaries, which is suitable for integration with spreadsheets and APIs. An example query and its corresponding response are provided, demonstrating the type of data returned, including key attributes like whether the key is disabled, expired, revoked, or secret, as well as its capabilities for signing and encrypting. The example also includes detailed information about user IDs and subkeys associated with the PGP key."
tags: ["docs"]
---
# gpg.keyinfo

`gpg.keyinfo` data queries return details about a PGP Public Key.

### Inputs

| `fingerprint:` | The public key fingerprint to look up |
| `filter:` | `uids`, `subkeys`, or omit for both |

### Response Formats

- **dictionaries** (default) returns a table-based format suitable for [sheets](/docs/sheets/) and API results.

### Examples

#### Query:

```
type:gpg.keyinfo
fingerprint:EB53CF5B6712E70F
format:dictionaries
```

#### Response:

```
{
  "data": {
    "disabled": false,
    "expired": false,
    "revoked": false,
    "is_secret": false,
    "can_sign": true,
    "can_encrypt": true,
    "uids": [
      {
        "name": "Webgroup Media LLC",
        "comment": null,
        "email": "support@webgroupmedia.com",
        "uid": "Webgroup Media LLC <support@webgroupmedia.com>",
        "revoked": false,
        "invalid": false
      }
    ],
    "subkeys": [
      {
        "fingerprint": "7DCA2B1D4FFE23739C46F49CEB53CF5B6712E70F",
        "keyid": "6712E70F",
        "timestamp": 1481832779,
        "expires": 0,
        "is_secret": false,
        "invalid": false,
        "can_encrypt": false,
        "can_sign": true,
        "disabled": false,
        "expired": false,
        "revoked": false
      },
      {
        "fingerprint": "28D08C9FE340C7C274329D768BAB1F9A7D9BBCCC",
        "keyid": "7D9BBCCC",
        "timestamp": 1481832779,
        "expires": 1734120779,
        "is_secret": false,
        "invalid": false,
        "can_encrypt": true,
        "can_sign": false,
        "disabled": false,
        "expired": false,
        "revoked": false
      },
      {
        "fingerprint": "94EC3D18B2C869B97540A15975332EBEBE387ABF",
        "keyid": "BE387ABF",
        "timestamp": 1481832779,
        "expires": 1734120779,
        "is_secret": false,
        "invalid": false,
        "can_encrypt": false,
        "can_sign": true,
        "disabled": false,
        "expired": false,
        "revoked": false
      }
    ]
  },
  "_": {
    "type": "gpg.keyinfo",
    "format": "dictionaries"
  }
}
```
