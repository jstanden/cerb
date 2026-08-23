---
id: "guides-api-using-php"
title: "Get started with the API using the PHP library"
url: "https://cerb.ai/guides/api/using-php/"
summary: "This webpage provides a comprehensive guide on how to get started with the Cerb API using a PHP library. It covers essential steps such as creating an API key-pair, setting up a sample PHP project, and sending requests to the Cerb API. The guide includes detailed instructions on running a search for open tickets, creating a task, and marking the task as completed. It also provides code snippets and expected JSON responses for each action, ensuring users can effectively interact with the Cerb API. The page concludes with a suggestion to consult the API documentation for further exploration of available requests."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Create an API key-pair](#create-an-api-key-pair)
- [Create a sample project](#create-a-sample-project)
- [Send the request to the Cerb API](#send-the-request-to-the-cerb-api)
- [Run a search](#run-a-search)
- [Create a task](#create-a-task)
- [Mark the task as completed](#mark-the-task-as-completed)
- [Next steps](#next-steps)

# Introduction

This step-by-step guide demonstrates how to use Cerb's PHP library to interact with the API.

# Create an API key-pair

If you haven't already, [follow these instructions](/guides/api/configure-plugin/) to create an API key-pair.

If you aren't an administrator, you'll need to have one create a key-pair for you.

 

# Create a sample project

Next, we'll create a simple PHP project to send API requests to Cerb.

[Download the PHP library](/docs/api/libraries/php/) and save it as `CerbApi.php`.

Let's start with a simple request for your account information.

Create a new `index.php` file with the following content:

```
<?php
require_once("CerbApi.php");

define('CERB_BASE_URL', 'https://cerb.example/rest/'); // URL to your Cerb install
define('CERB_ACCESS_KEY', 'XXXX'); // Access Key
define('CERB_SECRET_KEY', 'XXXX'); // Secret Key

$cerb = new Cerb_WebAPI(CERB_ACCESS_KEY, CERB_SECRET_KEY);

$out = $cerb->get(CERB_BASE_URL . 'workers/me.json');

if(null != ($content_type = $cerb->getContentType())) {
	header("Content-Type: " . $content_type);
	echo $out;
}
```

You'll need to modify the following constants:

- `CERB_BASE_URL`: Change this to the base URL of your Cerb installation, with `/rest/` appended to the index. If you aren't using [friendly URLs](/docs/friendly-urls/) then append `/index.php/rest/` instead.

- `CERB_ACCESS_KEY`: This is the public part of your API key-pair. It's sent in API requests to identify you.

- `CERB_SECRET_KEY`: This is the private part of your API key-pair. It's never sent in API requests but is used to sign them.

# Send the request to the Cerb API

Run your `index.php` script in a browser or from the command line.

You should receive back a JSON response with your worker details:

```
{
  "__build": 2017110901,
  "__status": "success",
  "__version": "8.2.2",
  "_context": "cerberusweb.contexts.worker",
  "_image_url": "https://cerb.example/avatars/worker/1?v=1510279250",
  "_label": "Kina Halpue",
  "address__context": "cerberusweb.contexts.address",
  "address_contact__context": "cerberusweb.contexts.contact",
  "address_contact_org__context": "cerberusweb.contexts.org",
  "address_contact_org_email__context": "cerberusweb.contexts.address",
  "address_id": 2,
  "address_org__context": "cerberusweb.contexts.org",
  "address_org_email__context": "cerberusweb.contexts.address",
  "at_mention_name": "Kina",
  "calendar__context": "cerberusweb.contexts.calendar",
  "calendar_id": 1,
  "dob": "1984-01-09",
  "first_name": "Kina",
  "full_name": "Kina Halpue",
  "gender": "F",
  "id": 1,
  "is_disabled": 0,
  "is_superuser": 0,
  "language": "en_US",
  "last_name": "Halpue",
  "location": "Los Angeles, CA USA",
  "mobile": "+15559991234",
  "phone": "",
  "record_url": "https://cerb.example/profiles/worker/1-Kina-Halpue",
  "time_format": "D, d M Y h:i a",
  "timezone": "America/Los_Angeles",
  "title": "Customer Support Manager",
  "updated": 1510279250
}
```

# Run a search

Now let's send a search request to the API to build a list of open tickets.

Comment out the `$cerb->get()` request and add the following:

```
//$out = $cerb->get(CERB_BASE_URL . 'workers/me.json');

$out = $cerb->get(CERB_BASE_URL . 'records/ticket/search.json?q=status:o&expand=owner_,custom_');
```

We're sending the following parameters to `records/ticket/search.json`:

- `q`: The search query.
- `expand`: We want to expand the `owner_` and `custom_` keys to retrieve full owner and custom field details.

The response should be a JSON array of ticket records, like:

```
{
  "__build": 2017110901,
  "__status": "success",
  "__version": "8.2.2",
  "count": 5,
  "page": 1,
  "results": [
    {
      "_context": "cerberusweb.contexts.ticket",
      "_label": "[#AHF-81578-221] Welcome to Cerb!",
      "closed_at": 0,
      "closed": 0,
      "created": 1510279251,
      "elapsed_response_first": "0",
      "elapsed_resolution_first": "0",
      "id": 1,
      "importance": 50,
      "mask": "AHF-81578-221",
      "num_messages": "1",
      "org_id": 0,
      "reopen_date": 0,
      "spam_score": 0.0023,
      "spam_training": "",
      "status_id": 0,
      "subject": "Welcome to Cerb!",
      "updated": 1510279251,
      "status": "open",
      "url": "https://cerb.example/profiles/ticket/AHF-81578-221",
      "group_id": 1,
      "bucket_id": 1,
      "initial_message_id": 1,
      "initial_response_message_id": 0,
      "latest_message_id": 1,
      "owner_id": 0,
      "group__context": "cerberusweb.contexts.group",
      "owner__context": "cerberusweb.contexts.worker",
      "org__context": "cerberusweb.contexts.org",
      ...
    },
    ...
  ],
  "total": 5
}
```

# Create a task

Comment out the last `$cerb->get()`.

Let's create a task record through the API:

```
//$out = $cerb->get(CERB_BASE_URL . 'workers/me.json');
//$out = $cerb->get(CERB_BASE_URL . 'records/ticket/search.json?q=status:o&expand=owner_,custom_');

$out = $cerb->post(CERB_BASE_URL . 'records/task/create.json', [
	['fields[title]', 'Test the Cerb API'],
	['fields[status]', 'open'],
]);
```

You should get a JSON response like:

```
{
  "__build": 2017110901,
  "__status": "success",
  "__version": "8.2.2",
  "_context": "cerberusweb.contexts.task",
  "_label": "Test the Cerb API",
  "completed": 0,
  "created": 1510362808,
  "due": 0,
  "id": "123",
  "importance": 0,
  "is_completed": false,
  "owner__context": "cerberusweb.contexts.worker",
  "owner_id": 0,
  "record_url": "https://cerb.example/profiles/task/123-Test-the-Cerb-API",
  "reopen": 0,
  "status": "open",
  "status_id": 0,
  "title": "Test the Cerb API",
  "updated": 1510362808
}
```

# Mark the task as completed

Comment out the `$cerb->post()` request.

Now let's close the task record we just created. You'll need to replace `123.json` with the real `id` value in your last response above.

```
//$out = $cerb->get(CERB_BASE_URL . 'workers/me.json');
//$out = $cerb->get(CERB_BASE_URL . 'records/ticket/search.json?q=status:o&expand=owner_,custom_');
//$out = $cerb->post(CERB_BASE_URL . 'records/task/create.json', [
//	['fields[title]', 'Test the Cerb API'],
//	['fields[status]', 'open'],
//]);

$out = $cerb->put(CERB_BASE_URL . 'records/task/123.json?' . http_build_query([
	'fields[status]' => 'closed',
]));
```

You should get back an updated ticket record with the new `status`:

```
{
  "__build": 2017110901,
  "__status": "success",
  "__version": "8.2.2",
  "_context": "cerberusweb.contexts.task",
  "_label": "Test the Cerb API",
  "id": "123",
  "is_completed": true,
  "status": "closed",
  "status_id": 1,
	...
}
```

# Next steps

You can refer to the [API documentation](/docs/api/) for a full list of possible requests.

