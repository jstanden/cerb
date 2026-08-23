---
id: "guides-scaling-redis"
title: "Redis"
url: "https://cerb.ai/guides/scaling/redis/"
summary: "This guide provides comprehensive instructions for configuring Redis as a caching solution using Docker containers. It covers both basic and secure configurations, including creating Redis containers, managing network connectivity, setting passwords, and configuring Cerb to use Redis. The guide also includes troubleshooting tips, best practices for security, and common Docker commands for managing Redis containers, all aimed at improving application performance through effective caching."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Requirements](#requirements)
- [Local Development Setup](#local-development-setup)
- [Verifying the Connection](#verifying-the-connection)
- [References](#references)

# Introduction

Redis[1](#fn:redis) is an in-memory data structure store that serves as a powerful [caching](/docs/setup/configure/cache/) solution for Cerb. While Cerb's default filesystem caching works well for single-server setups, Redis enables you to scale your deployment across multiple servers while significantly reducing database query traffic. By caching frequently accessed but infrequently changed content (like worker data, groups, and bucket information), Redis helps optimize your application's performance. This guide will walk you through configuring Redis for caching in Cerb using Docker containers.

# Requirements

- Docker installed and running
- A working Cerb installation in Docker
- php8.5-redis package installed (for Ubuntu 24.04)

# Local Development Setup

Start Redis on a local port with the configuration:

```
docker run --name redis-cerb -p 6379:6379 -d redis
```

Finally, configure Cerb to use Redis by navigating to **Setup&nbsp;» Configure&nbsp;» Cache** and entering these settings:

- Host: `host.docker.internal`
- Port: `6379`

 

Once connected, Cerb will show: Objects are cached in **Redis** at **host.docker.internal:6379**

 

# Verifying the Connection

To verify that Redis is working properly:

Start by connecting to the Redis CLI:

```
docker exec -it redis-cerb redis-cli
ping
```

You should receive `PONG` as a response.

 

# References

1. Redis: dockerhub - https://hub.docker.com/\_/redis&nbsp;[↩](#fnref:redis)

