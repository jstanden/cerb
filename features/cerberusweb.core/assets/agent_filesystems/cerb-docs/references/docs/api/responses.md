---
id: "docs-api-responses"
title: "API: Responses"
url: "https://cerb.ai/docs/api/responses/"
summary: "This page provides detailed information on how the Cerb Web API responds to requests with JSON or XML encoded objects, focusing on the structure and functionality of single-level dictionaries of key/value pairs. It explains how to expand keys in API requests to efficiently retrieve related records without multiple HTTP requests, thereby reducing overhead. The page also discusses the use of meta data in API responses, which includes labels and data types for available keys, aiding in understanding and utilizing the data effectively. The `show_meta` option is highlighted as a way to access this meta data, which is particularly useful for deciphering custom fields."
tags: ["docs"]
---
The Web API answers requests with one or more **objects** encoded in JSON or XML. Each response is a single-level **dictionary** of **key**/**value** pairs.

- [Dictionaries](#dictionaries)
  - [Expanding keys in API requests](#expanding-keys-in-api-requests)

- [Meta data](#meta-data)

# Dictionaries

Suppose we send this `GET` request to the API to retrieve ticket #123 in JSON format:

```
GET /rest/records/ticket/123.json
```

The response looks like:

```
{
    __build: 2017013001,
    __status: "success",
    __version: "9.0.6",
    _context: "cerberusweb.contexts.ticket",
    _label: "[#JCA-16346-351] Do you offer volume discounts?",
    bucket__context: "cerberusweb.contexts.bucket",
    bucket_id: 6,
    closed_at: 0,
    created: 1360990928,
    elapsed_resolution_first: "0",
    elapsed_response_first: "0",
    group__context: "cerberusweb.contexts.group",
    group_id: 6,
    id: 123,
    initial_message__context: "cerberusweb.contexts.message",
    initial_message_id: 1195,
    initial_message_sender__context: "cerberusweb.contexts.address",
    initial_message_sender_org__context: "cerberusweb.contexts.org",
    initial_message_worker__context: "cerberusweb.contexts.worker",
    initial_message_worker_address__context: "cerberusweb.contexts.address",
    initial_message_worker_address_org__context: "cerberusweb.contexts.org",
    initial_response_message__context: "cerberusweb.contexts.message",
    initial_response_message_id: 0,
    initial_response_message_sender__context: "cerberusweb.contexts.address",
    initial_response_message_sender_org__context: "cerberusweb.contexts.org",
    initial_response_message_worker__context: "cerberusweb.contexts.worker",
    initial_response_message_worker_address__context: "cerberusweb.contexts.address",
    initial_response_message_worker_address_org__context: "cerberusweb.contexts.org",
    latest_message__context: "cerberusweb.contexts.message",
    latest_message_id: 1195,
    latest_message_sender__context: "cerberusweb.contexts.address",
    latest_message_sender_org__context: "cerberusweb.contexts.org",
    latest_message_worker__context: "cerberusweb.contexts.worker",
    latest_message_worker_address__context: "cerberusweb.contexts.address",
    latest_message_worker_address_org__context: "cerberusweb.contexts.org",
    mask: "JCA-16346-351",
    num_messages: "1",
    org__context: "cerberusweb.contexts.org",
    org_id: 51,
    owner__context: "cerberusweb.contexts.worker",
    owner_address__context: "cerberusweb.contexts.address",
    owner_id: 0,
    reopen_date: 0,
    spam_score: 0.0001,
    spam_training: "",
    status: "open",
    subject: "Do you offer volume discounts?",
    updated: 1360990928,
    url: "https://cerb.example/profiles/ticket/JCA-16346-351"
}
```

You can read more about how [dictionaries](/docs/guide/developers/dictionaries/) work in the documentation for bots.

## Expanding keys in API requests

In the above example, you'll notice that some related records weren't loaded by default:

```
group__context: "cerberusweb.contexts.group",
    group_id: 6,
```

When you're using a dictionary in [automations](/docs/automations/) you can continue to expand keys as needed.

However, with the API, the `GET` request would have already returned the above JSON from the server, so accessing undefined keys from the dictionary would just return `null` values.

Without an introduction to **key expansion**, many programmers assume that the `__context` and `_id` keys are used for subsequent API requests to load additional information. That would work, but it would be very inefficient to issue a dozen HTTP requests just to construct the full dictionary of a single ticket. When performing a search that returns 100 tickets at a time, all those incremental round-trips to load additional data would create a lot of unnecessary overhead.

Instead, you can request that multiple keys be expanded when you make the original request.

Let's take another look at our original `GET` request:

```
GET /rest/tickets/123.json
```

If we wanted the `group_name` and `latest_message_sender_org_name` keys to be available in the response, we'd add the following option to the request:

```
GET /rest/tickets/123.json?expand=group_name,latest_message_sender_org_name
```

Remember, this would also load any other related key/value pairs from the group and organization records. In fact, the following shortcut also works:

```
GET /rest/tickets/123.json?expand=group_,latest_message_sender_org_
```

In the above request, we're requesting the expansion of the keys for the ticket's group and the latest message sender's organization by specifying only their key prefix. This will also load the latest message and the latest message's sender, because they're both hierarchal parents of the organization's record.

# Meta data

Now that you understand how key expansion works, you may be wondering how to figure out what keys are available for a given request.

By default, most responses include special **meta data** keys, which provide a list of the labels and data types for all of the available keys in the dictionary. You'll find these in the `_labels` and `_types` keys of a typical single dictionary response, and in the `results_meta` section of a multiple-dictionary response like search results.

The `show_meta` option in a request URL determines whether this information is provided or not. The option is disabled by default to save bandwidth and CPU cycles for subsequent requests. You can enable it by setting it to `1`.

The labels meta data is also useful for understanding what is stored in a particular key. In most cases the key names are intuitive (e.g. `ticket_latest_message_sender_first_name`), but custom fields are particularly cryptic. For example, the meta data could tell you that the key `custom_123` is a _date_ custom field, with the label "Due date". With this information, you could use the key's value appropriately.

