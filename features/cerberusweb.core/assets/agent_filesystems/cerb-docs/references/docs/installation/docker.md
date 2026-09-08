---
id: "docs-installation-docker"
title: "Launch Cerb in Docker"
url: "https://cerb.ai/docs/installation/docker/"
summary: "This page provides a comprehensive guide on launching Cerb using Docker for local evaluation, development, and testing. It details the setup process, including installing Docker, starting containers with Docker Compose, and accessing the Cerb installer via a web browser. The guide also covers updating Cerb, the optional .env file copied from .env.template that sets the environment name, web server port, database credentials, service token, and PHP-FPM and nginx pool sizes, why editing the database settings later changes only what Cerb connects with, and how to rotate the password safely, running more than one environment on one machine with separate data, connecting to the MySQL console, and managing containers, including pausing, resuming, and deleting them. Additionally, it explains how to edit code with immediate reflection in the containers, making it a useful resource for developers working with Cerb in a Docker environment."
tags: ["docs"]
---
- [Install Docker](#install-docker)
- [Launch Cerb](#launch-cerb)
  - [Option 1: Docker Hub (Production)](#option-1-docker-hub-production)
    - [Running the scheduler in the container from the host](#running-the-scheduler-in-the-container-from-the-host)

  - [Option 2: GitHub (Development, Testing)](#option-2-github-development-testing)
    - [Starting local containers with Docker Compose](#starting-local-containers-with-docker-compose)
    - [Background drains](#background-drains)
    - [Updating Cerb](#updating-cerb)
    - [Customizing the environment](#customizing-the-environment)
      - [The database credentials](#the-database-credentials)
      - [The service token](#the-service-token)

    - [Running more than one environment](#running-more-than-one-environment)
    - [Connecting to the MySQL console](#connecting-to-the-mysql-console)
    - [Stopping the containers](#stopping-the-containers)
    - [Deleting containers](#deleting-containers)

https://www.youtube.com/embed/NkKUDS6oicw

# Install Docker

First, make sure Docker Desktop (desktops) or the Docker Engine (servers) is installed.

# Launch Cerb

## Option 1: Docker Hub (Production)

Cerb is available as a pre-built container image for `amd64` or `arm64` on Docker Hub. It must be paired with a web server that supports the FastCGI protocol (e.g. Caddy, nginx, Apache, IIS).

This repository provides several usage examples. For example:

```
git clone https://github.com/cerb/cerb-docker/

# ... or download + unzip: https://github.com/cerb/cerb-docker/archive/refs/heads/main.zip

cd cerb-docker/cerb-caddy-mysql

cat .env

docker compose up --build
```

The `cerb` container listens on port `:9000` using FastCGI and **must not** be exposed to the public network. The container is configured using environment variables:

| ENV | &nbsp; |
| --- | --- |
| `CERB_AUTHORIZED_IPS` | A comma-separated list of IPv4 addresses or subnets that can access `/cron` and `/update` without a session. |
| `CERB_INSTALL` | If `yes` the `/install/` path is available; otherwise it is blocked. Only enable during installation and disable afterward. |
| `MYSQL_HOST` | The database server hostname. This is created for you in the `cerb-caddy-mysql` example, but should be a dedicated server like Amazon RDS in production. |
| `MYSQL_DATABASE` | The database name. Use `CREATE DATABASE cerb CHARACTER SET utf8mb4` if using an external database server. |
| `MYSQL_USER` | The database user with `GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES`. |
| `MYSQL_PASSWORD` | The database user's password. |

The container uses the `/mnt/storage/` path for persistent data. In the example a local Docker volume binds to the path, but in production you should bind a shared filesystem like Amazon EFS, NFS, or GlusterFS. This allows you to scale containers horizontally.

The cerb-caddy-mysql example above provides additional configuration options:

| ENV | &nbsp; |
| --- | --- |
| `CERB_VERSION` | The Cerb versioned image tag to use. Use tags like `11` for 11.x, `11.1` for 11.1.x, or `11.1.0` for a specific version. The `latest` tag always points to the latest stable version. |
| `CERB_HOSTNAME` | If `localhost` a self-signed SSL certificate is generated; otherwise a LetsEncrypt certificate is automatically generated. Be sure the DNS for this hostname points to your server. Ports `:80` and `:443` must be exposed to the public network for LetsEncrypt to work. |
| `CERB_PORT` | The HTTP port. This should be `80` in production, but can be something like `8080` for testing. |
| `CERB_PORT_SSL` | The HTTPS/TLS port. This should be `443` in production, but can be something like `8443` for testing. |

A `docker-compose.yml` file is provided for reference and testing. In production, you should use a container orchestration service like Amazon ECS/Fargate, Google Cloud Run, Azure Containers, or Kubernetes.

You can use the cerb example to build your own customized image.

### Running the scheduler in the container from the host

You can use `docker exec` to ping the scheduler in the container.

```
docker exec cerb-docker-caddy-1 wget -O - --header "Host: localhost" "https://localhost/cron?loglevel=7&ignore_wait=1"
```

You can add this to `crontab` on the host.

## Option 2: GitHub (Development, Testing)

The Cerb source code ships with a Docker configuration for local evaluation, development, and testing. This creates preconfigured containers for Nginx (web server), PHP/FPM (code), and MySQL (database).

You can edit files in your local filesystem and the changes will be reflected instantly within the containers.

By default, data is stored in two volumes (one for the database and the other for the `./storage/` directory). A virtual network is created to connect the containers.

### Starting local containers with Docker Compose

Navigate to the directory where you want to install a copy of Cerb. Then run the following commands:

```
git clone -b v12.0 https://github.com/cerb/cerb-release.git v12.0

# ... or download + unzip: https://github.com/cerb/cerb-release/archive/refs/heads/v12.0.zip

cd v12.0

cd install/docker

cp .env.template .env

docker compose up --build
```

It will take a few minutes to build the container images the first time you run them. Afterward, the containers will start almost instantly.

Copying `.env.template` is **optional**. Every value it holds has the same default built into `docker-compose.yml`, so `docker compose up --build` works on a fresh clone with no `.env` at all. What the file gives you is one place to record what you changed – and it's what lets [more than one copy of Cerb](#running-more-than-one-environment) share a machine. It's ignored by git, so your settings survive a `git pull`.

Once the containers are running, open your browser to: `http://localhost/`

The guided installer will finish installing Cerb based on your needs. For testing we recommend disabling outbound email when prompted (this can always be re-enabled later). At the end of the installer you can also choose "Demo" mode to have test data to experiment with.

### Background drains

`/queue` and `/cron` route to a `php-fpm-background` container running a PHP-FPM pool tuned for long holds, while `php-fpm` serves everything else.

One background request can hold a child for the length of an [AI agent](/docs/agents/) turn or a [queue](/docs/queues/) drain, so keeping the two pools apart is what stops a busy queue from making the interface feel slow.

Scale the drain capacity with:

```
docker compose up --scale php-fpm-background=3
```

Three variables size the pools. They're already in [`.env`](#customizing-the-environment), commented out at their defaults:

| Variable | Default | Notes |
| --- | --- | --- |
| `CERB_WEB_CHILDREN` | `6` | PHP-FPM children serving page loads |
| `CERB_BACKGROUND_CHILDREN` | `4` | PHP-FPM children serving `/queue` and `/cron` |
| `CERB_NGINX_WORKERS` | `2` | nginx worker processes. Set it to `auto` for one per core |

`CERB_NGINX_WORKERS` previously had no setting and nginx ran `auto`, one worker per core, which is wasteful on a development machine with many of them. Set it back to `auto` on a dedicated host.

A fourth variable isn't in the template because it's rarely changed. **`CERB_FPM_BACKGROUND`** points the web server at the background pool and defaults to `php-fpm-background:9000`. Pointing it back at `php-fpm:9000` collapses the two pools into one.

Size the background pool on **memory and database connections rather than cores**. A drain worker burns almost no CPU while it waits on a model provider, but holds roughly 50-60MB and one MySQL connection for the length of the drain.

This is a **reference environment**, not a production topology -- it shows the shape a real deployment reproduces with its own load balancer and task scaling. Backpressure stays in the application, where a drain that can't get a [concurrency slot](/docs/queues/#concurrency-slots) answers `529` with a `Retry-After`, so it works the same however your edge is built.

### Updating Cerb

```
git stash

git pull origin --rebase

git stash pop
```

### Customizing the environment

`.env` holds ten values. Seven are set in the template; the three pool sizes are commented out at their defaults, so uncommenting one is how you change it.

| Variable | Default | What it does |
| --- | --- | --- |
| `CERB_ENV` | `demo` | Names this environment. The compose project becomes `cerb-<CERB_ENV>`, which prefixes every container, network, and volume |
| `CERB_PORT` | `80` | The port on **your machine** the web server answers on. Use something like `8080` if port 80 is taken |
| `MYSQL_DATABASE` | `cerb` | The database name |
| `MYSQL_USER` | `cerb` | The account Cerb connects with. Choose it before the first startup |
| `MYSQL_PASSWORD` | `s3cr3t` | That account's password. Rotating it later takes [two steps](#the-database-credentials) |
| `CERB_SERVICE_TOKEN` | `sk_docker_token` | A bearer token letting a caller outside Cerb reach privileged endpoints with no session – see below |
| `CERB_SERVICE_TOKEN_SCOPE` | `cron update` | Which endpoints that token may reach, space separated |
| `CERB_WEB_CHILDREN` | `6` | PHP-FPM children serving page loads |
| `CERB_BACKGROUND_CHILDREN` | `4` | PHP-FPM children serving `/queue` and `/cron` – see [background drains](#background-drains) |
| `CERB_NGINX_WORKERS` | `2` | nginx worker processes. `auto` gives one per core |

Changes take effect on the next `docker compose up`. **You shouldn't need to edit `docker-compose.yml`** for any of this. It's still where the un-parameterized things live – image versions, the service and volume definitions, adding a service of your own – but none of the settings above require touching it.

#### The database credentials

**Editing these after the first run changes what Cerb connects _with_, not what the database _has_.** MySQL creates the account during first-boot initialization only, and the database volume outlives the containers. Cerb's configuration is re-rendered on _every_ startup, so a password edited here reaches Cerb immediately while the database still expects the old one -- and Cerb stops connecting, with nothing visibly wrong in either file.

Choose `MYSQL_USER` and `MYSQL_DATABASE` before the first startup. Changing either on a running installation is a database migration rather than a setting, and it isn't something this file does for you.

**The password can be rotated**, in this order:

```
docker compose exec mysql mysql -u cerb -p
```

```
ALTER USER CURRENT_USER() IDENTIFIED BY 'newpassword';
```

Then set `MYSQL_PASSWORD` in `.env` to match, and run `docker compose up -d` so the new value reaches Cerb's configuration. Doing it the other way round points Cerb at a password the database doesn't have yet. Either order leaves a brief window where Cerb can't connect; this one keeps that window where you can see it.

`CURRENT_USER()` is the form to use because the account changes its own password. The database container generates a random root password at first boot that nobody has, so there's no administrative account to do it from.

#### The service token

`CERB_SERVICE_TOKEN` lets something outside Cerb – your host's `crontab`, a monitoring system – call `/cron` and `/update` without signing in. Send it as an `Authorization: Bearer <token>` header, or as an `_authorization` field on a POST. `CERB_SERVICE_TOKEN_SCOPE` limits what it may reach, and the default `cron update` is both of them.

**Nothing in these containers uses it.** There's no [scheduler](/docs/setup/configure/scheduler/) service in this stack, so a local evaluation copy works perfectly well if you ignore it. It's there for when you want to drive the scheduler from the host.

Change the token before exposing an installation to anything but your own machine. It's a master credential within its scope, and `sk_docker_token` is the value in the template everybody has.

### Running more than one environment

Two variables are all it takes to run several copies of Cerb side by side.

**`CERB_ENV`** names the compose project, which prefixes the containers (`cerb-demo-mysql-1`), the network (`cerb-demo_default`), and the volumes (`cerb-demo_db`, `cerb-demo_storage`). So two environments have **separate data**, not just separate names – neither can see the other's database or storage.

**`CERB_PORT`** covers the one thing compose can't namespace: the port on your own machine. `web` is the only service that publishes one.

So a second copy is a second clone with its own `.env`:

```
CERB_ENV=staging
CERB_PORT=8080
```

That gives you `cerb-staging-*` containers answering on `http://localhost:8080/`, alongside the default `cerb-demo-*` on port 80.

### Connecting to the MySQL console

```
docker compose exec mysql mysql -u cerb -p cerb
```

Run it from `install/docker/`. The password is whatever `MYSQL_PASSWORD` holds in your [`.env`](#customizing-the-environment), which is `s3cr3t` in the template.

Addressing the **service** (`mysql`) rather than a container name is deliberate. Container names carry the `CERB_ENV` prefix, so they change the moment you name an environment; `docker compose ps` lists the real ones. There's also no root password to use here -- the database container generates a random one at first boot, and `cerb` is the account Cerb itself connects with.

### Stopping the containers

To pause the containers, use the `Ctrl+C` keyboard shortcut or stop them from Docker Desktop.

Resume them later with `docker compose up --build` or the play button in Docker Desktop.

### Deleting containers

To delete the containers and their data, use the command:

```
docker compose down --volumes
```

[\< Installation](/docs/installation/)

[Guided Installer \>](/docs/guided-installer/)

