---
id: "tips-incremental-quick-search"
title: "Incremental quick search"
url: "https://cerb.ai/tips/incremental-quick-search/"
summary: "This page provides a tip for using incremental quick search in Cerb to add new filters to an existing worklist without replacing the current filters. It explains how starting a quick search with a `+` allows users to append additional criteria to their existing search. An example is given where a user has a worklist filtered by open tickets linked to certain organizations and wants to further filter by tickets updated in the past year. By using the `+` prefix, users can efficiently refine their search results without losing the original filters."
tags: ["tips"]
---
You've probably noticed that when you quick search a [worklist](/docs/worklists/) it replaces the existing filters using the new query. This is efficient and convenient when you want to change all of the filters at once. Sometimes, however, you just want to add a few more filters and keep the existing ones.

To add new filters to a previously filtered worklist, start your quick search with `+`.

For instance, let's assume that you already have a [ticket](/docs/tickets/) worklist filtered by `status:o links.org:(Donter)` (open tickets linked to organizations matching _'Donter'_). You want to further filter the results to only those updated in the past year, but you left the page and came back, and now the quick search field is empty.

 

You can use this quick search:

```
+updated:"-1 year"
```

The worklist now has three filters:

 
