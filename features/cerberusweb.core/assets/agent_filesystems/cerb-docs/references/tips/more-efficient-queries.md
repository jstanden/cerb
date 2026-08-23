---
id: "tips-more-efficient-queries"
title: "More efficient OR queries"
url: "https://cerb.ai/tips/more-efficient-queries/"
summary: "This page discusses ways to make search queries more efficient when using the OR operator in Cerb, a tool for managing helpdesk operations. The author suggests that where and how you use the OR operator can impact efficiency, providing an example of how rewriting a query from `status:o (someFilter:1 OR otherFilter:2)` to `(status:o someFilter:1) OR (status:o otherFilter:2)` can improve results by reducing the scope of redundant filters."
tags: ["tips"]
---
When making search queries with the OR operator, where and how you use it can make your query more efficient.

For example:

```
status:o (someFilter:1 OR otherFilter:2)
```

is more efficient as:

```
(status:o someFilter:1) OR (status:o otherFilter:2)
```

That's because the common `status:o` filter being redundantly included in both operands of the OR reduces the scope. With the first query, those filters could match 1M tickets but only 100 of them are also open.

