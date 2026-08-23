---
id: "guides-scaling-memcached"
title: "Memcached"
url: "https://cerb.ai/guides/scaling/memcached/"
summary: "This guide provides instructions for configuring Memcached as a caching solution using Docker containers. Learn how to set up a basic Memcached container and configure Cerb to use it through the Memcached interface for improved application performance."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Requirements](#requirements)
- [Local Development Setup](#local-development-setup)
- [Verifying the Connection](#verifying-the-connection)
- [References](#references)

# Introduction

Memcached[1](#fn:memcached) is a high-performance distributed memory caching system that serves as a powerful [caching](/docs/setup/configure/cache/) solution for Cerb. While Cerb's default filesystem caching works well for single-server setups, Memcached enables you to scale your deployment across multiple servers while significantly reducing database query traffic. By caching frequently accessed but infrequently changed content (like worker data, groups, and bucket information), Memcached helps optimize your application's performance. This guide will walk you through configuring Memcached for caching in Cerb using Docker containers.

# Requirements

- Docker installed and running
- A working Cerb installation in Docker

# Local Development Setup

Start Memcached on a local port with the configuration:

```
docker run --name memcached-cerb -p 11211:11211 -d memcached
```

Finally, configure Cerb to use Memcached by navigating to **Setup&nbsp;» Configure&nbsp;» Cache** selecting Memcached as your cache type, and entering these settings:

- Host: `host.docker.internal`
- Port: `11211`

 

Once connected, Cerb will show: Objects are cached in **Memcached** at **host.docker.internal:11211**

 

# Verifying the Connection

To verify that Memcached is working properly:

Start by checking the server statistics:

```
echo stats | nc 127.0.0.1 11211
```

You should receive a response showing various Memcached statistics, indicating the server is running and accepting connections.

 

# References

1. Memcached: dockerhub - https://hub.docker.com/\_/memcached&nbsp;[↩](#fnref:memcached)

