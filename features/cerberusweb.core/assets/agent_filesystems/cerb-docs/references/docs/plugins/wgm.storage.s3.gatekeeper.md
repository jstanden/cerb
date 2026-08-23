---
id: "docs-plugins-wgm-storage-s3-gatekeeper"
title: "Plugin: S3 Gatekeeper Storage Engine"
url: "https://cerb.ai/docs/plugins/wgm.storage.s3.gatekeeper/"
summary: "This page provides information about the S3 Gatekeeper Storage Engine plugin for Cerb, developed by Webgroup Media, LLC. The plugin introduces a storage engine for Amazon S3 that enhances security by not storing or exposing AWS account credentials. Instead, it uses a remote gatekeeper script to authenticate and obtain a secure, pre-signed URL for making requests. The plugin is identified by the ID 'wgm.storage.s3.gatekeeper' and is located in the path 'storage/plugins/wgm.storage.s3.gatekeeper/'. The page also lists the extension type as a Storage Engine with the identifier 'devblocks.storage.engine.gatekeeper'."
tags: ["docs"]
---
| **Name:** | S3 Gatekeeper Storage Engine |
| **Identifier (ID):** | wgm.storage.s3.gatekeeper |
| **Author:** | Webgroup Media, LLC. |
| **Path:** | storage/plugins/wgm.storage.s3.gatekeeper/ |
| **Image:** |  |

This plugin adds a new storage engine for Amazon S3 that doesn't store or expose your AWS account credentials. Instead, it authenticates with a remote gatekeeper script and retrieves a secure, pre-signed URL that is used to perform requests.

- [Extensions](#extensions)
  - [Storage Engine](#storage-engine)

# Extensions

### Storage Engine

| S3 Gatekeeper | `devblocks.storage.engine.gatekeeper` |

[\< Plugins](/docs/plugins/#plugins)

