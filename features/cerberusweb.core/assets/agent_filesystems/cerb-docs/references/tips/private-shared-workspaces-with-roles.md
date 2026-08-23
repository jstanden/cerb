---
id: "tips-private-shared-workspaces-with-roles"
title: "Private shared workspaces with roles"
url: "https://cerb.ai/tips/private-shared-workspaces-with-roles/"
summary: "This page provides guidance on creating private shared workspaces in Cerb, focusing on the different ownership types and their permissions. It explains how app-owned, role-owned, group-owned, and worker-owned workspaces function, particularly emphasizing the flexibility of role-owned workspaces for sharing with select workers across different groups. The page also includes a step-by-step process for admins to create a new role, assign it to workers or groups, and use it to manage workspace ownership and access."
tags: ["tips"]
---
When you create a [workspace](/docs/workspaces/) in Cerb, the owner determines who is able to use or modify it.

- **App-owned** workspaces can be used by everyone, but can only be modified by admins.
- **Role-owned** workspaces can be used by anyone in that role, but can only be modified by admins.
- **Group-owned** workspaces can be used by [group](/docs/groups/) members, but can only be modified by group managers.
- **Worker-owned** workspaces can only be used or modified by that [worker](/docs/workers/).

Sometimes you need to share a workspace with a few workers from different groups, while hiding it from everyone else. You can handle this with role-owned workspaces.

You'll need to be an admin to create a new role.

From the global [Search menu](/docs/guide/workers/user-interface/#search-menu), select **Roles**.

Click the **(+)** icon above the worklist to add a new role.

 

Give a **Name:** to the new role.

In **Apply to:**, you can assign individual workers or entire groups.

For **Privileges** just select **None**.

 

Then click the **Save Changes** button.

Now from the **Search&nbsp;» Workspace Pages** menu, you can use the new role as the owner when creating a new workspace or reassigning an existing one.

 
