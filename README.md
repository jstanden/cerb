![image](https://cerb.ai/assets/cerb_logo.svg)

# What is Cerb?

**Cerb** is a fully customizable, web-based platform for enterprise communication and process automation. The project has continuously evolved for 22+ years based on the feedback of thousands of teams around the world in almost every industry. It is used by everyone from solo founders to 1,000+ person teams managing millions of customer requests.

Cerb integrates with any API-based service. It can automate nearly any repetitive digital workflow with its specialized [KATA](https://cerb.ai/docs/kata/) language and browser-based coding tools. [Automations](https://cerb.ai/docs/automations/) add conditional actions to any event. [Interactions](https://cerb.ai/docs/interactions/) extend any toolbar for complex multistep workflows that require user input.

Teams and individuals can build a personalized "mission control" using highly customizable workspace widgets to stay focused on their most important tasks. Custom records and fields organize of any kind of task (email, calls, social media, orders, survey responses, todo, etc). Pre-built solutions can be easily shared within the community using [packages](https://cerb.ai/docs/packages/).

The most common use case is converting standard POP3/IMAP mailboxes (e.g. `support@`, `team@`) into high-volume team-based webmail with automated triage, internal discussions with `@mentions`, built-in productivity tools, custom actions, reporting, and a shared history.

An emerging use case is integrating with large language models (LLMs) to automatically suggest answers to customer requests based on existing team knowledge (documentation, FAQs, articles).

![image](https://cerb.ai/assets/images/home/dashboards.png)

# Installation

Cerb can be [installed](https://cerb.ai/docs/installation/) on your own hardware or deployed as a fully managed service in [Cerb Cloud](https://cerb.ai/cloud/).

## Cerb Cloud

**Cerb Cloud** is a subscription-based service that provides a finely tuned, ready-to-use instance of Cerb in an ideal environment. All you need is a web browser and your team can start putting Cerb’s tools to work. We’ll handle everything else.

* **Fully managed:** We install Cerb and its dependencies in an ideal environment, apply updates and security patches, monitor and scale the infrastructure, optimize performance, maintain backups, interface with email service providers for deliverability, provide application support and other technical services, and everything else. You can focus on what you do best.

* **Highly available:** Failed components are automatically replaced and redundant capacity allows your service to continue uninterrupted. The Enterprise tier provides a database cluster with near-instant automated failover, and the other tiers recover from database failures automatically within minutes.

* **Scalable:** Your Cerb environment can scale seamlessly from a single worker who sends a couple of messages per day, to hundreds of concurrent workers with a history spanning millions of conversations. Resources can seamlessly “scale up” and “scale out”. New resources are automatically provisioned and added to load balancers in response to traffic needs (web servers, cache servers, incoming and outgoing mail servers, etc).

* **High performing:** Cerb is already designed to be fast and efficient. Cerb Cloud further accelerates performance by optimizing the underlying infrastructure and taking advantage of distributed services in the cloud. The database is continuously tuned for your workload. Resource requests (images, scripts, stylesheets, and fonts) are served instantly from a memory cache. Frequently accessed application data is retrieved from a memory-based cache cluster to reduce database latency. Background jobs are managed by an automated scheduler.

* **Secure:** All traffic between you and your Cerb instance is encrypted with SSL. We support “Perfect Forward Secrecy”, which is a strategy that protects your past encrypted transmissions even if they are intercepted and recorded (even we can’t decrypt them once your session ends). Our resources operate in a “private cloud” with private networks for traffic between components, and firewall rules in front of public components that expose a minimally necessary attack surface. Our own access to those resources requires RSA keys and two-factor authentication.

* **Durable:** We archive a sequence of full daily database backups, as well as the incremental point-in-time changes in between. Long term object storage (like attachments) are redundantly stored in several geographically separate locations. We can also arrange for backups to be routinely transfered to you.

You can [sign up](https://cerb.ai/cloud/) for a free Cerb Cloud trial with no time limit. 

We provide Cerb Cloud SMTP (outbound) and MX (inbound), or you can bring your existing email accounts (Gmail, Office365, etc). You can also bring your own domain (e.g. `support.example.com`).

You can choose to store your data in one of three self-contained regions: U.S., Europe (Frankfurt), or Asia Pacific (Sydney).

At any time, your data in Cerb Cloud can be migrated to a different region, or to a self-hosted environment.

## Evaluation and local development with Docker

Cerb ships with a Docker configuration for local evaluation, development, and testing. This creates preconfigured containers for Nginx (web server), PHP/FPM (code), and MySQL (database). By default, data is stored in two volumes (one for the database and the other for the `./storage/` directory). A virtual network is created to connect the containers.

First, make sure [Docker Desktop](https://www.docker.com/products/docker-desktop/) is installed.

Navigate to the directory where you want to install a copy of Cerb. Then run the following commands:

```shell
git clone -b v10.4 https://github.com/cerb/cerb-release.git v10.4

# ... or download + unzip: https://github.com/cerb/cerb-release/archive/refs/heads/v10.4.zip

cd v10.4

cd install/docker

docker compose up
```

It will take a few minutes to build the container images the first time you run them. Afterward, the containers will start almost instantly.

Once the containers are running, open your browser to: `http://localhost/`

The guided installer will finish installing Cerb based on your needs. For testing we recommend disabling outbound email when prompted (this can always be re-enabled later). At the end of the installer you can also choose "Demo" mode to have test data to experiment with.

If you're already using port `80` for a different project, you can bind Cerb to a different port (e.g. `8080`) by editing the `docker-compose.yml` file before running `docker compose up`.

```yaml
services:
  web:
    image: nginx:latest
    ports:
      - "8080:80"
...
```

To connect the MySQL console:

```shell
docker exec -it cerb-mysql-1 mysql -u root -p cerb
```

The default password is `s3cr3t`.

You can edit files in your local filesystem and the changes will be reflected instantly within the containers.

To pause the containers, use the `Ctrl+C` keyboard shortcut or stop them from Docker Desktop. Resume them later with `docker compose up` or the play button in Docker Desktop.

To delete the containers and their data, use the command:

```shell
docker compose down --volumes
```

## Deploying Cerb in production

The [basic installation guide](https://cerb.ai/docs/installation/) walks through the steps to install Cerb's components on a single server. This will be performant enough for dozens of concurrent workers, but it will not be resilient to failure.

In mission-critical environments we recommend using highly available and scalable architecture to separate each component and add redundancy.

For example, in Amazon Web Services (AWS) you'd use EC2 or ECS/Fargate for nginx and PHP/FPM containers, ALB for load balancing, RDS/Aurora for the database server cluster, Elasticache for memcached, EFS for a shared `./storage` mount between containers, S3 for long-term storage/backups, and CloudWatch for monitoring.

If you do not have experience with deploying scalable web apps, we strongly recommend that you use Cerb Cloud.

# Getting started

Read the [documentation](https://cerb.ai/docs/) to get started.

# License

The software is distributed under the [Devblocks Public License](https://cerb.ai/license) as a commercial open source project. The full source code is publicly available on GitHub.

Licenses are based on the maximum number of workers able to log in at the same time (i.e. seats). The software can be deployed on independent servers, or as a fully-managed, cloud-based service.

# Credits

Cerb is developed in the PHP programming language. Relational data is stored in MySQL. See the [credits](https://cerb.ai/docs/credits) for a full list of third-party libraries, resources, and contributors.