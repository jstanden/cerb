---
id: "guides-scaling-valkey"
title: "Valkey"
url: "https://cerb.ai/guides/scaling/valkey/"
summary: "This guide provides instructions for configuring Valkey as a caching solution using Docker containers. Learn how to set up a basic Valkey container and configure Cerb to use it through the Redis cache configuration interface for improved application performance."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Requirements](#requirements)
- [Local Development Setup](#local-development-setup)
- [Verifying the Connection](#verifying-the-connection)
- [References](#references)

# Introduction

Valkey[1](#fn:valkey) is a high-performance data structure server that serves as a powerful [caching](/docs/setup/configure/cache/) solution for Cerb. While Cerb's default filesystem caching works well for single-server setups, Valkey enables you to scale your deployment across multiple servers while significantly reducing database query traffic. By caching frequently accessed but infrequently changed content (like worker data, groups, and bucket information), Valkey helps optimize your application's performance. This guide will walk you through configuring Valkey for caching in Cerb using Docker containers.

# Requirements

- Docker installed and running
- A working Cerb installation in Docker

# Local Development Setup

Start Valkey on a local port with the configuration:

```
docker run --name valkey-cerb -p 6379:6379 -d valkey/valkey
```

Finally, configure Cerb to use Valkey by navigating to **Setup&nbsp;» Configure&nbsp;» Cache** selecting Redis as your cache type, and entering these settings:

- Host: `host.docker.internal`
- Port: `6379`

 

Once connected, Cerb will show: Objects are cached in **Redis** at **host.docker.internal:6379**

 

# Verifying the Connection

To verify that Valkey is working properly:

Start by connecting to the Valkey CLI:

```
docker exec -it valkey-cerb valkey-cli
ping
```

You should receive `PONG` as a response.

 

# References

1. Valkey: dockerhub - https://hub.docker.com/r/valkey/valkey&nbsp;[↩](#fnref:valkey)

