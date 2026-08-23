---
id: "docs-setup-configure-cache"
title: "Cache"
url: "https://cerb.ai/docs/setup/configure/cache/"
summary: "This page explains how Cerb optimizes performance by caching frequently accessed but infrequently changed content, such as worker data, to reduce database query traffic. It describes the caching mechanism, which involves storing data like worker records, groups, buckets, sender addresses, bots, and behaviors, and invalidating the cache when changes occur. The default caching method involves saving cache files to the filesystem, but for scaling beyond a single server or addressing filesystem I/O bottlenecks, distributed caching with Redis or Memcached is supported."
tags: ["docs"]
---
 

To optimize performance, Cerb caches _frequently accessed_ but _infrequently changed_ content. This significantly reduces database query traffic.

For instance, worker data is used on almost every page in Cerb, but you may go weeks without adding or modifying worker records. We cache worker records and _invalidate_ the cache when one of the records changes. If you retrieve a list of tickets with an _owner_ column, we can fill-in the worker information from the cache without requiring a potentially expensive `JOIN` in the database.

We use this approach in many other places as well: groups, buckets, sender addresses, bots, behaviors, etc.

By default, Cerb saves cache files to the filesystem in the `./storage/tmp/` directory. The underlying operating system usually caches the contents of these files in memory anyway.

If you experience filesystem I/O bottlenecks, or you want to [scale](/docs/scaling/) beyond a single web server, you may choose to set up a distributed cache using Redis[1](#fn:redis), Valkey[2](#fn:valkey), or Memcached[3](#fn:memcached). We support them all.

# See also

- Guide: [Scaling with Memcached](/guides/scaling/memcached/)
- Guide: [Scaling with Redis](/guides/scaling/redis/)
- Guide: [Scaling with Valkey](/guides/scaling/valkey/)

# References

1. https://redis.io/&nbsp;[↩](#fnref:redis)

2. https://valkey.io/&nbsp;[↩](#fnref:valkey)

3. https://memcached.org/&nbsp;[↩](#fnref:memcached)

