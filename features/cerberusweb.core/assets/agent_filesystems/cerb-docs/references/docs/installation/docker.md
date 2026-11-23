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
    - [Opening the installer](#opening-the-installer)
    - [Enabling HTTPS](#enabling-https)
    - [After the installer](#after-the-installer)
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

https://www.youtube.com/embed/zxWRZWq1tyM

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

cp .env-template .env

docker compose up
```

**Don't skip the copy.** There is no `.env` in a fresh clone -- it's ignored by git, and `.env-template` is the only place `CERB_INSTALL=yes` is set. Without it `CERB_INSTALL` defaults to an empty string, and Caddy rewrites `/install/index.php` to `/index.php`. The containers come up perfectly and the installer is simply unreachable, with nothing on screen explaining why.

Note the spelling: this repository uses `.env-template` with a **hyphen**. The [source installation](#option-2-github-development-testing) below uses `.env.template` with a dot.

Every service here pulls a pre-built image, so there's nothing to compile and `docker compose up` needs no `--build`.

`docker compose up` stays in the foreground, streaming interleaved logs from every container, so the terminal it runs in is occupied until you stop it. `Ctrl+C` gets your prompt back by stopping the stack. Add `-d` to start it in the background instead and keep the shell:

```
docker compose up -d
```

The `cerb` container listens on port `:9000` using FastCGI and **must not** be exposed to the public network. The container is configured using environment variables:

| ENV | &nbsp; |
| --- | --- |
| `CERB_AUTHORIZED_IPS` | A comma-separated list of IPv4 addresses or subnets that can access `/cron` and `/update` without a session. |
| `CERB_INSTALL` | If `yes` the `/install/` path is available; otherwise it is blocked. The `cerb-caddy-mysql` example ships it as `yes` so you can reach the installer – [turn it off afterward](#after-the-installer). |
| `MYSQL_HOST` | The database server hostname. This is created for you in the `cerb-caddy-mysql` example, but should be a dedicated server like Amazon RDS in production. |
| `MYSQL_DATABASE` | The database name. Use `CREATE DATABASE cerb CHARACTER SET utf8mb4` if using an external database server. |
| `MYSQL_USER` | The database user with `GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES`. |
| `MYSQL_PASSWORD` | The database user's password. |

The container uses the `/mnt/storage/` path for persistent data. In the example a local Docker volume binds to the path, but in production you should bind a shared filesystem like Amazon EFS, NFS, or GlusterFS. This allows you to scale containers horizontally.

The cerb-caddy-mysql example above provides additional configuration options:

| ENV | Default | Notes |
| --- | --- | --- |
| `CERB_ENV` | `docker` | Names this environment. The compose project becomes `cerb-<CERB_ENV>`, which prefixes every container, network, and volume – so it decides the names `docker compose ps` reports, and it's what lets two copies share a machine |
| `CERB_VERSION` | `12.0` | The Cerb versioned image tag to use. Use tags like `12` for 12.x, `12.0` for 12.0.x, or `12.0.0` for a specific version. The `latest` tag always points to the latest stable version |
| `CERB_HOSTNAME` | `localhost` | The hostname Caddy answers on. With the default `Caddyfile` this is served over plain HTTP – see [enabling HTTPS](#enabling-https) |
| `CERB_PORT` | `80` | The HTTP port. This should be `80` in production, but can be something like `8080` for testing |
| `CERB_PORT_SSL` | `443` | The HTTPS/TLS port. **Published on your machine whether or not HTTPS is enabled** – see below |
| `CERB_SERVICE_TOKEN` | `s3cr3t` | A bearer token letting a caller outside Cerb reach `/cron` and `/update` with no session. **Change it** before the installation is reachable by anything but your own machine |
| `MYSQL_VERSION` | `8.4` | The `mysql` image tag |
| `CADDY_VERSION` | `latest` | The `caddy` image tag. `latest` floats, so a later `docker compose pull` can move it |

**Port 443 is claimed even though nothing serves it.** `docker-compose.yml` publishes both `${CERB_PORT}:80` and `${CERB_PORT_SSL}:443` unconditionally, while the default `Caddyfile` listens on `:80` only. If something else on your machine already holds `443`, `docker compose up` fails to bind a port this configuration never uses. Change `CERB_PORT_SSL` to get past it -- you don't have to want HTTPS to need that setting.

A `docker-compose.yml` file is provided for reference and testing. In production, you should use a container orchestration service like Amazon ECS/Fargate, Google Cloud Run, Azure Containers, or Kubernetes.

You can use the cerb example to build your own customized image.

### Opening the installer

Once the containers are running, open your browser to: `http://localhost/`

That's plain HTTP with no certificate to accept. **Give it a few seconds if the first load errors.** Every time you start from an empty database volume, the web server answers `500` for a second or two while MySQL initializes, and then redirects normally – there's no health check gating it. Slower hardware takes longer. While the database is empty Cerb redirects you to the [guided installer](/docs/guided-installer/) from any location, so you don't need to type `/install/` yourself. If you changed `CERB_PORT`, use `http://localhost:<CERB_PORT>/`.

### Enabling HTTPS

The default `Caddyfile` opens its site block with an explicit `http://` scheme, so Caddy neither provisions a certificate nor redirects to one. That's deliberate – it keeps self-signed certificate warnings out of local development.

To serve HTTPS instead, copy `caddy/Caddyfile.ssl.example` over `caddy/Caddyfile` and restart the `caddy` container. Caddy then obtains a LetsEncrypt certificate for a hostname that resolves publicly, or issues one from its own internal CA for a local hostname – and it's that second case your browser warns about. For LetsEncrypt, be sure DNS for `CERB_HOSTNAME` points at your server and that ports `:80` and `:443` are reachable from the public network.

### After the installer

The example ships `CERB_INSTALL=yes` so that the installer is reachable at all. Once you've finished installing, comment it out in `.env` and restart:

```
#CERB_INSTALL=yes
```

```
docker compose up -d
```

Leaving `CERB_INSTALL=yes` set on a public-facing installation keeps the `/install/` path served, and it exposes sensitive information about your environment. Turn it off before anyone but you can reach the site.

The installer's own closing advice – _"Delete the install directory before going to production"_ – **can't be followed on this path.** The `install` directory is inside the container image rather than on your disk, since the `cerb` service mounts only the storage volume and no code. Turning `CERB_INSTALL` off is the equivalent, and it's verifiable: with it set, `/install/index.php` answers `200`; with it commented out and the containers restarted, it answers `302` to the login page.

Change `CERB_SERVICE_TOKEN` at the same time if you haven't. The example ships `s3cr3t`, which is also the default `MYSQL_PASSWORD` – two different credentials, both published, both the same word.

### Running the scheduler in the container from the host

You can use `docker exec` to ping the scheduler in the container.

```
docker exec cerb-docker-caddy-1 wget -O - --header "Host: localhost" "https://localhost/cron?loglevel=7&ignore_wait=1"
```

You can add this to `crontab` on the host.

## Option 2: GitHub (Development, Testing)

The Cerb source code ships with a Docker configuration for local evaluation, development, and testing. It defines five services:

| Service | Image | What it does |
| --- | --- | --- |
| `web` | `nginx:latest` | The web server. The only service that publishes a port on your machine |
| `cerb-config` | Built locally | Renders `framework.config.php` at startup, then exits |
| `php-fpm` | Built locally | Serves page loads |
| `php-fpm-background` | Built locally | Serves `/queue` and `/cron` – see [background drains](#background-drains) |
| `mysql` | `mysql:8.4` | The database |

`cerb-config` does its work once and stops, so `docker compose ps` doesn't list it at all. `docker compose ps -a` shows it as `Exited (0)`, which is what success looks like for this service rather than a failure.

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

`docker compose up` stays in the foreground, streaming interleaved logs from every container, so the terminal it runs in is occupied until you stop it. `Ctrl+C` gets your prompt back by stopping the stack. Add `-d` to start it in the background instead and keep the shell:

```
docker compose up -d
```

Copying `.env.template` is **optional** here, unlike the [Docker Hub example](#option-1-docker-hub-production) above. Every value it holds has the same default built into `docker-compose.yml`, so `docker compose up --build` works on a fresh clone with no `.env` at all. What the file gives you is one place to record what you changed – and it's what lets [more than one copy of Cerb](#running-more-than-one-environment) share a machine. It's ignored by git, so your settings survive a `git pull`.

Once the containers are running, open your browser to: `http://localhost/`

Browsing there redirects you to the [guided installer](/docs/guided-installer/), which finishes installing Cerb based on your needs. You don't need to type `/install/` yourself. There's nothing to configure for outgoing mail – a `Null` mail transport is always installed, and you connect a real one afterward. On the **Packages** step you can choose **Demo / Development** to have test data to experiment with.

### Background drains

`/queue` and `/cron` route to a `php-fpm-background` container running a PHP-FPM pool tuned for long holds, while `php-fpm` serves everything else.

One background request can hold a child for the length of an [AI agent](/docs/agents/) turn or a [queue](/docs/queues/) drain, so keeping the two pools apart is what stops a busy queue from making the interface feel slow.

Scale the drain capacity with:

```
docker compose up --scale php-fpm-background=3
```

Three variables size these tiers. They're already in [`.env`](#customizing-the-environment), commented out at their defaults:

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

`.env` holds ten values. Seven are set in the template; the two FPM pool sizes and the nginx worker count are commented out at their defaults, so uncommenting one is how you change it. Those three aren't next to each other in the file – two sit above the database block and one below it.

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

