---
id: "docs-api-custom-fields"
title: "API: Custom Fields"
url: "https://cerb.ai/docs/api/custom-fields/"
summary: "This page provides detailed information on how to work with custom fields in the Cerb API. It covers the types of custom fields available, such as checkboxes, dropdowns, dates, files, and more. The page explains how to build a list of available custom fields, retrieve custom fields on records and search results, and set custom fields on records using API requests. It includes examples of API calls for each operation, demonstrating how to use key expansion to access custom field data in both GET and POST requests, as well as how to update custom fields with PUT requests."
tags: ["docs"]
---
Records in Cerb can be extended with [custom fields](/docs/custom-fields/) and [fieldsets](/docs/custom-fieldsets/). These fields are available in the [API responses](/docs/api/responses/) using [key expansion](/docs/api/responses/#expanding-keys-in-api-requests).

- [Custom field types](#custom-field-types)
- [Working with custom fields in the API](#working-with-custom-fields-in-the-api)
  - [Building a list of the available custom fields](#building-a-list-of-the-available-custom-fields)
  - [Retrieving custom fields on records](#retrieving-custom-fields-on-records)
  - [Retrieving custom fields on search results](#retrieving-custom-fields-on-search-results)
  - [Setting custom fields on records](#setting-custom-fields-on-records)

# Custom field types

A custom field is created with one of the following types:

| Type | Description |
| --- | --- |
| **(C) Checkbox/Bit** | A _true_ or _false_ value, represented by `1` or `0` respectively. |
| **(D) Dropdown/Picklist** | A single selection from a predefined list of options. |
| **(E) Date** | A 32-bit Unix timestamp integer, representing the number of elapsed seconds since January 1, 1970 00:00:00 GMT. |
| **(F) File** | A file contained within the storage service. |
| **(I) Multiple File** | Multiple files contained within the storage service. |
| **(L) Record Link** | A link to another record. |
| **(M) List** | A list of multiple text values. |
| **(N) Number** | An unsigned 32-bit whole number. |
| **(S) Single Line** | A single line of text. |
| **(T) Multiple Lines** | Multiple lines of text, with linefeeds preserved. |
| **(U) URL** | A Uniform Resource Locator to a web page. |
| **(W) Worker** | A single selection from the list of active workers. |
| **(X) Multiple Checkboxes** | Any number of selections from a predefined list of options. |

# Working with custom fields in the API

### Building a list of the available custom fields

You can retrieve custom field information using the [/records](/docs/api/endpoints/records/) API endpoint.

The available custom fields and fieldsets for a specific record type can also be retrieved using the [/contexts/list](/docs/api/endpoints/contexts/#list) endpoint:

```
GET /rest/contexts/list.json
```

This will return a list of record types with custom field information, like the following example:

```
{
   id: "cerberusweb.contexts.ticket",
   name: "Ticket",
   plugin_id: "cerberusweb.core",
   custom_fields: [
      {
         id: 146,
         name: "Due",
         type: "E"
      },
      {
         id: 143,
         name: "Priority",
         type: "D",
         params: {
            options: [
               "Moderate",
               "High",
               "Critical"
            ]
         }
      }
   ],
   custom_fieldsets: [
      {
         id: "1",
         name: "Billing",
         owner__context: "cerberusweb.contexts.group",
         owner_id: "5",
         custom_fields: [
            {
               id: 14,
               name: "Invoice #",
               type: "S"
            }
         ]
      },
      {
         id: "45",
         name: "Impact",
         owner__context: "cerberusweb.contexts.group",
         owner_id: "2",
         custom_fields: [
            {
               id: 166,
               name: "Severity",
               type: "D",
               params: {
                  options: [
                     "4 - Suggestion",
                     "3 - Need assistance",
                     "2 - Something isn't right",
                     "1 - Urgent"
                  ],
                  context: "cerberusweb.contexts.activity_log"
               }
            }
         ]
      }
   ]
}
```

### Retrieving custom fields on records

Custom fields can be retrieved on records using key expansion in `GET` requests:

```
GET /rest/records/tickets/123.json?expand=custom_
```

The _keys_ for custom fields are in the format `custom_123`, where `123` is the ID. The labels and types of each custom field are in the `_labels` and `_types` keys respectively.

### Retrieving custom fields on search results

Custom fields can be retrieved in search results using key expansion in `POST` requests:

```
POST /rest/records/tickets/search.json
Content-Type: application/x-www-form-urlencoded; charset=utf-8

expand=custom_&q=subject:Receipt*
```

When using an official library, the requests look like:

```
POST /rest/records/tickets/search.json
Host: cerb.example
Authorization: Bearer <token>

expand=custom_
&q=subject%3AReceipt%2A
&sortBy=created
&sortAsc=0
&page=1
```

The _keys_ for custom fields are in the format `custom_123`, where `123` is the ID. The labels and types of each custom field are in the `results_meta` key.

### Setting custom fields on records

Custom fields can be set on records in `PUT` requests:

```
PUT /rest/records/tickets/123.json?expand=custom_
Content-Type: application/x-www-form-urlencoded; charset=utf-8

fields[custom_14]=CERB-1234&fields[custom_143]=Critical
```

When using an official library, the requests look like:

```
PUT /rest/records/tickets/123.json?expand=custom_
Host: cerb.example
Authorization: Bearer <token>

fields[custom_14]=CERB-1234
&fields[custom_143]=Critical
```

Each custom field value is sent as a form field in the format `fields[custom_123]=value`, where `123` is the ID, and `value` is determined by the [custom field type](#field-types).

