---
id: "docs-setup-storage-overview"
title: "Setup: Storage Overview"
url: "https://cerb.ai/docs/setup/storage/overview/"
summary: "This page provides an overview of the active storage schemas in Cerb, detailing the total number of objects and the total size for each schema. It also offers the option to configure storage settings by clicking the edit link for each schema, allowing users to manage how content is stored and archived."
tags: ["docs"]
---
This page makes storage lifecycles easy to read: the **active** and **archive** profile for each storage schema are clearly marked, and a distribution bar compares the number or size of objects held by each profile.

### Migrating objects between profiles

A button on this page starts a migration of objects from one storage profile to another.

Migration isn't limited to the active-to-archive direction driven by the `cron.storage` scheduler job. Storage that has become fragmented across profiles can be consolidated, and long-term storage such as S3 can be brought back to local disk.

Migrations run as parallel background [queue jobs](/docs/records/types/queue_job/), so a large migration continues after you navigate away.

This page displays the active _storage schemas_, with the total number of objects and total size for each schema.

On [Cerb Cloud](/pricing/) the storage engine is **pinned by the platform** and can't be changed. The same applies to the [cache](/docs/setup/configure/cache/) engine. Both are managed for you as part of the subscription.

Click the **(edit)** link for a schema to configure how its content is stored and archived.

