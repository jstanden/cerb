---
id: "solutions-integrations-ipstack"
title: "ipstack"
url: "https://cerb.ai/solutions/integrations/ipstack/"
summary: "This page provides a guide on integrating Cerb with ipstack to utilize its API for geolocation purposes. It outlines the steps to sign up for an ipstack account, obtain a free API key, and create a connected account in Cerb to enable IP-based geolocation. The guide emphasizes the approximate nature of IP-derived locations due to factors like VPNs and proxies. Additionally, it includes related resources for further workflow automation using ipstack within Cerb."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Sign up at ipstack](#sign-up-at-ipstack)
- [Create the connected account in Cerb](#create-the-connected-account-in-cerb)
- [Examples](#examples)
  - [Simple IP lookup](#simple-ip-lookup)
  - [Bulk IP lookup](#bulk-ip-lookup)

- [Related Resources](#related-resources)

# Introduction

In this guide we'll walk through the process of linking Cerb to ipstack. You'll be able to use ipstack's API from automations to perform geolocation.

Let's assume we have a list of client IP addresses and want to plot them on a map to visualize where activity is coming from.

First, it's important to acknowledge that locations derived from IP addresses are approximate. Clients may be using VPNs, proxies, third party services, etc. The location may be an ISP, or the contact information for the registered owner of a block of IP addresses.

# Sign up at ipstack

Like geocoding, IP-based geolocation uses a large dataset. It wouldn't make sense to ship this with Cerb, so we'll be using an API from ipstack.

You can sign up for a free API key at ipstack and make 1,000 IP location requests per month at no cost.

Once you sign up, make a note of your API Access Key so you can use it within Cerb.

# Create the connected account in Cerb

In Cerb, navigate to **Search&nbsp;» Connected Services&nbsp;» (+)** and select IPstack from the **Library**.

Paste your API Access Key from above.

# Examples

## Simple IP lookup

```
start:
  http.request/geolocate:
    output: http_response
    inputs:
      method: GET
      url: http://api.ipstack.com/1.2.3.4
      authentication: cerb:connected_account:ipstack
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```

## Bulk IP lookup

_Not available on free or basic tier_

```
start:
  http.request/geolocate:
    output: http_response
    inputs:
      method: GET
      url: http://api.ipstack.com/1.2.3.4,5.4.3.2
      authentication: cerb:connected_account:ipstack
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```

# Related Resources

- Workflow: [Geolocate IPs with IPstack](/workflows/cerb.integrations.ipstack/)

