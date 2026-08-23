---
id: "docs-roles"
title: "Worker Roles"
url: "https://cerb.ai/docs/roles/"
summary: "This page explains the concept of worker roles and privileges in Cerb. It describes how roles are used to grant specific sets of privileges to workers, ensuring that not all workers have equal authority, particularly in sensitive tasks. Multiple roles can be assigned to a single worker, and roles can be automatically applied based on group memberships. A privilege is granted if any of a worker's roles allow it. Additionally, the page highlights the role of administrators, who have unrestricted privileges and are responsible for determining the necessary privileges for other workers to perform their duties effectively."
tags: ["docs"]
---
[Workers](/docs/workers/) generally don't have equal authority in executing their duties. For instance, someone in an entry-level position shouldn't have access to destroy important business records without any oversight.

In Cerb, sets of **privileges** are granted to workers using **roles**.

Multiple roles can be applied to the same worker. Roles can also be automatically applied to workers based on their [group](/docs/groups/) memberships.

A particular privilege is granted to a worker if _any_ of their roles permit it.

Special workers called **administrators** have no restrictions on their privileges. It is their job to decide which privileges everyone else needs in order to accomplish their jobs.

 
