---
id: "docs-automations-commands-encrypt-pgp"
title: "Automations: encrypt.pgp"
url: "https://cerb.ai/docs/automations/commands/encrypt.pgp/"
summary: "This page provides detailed information on the 'encrypt.pgp' automation command in Cerb, which is used to encrypt a block of text using PGP public keys. It outlines the syntax and structure of the command, including the necessary inputs such as the message to be encrypted and the public keys required for encryption. The page also explains the output handling, and the procedures for simulation, success, and error states. It specifies how to manage the encrypted message output and error messages, ensuring users can effectively implement and troubleshoot the encryption process within their automations."
tags: ["docs", "docs-automations"]
---
The **encrypt.pgp:** command encrypts a block of text using one or more PGP public keys.

```
start:
  encrypt.pgp:
    output: encrypted_message
    inputs:
      message@text:
        This is a secret message.
      public_keys:
        uri: cerb:gpg_public_key:D399DA5BA62A0472
    on_success:
      return:
        encrypted_message@key: encrypted_message
```

- [Syntax](#syntax)
  - [inputs:](#inputs)
  - [output:](#output)
  - [on\_simulate:](#on_simulate)
  - [on\_success:](#on_success)
  - [on\_error:](#on_error)

# Syntax

## inputs:

| Key | &nbsp; |
| --- | --- |
| `message@text:` | The message to encrypt as a text block. |
| `public_keys:uri:` | This contains one or more child recipient public keys in the format `uri/<nickname>: cerb:gpg_public_key:<id>`, where `<id>` is a record ID, fingerprint, or fingerprint-16. |
| `public_keys:ids:` | Alternatively, an array of public key IDs can be provided. |

## output:

Save the results in this placeholder.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of encrypting the message.

If omitted, the message is encrypted during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The `output:` placeholder is set to the encrypted message.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

