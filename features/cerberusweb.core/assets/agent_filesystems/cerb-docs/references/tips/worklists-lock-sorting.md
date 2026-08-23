---
id: "tips-worklists-lock-sorting"
title: "Lock worklist sorting"
url: "https://cerb.ai/tips/worklists-lock-sorting/"
summary: "This page provides guidance on how to lock the sorting of worklists in Cerb to improve team efficiency and reduce task collisions. It explains the importance of sorting worklists by responsibility rather than defaulting to the most recent updates, which can lead to inefficiencies. The page details how to prevent workers from changing the sort order by using the 'Prevent workers from changing the sort column' option and how to explicitly set a specific sort column through the quick search query. This ensures that all team members view the worklist in a consistent manner, aligned with their responsibilities and priorities."
tags: ["tips"]
---
A client asked:

> I see that I can prevent workers from changing the sorting on a worklist, but how do I force sorting by a specific column?

There's a natural tendency to focus on the most recent tickets that land in the inbox. New messages offer quick wins for faster response times, and they provide an opportunity to remain busy while avoiding the pile of more complex issues building up in the backlog. This sort order is also used by default in most email applications, and people are used to it.

However, when everyone on your team sorts their work in the same way, it leads to many collisions while finding the next thing to work on.

We introduced [bucket responsibilities](/releases/7.0/#responsibilities) back in Cerb 7.0 to provide personalized worklists to every worker based on their role and responsibilities. When you add the "Responsibility" column to a worklist and sort on it, workers are directed to work that matches their highest responsibilities, with the highest importance, that have been waiting for the longest.

This feature provides a big boost to team efficiency – but it doesn't do you any good if workers just change their sorting back to "Most recently updated".

You can prevent workers from changing the sorting on a worklist. Click on the gear icon in the top right of the worklist you want to edit.

In the **Options** section, you'll find the **Prevent workers from changing the sort column** option:

 

When you enable this option, workers will no longer be able to change sorting on a worklist by clicking on a column header.

By default, the worklist will remember the sort column being used when this option was enabled. So if you're sorting by "Responsibility" in descending order now, every user of the worklist will also see the same thing.

You can also explicitly force a specific column by adding it to the query in **Restrict the worklist results using this quick search**:

```
status:[o] inGroupsOf:me sort:created
```

You can sort in descending order by prepending a `-` to the sort field:

```
status:[o] inGroupsOf:me sort:-responsibility
```

The field names are the same as those that appear in the quick search menu.

