---
id: "docs-automations-commands-decrypt-pgp"
title: "Automations: decrypt.pgp"
url: "https://cerb.ai/docs/automations/commands/decrypt.pgp/"
summary: "This page provides detailed information on the 'decrypt.pgp' command used in Cerb automations to decrypt PGP-encrypted text blocks. It outlines the syntax and structure for using this command, including the necessary inputs, such as the PGP-encrypted message, and the expected output, which is the decrypted message. The page also explains the different states of the automation process, such as on_success, on_simulate, and on_error, detailing the actions to be taken in each scenario. The documentation is designed to guide users in effectively implementing the decryption process within their automation workflows."
tags: ["docs", "docs-automations"]
---
The **decrypt.pgp:** command decrypts a PGP-encrypted text block.

```
start:
  decrypt.pgp:
    output: decrypted_message
    inputs:
      message@text:
        -----BEGIN PGP MESSAGE-----
        
        wf8AAAIMA9OZ2lumKgRyARAAhtCjoVos8hiC0RejOfMnQX34Cm83dapwGoynTrc2yaCdyUwpI5M4
        AnLI3IVxYmdxatH31dj7W5/J7k/2gmr6JMEUy5jCIycD1b+FqPxD8dVJqCpyKPlN4/ot5Ke7k6pe
        48KptLECdh18w4N/IA+NZML+a5b1VXqg3KngI/Vbp8rIZycW2Vp571iKS+3RM2gp10l61yrKPPNJ
        QFohE0XKVLs7NxGPjFL7eSOORbDX73SfIsgR4UTnv/DMrZ4OuuS63qPB/epngMEdV+lJELdyHgzb
        3m4bNyJuy1zkTvAyMBME5O89FduvIXn7BcxthayxpTnH4uc4yU9E7cye8vk4XjZRdEkf1U8nlV6a
        tCrIgE5pj6lBhfXI66DPTo9SchvXG4vm7ZxEWfaggI0Pwf4bemS09x9RD/BruI9FcIImMXjuv+r4
        cxLQAAXe1GYSnuyh4wR6UhAiEfkX1tTOQaa0bIgE+R94d2vNjNjPtrcqt6b+ZxT8IC0p+WBOLN9M
        ng+A6l71TM9LYCY3R/H2jrd0jQrajTe8AXpnUdm4TQTh6sRuPUuj7FZoNW3eUdb9WyD/AXla9QXT
        49nAkiACXOZa1gt6nnM1CYWN9Z5uxBeD9h4xvSUbIiF1pEnyBMjjS0tt1iRxgrMa8lp8xv7yohHR
        CGVkG7EicuiSMcUdIUKsvO/S/wAAAFsBJKmA9SdyrNBKMI1VAi45jOdejbPjdz0+oglDWRZNVIlv
        58kfJ6jGONz0P3bomG8KI1rm7lRmKS7c+B8BJDVlbtHDW3ejBifla4rypgj7mPkQJaoFwzImusu4
        =6tzL
        -----END PGP MESSAGE-----      
    on_success:
      return:
        decrypted_message@key: decrypted_message
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
| `message@text:` | A PGP-encrypted message as a text block. This will attempt to match any private key records. |

## output:

Save the results in this placeholder.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of decrypting the message.

If omitted, the PGP message is decrypted during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The `output:` placeholder is set to the decrypted message.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

