---
id: "docs-setup-storage-profiles"
title: "Setup: Storage Profiles"
url: "https://cerb.ai/docs/setup/storage/profiles/"
summary: "This page provides information on setting up storage profiles in Cerb. By default, Cerb stores objects in the database and the local filesystem, with message content in the database and attachments in the filesystem. The page explains how to create a new storage profile to store objects in alternative locations, such as Amazon Simple Storage Service (S3). Once a storage profile is created, it can be applied to a storage schema, allowing for flexible and customizable storage solutions."
tags: ["docs"]
---
By default, Cerb stores objects in the database and the local filesystem (in the `storage/` directory). Message content is stored in the database and attachments are stored in the filesystem.

You can create a new **storage profile** here to store objects in different locations, like Amazon Simple Storage Service (S3).

Once a profile is created here, you can use it on a [storage schema](/docs/setup/storage/overview/).

