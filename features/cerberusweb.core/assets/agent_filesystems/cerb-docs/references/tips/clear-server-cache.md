---
id: "tips-clear-server-cache"
title: "Clear the server-side cache"
url: "https://cerb.ai/tips/clear-server-cache/"
summary: "This page provides instructions on how to clear the server-side cache in Cerb. It explains the purpose of the cache, which is to speed up operations like database queries by storing temporary copies of resources. While Cerb typically manages cache clearing automatically during modifications or upgrades, manual intervention is required if the database is altered directly. The page outlines a simple process for flushing the cache by appending 'update' to the URL while logged into Cerb. It also addresses potential authorization issues, advising users to add their IP to the allowlist in the security settings if necessary."
tags: ["tips"]
---
You're probably familiar with the file cache in your web browser. It saves a temporary copy of website resources on your device to speed up subsequent network requests. This is useful for files that change infrequently, like images, stylesheets, fonts, and scripts.

Cerb uses a similar cache on the web server to speed up expensive operations like database queries.

You usually don't have to think about the cache. When you modify records or [upgrade](/docs/upgrading/), Cerb automatically clears the appropriate caches for you.

However, if you modify the database directly then you'll need to clear the cache yourself.

### Flushing the cache

1. Click on the [logo](/docs/guide/workers/user-interface/#logo) in the top left while logged into Cerb.

2. Append `update` to the URL in your browser and press `<ENTER>`.

3. This runs the updater and flushes the cache. After a few seconds you should be returned to your default page.

If you receive an error about your IP not being authorized, you can add it to the allowlist in **Setup » Configure » Security**.

