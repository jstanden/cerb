---
id: "guides-impex-export-json"
title: "Export data to JSON"
url: "https://cerb.ai/guides/impex/export-json/"
summary: "This webpage provides a comprehensive guide on exporting data from Cerb to a JSON format, which is useful for data migration or merging multiple Cerb installations. It outlines the necessary requirements, including access to the Cerb database and storage directory, and the installation of PHP CLI. The guide details the installation process of the `cerb-package-exporter.php` script and the configuration of a `config.json` file to specify database and storage details. It also covers customization options such as ID mapping and scope adjustments to tailor the export process to specific needs. The exporting process is explained, with instructions on running the script to export ticket data and other records. An example JSON output is provided to illustrate the format and structure of the exported data."
tags: ["guides"]
---
# Introduction

This guide provides instructions on exporting data from Cerb to a neutral JSON format.

This process is used to migrate data from Cerb to another application, or to merge multiple Cerb installations.

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Customizations](#customizations)
  - [ID Mapping](#id-mapping)
  - [Scope](#scope)

- [Exporting](#exporting)
- [Example](#example)

# Requirements

Before you begin, verify you have:

- Access to a copy of your Cerb database
- Access to a copy of your Cerb `storage/` directory
- PHP CLI installed on your server/machine

# Installation

Copy the `cerb-package-exporter.php` script to a directory on your server:

```
curl -O "https://raw.githubusercontent.com/cerb/cerb-release/v12.0/install/extras/impex/cerb-package-exporter.php"
```

# Configuration

In the same directory, create a `config.json` file:

```
{
	"exporter": {
		"source": "Cerb10",
		"options": {
			"db_host": "localhost",
			"db_name": "cerb",
			"db_user": "root",
			"db_pass": "",
			"mask_prefix": "",
			"storage_path": "/path/to/cerb/storage/"
		}
	}
}
```

- `exporter.options.db_host` is the server (IP or hostname) containing your Cerb database
- `exporter.options.db_name` is the name of your Cerb database
- `exporter.options.db_user` is the user account with access to your Cerb database
- `exporter.options.db_pass` is the password of your database user
- `exporter.options.storage_path` is the full path to the `storage/` directory from your Cerb installation

# Customizations

### ID Mapping

The script is designed to be modified to meet the specific needs of your project.

Several functions can be implemented to map record IDs between the source and destination:

- `mapGroupBucketIds($group_id, $bucket_id)`
- `mapCustomFieldId($id)`
- `mapWorkerId($id)`
- `mapTimeTrackingActivityId($id)`

For instance, to merge two Cerb instances, you would map from source groups/buckets/workers to records in the destination. The default JSON format can be directly imported into a Cerb instance as [packages](/docs/packages/).

### Scope

Edit the `_exportTickets()` function to control what ticket data will be exported: export only from specific groups/buckets, for specific organizations/owners, etc.

Edit `$ticket_json` to control the exported JSON format: replace group/bucket IDs with names, etc.

# Exporting

By default, the script will export your ticket data (tickets, messages, comments, and attachments). You may elect to also export data for workers and organizations.

In the directory where you installed `cerb-package-exporter.php`, run the following command:

```
php cerb-package-exporter.php -c config.json -o output
```

You will see output as your data exports. A large dataset will be broken up into multiple subdirectories.

# Example

**03-tickets-000001/000000001.json:**

```
{
    "package": {
        "name": "Ticket #1",
        "revision": 1,
        "requires": {
            "cerb_version": "10.4.9",
            "plugins": []
        },
        "configure": {
            "prompts": [],
            "placeholders": [],
            "options": {
                "disable_events": true
            }
        }
    },
    "records": [
        {
            "uid": "ticket_1",
            "_context": "ticket",
            "mask": "DEMO-001",
            "subject": "What is Cerb?",
            "importance": 50,
            "status": "open",
            "created": 1591945350,
            "updated": 1591950726,
            "participants": "a.costa@fiaflux.example",
            "group_id": 1,
            "bucket_id": 4,
            "owner_id": 2,
            "org": "Fiaflux Software"
        },
        {
            "uid": "message_1",
            "_context": "message",
            "ticket_id": "{{{uid.ticket_1}}}",
            "created": 1591945350,
            "is_outgoing": 0,
            "sender": "a.costa@fiaflux.example",
            "response_time": 0,
            "hash_header_message_id": "3eda2ef8f4f0459c8e24b909e3068a8c54a2bfe6",
            "headers": "To: noreply@cerb.example\nFrom: a.costa@fiaflux.example\nSubject: What is Cerb?\nDate: Fri, 12 Jun 2020 07:02:30 +0000\nContent-Type: text\/plain; charset=utf-8\nMessage-Id: <demo1.msg1@cerb.example>\n",
            "content": "My team needs something like Cerb. How should I pitch it to our management team?\n\n- Alessio\n"
        },
        {
            "uid": "message_2",
            "_context": "message",
            "ticket_id": "{{{uid.ticket_1}}}",
            "created": 1591950726,
            "is_outgoing": 1,
            "sender": "noreply@cerb.example",
            "response_time": 5376,
            "hash_header_message_id": "b45e8e5fd1f07457d4c188bb4ae9bad421b27ff3",
            "headers": "To: a.costa@fiaflux.example\nFrom: noreply@cerb.example\nSubject: Re: What is Cerb?\nDate: Fri, 12 Jun 2020 08:32:06 +0000\nContent-Type: text\/plain; charset=utf-8\nMessage-Id: <demo1.msg2@cerb.example>\n",
            "content": "> How should I pitch [Cerb] to our management team?\n\nHi Alessio,\n\nCerb helps you build and automate workflows; it improves team collaboration; and it can replace several other apps and services to reduce your costs.\n\nYour team only needs a web browser to use it, and they have the freedom to work from anywhere.\n\nYou have the flexibility to run Cerb on your own servers or as a fully managed service in the cloud.\n",
            "worker_id": 2
        }
    ]
}
```
