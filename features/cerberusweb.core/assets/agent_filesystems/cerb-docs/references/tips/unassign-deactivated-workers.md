---
id: "tips-unassign-deactivated-workers"
title: "Remove assignments from deactivated workers"
url: "https://cerb.ai/tips/unassign-deactivated-workers/"
summary: "This page provides guidance for administrators on managing assignments for deactivated worker accounts in Cerb. It explains how to deactivate worker accounts and highlights that while deactivated workers cannot log in or be included in active assignment lists, their historical data remains intact. The page introduces the deep quick search feature in Cerb 7.3, which allows administrators to identify records, such as tickets, that are still assigned to deactivated workers. It provides example queries to find and update these records, ensuring that assignments are appropriately managed without altering the records' existing statuses unless specified."
tags: ["tips"]
---
As an administrator, you can deactivate worker accounts by clicking on **Workers** in the [Search](/docs/guide/workers/user-interface/#search-menu) menu. A deactivated worker is no longer able to log in, and they aren't included in active worker lists when making assignments; but their entire history of email replies and comments is preserved.

A deactivated worker may still be assigned to historical records. You can use the new [deep quick search](/docs/workspaces/#deep-searching) feature in Cerb 7.3 to find records owned by worker accounts that are deactivated.

For instance, you can search a [ticket](/docs/tickets/) [worklist](/docs/worklists/) with this query:

```
owner:(isDisabled:y)
```

This will return all the tickets that are owned by a deactivated worker. You can then [bulk update](/docs/workspaces/#bulk-update) those results to remove the owner. The records will keep their existing status unless you explicitly change it.

If you want to handle open and closed tickets differently, you can also include a status filter in the query:

```
owner:(isDisabled:y) status:[open,waiting]
```
