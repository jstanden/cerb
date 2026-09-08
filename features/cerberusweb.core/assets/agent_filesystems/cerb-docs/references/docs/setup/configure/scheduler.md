---
id: "docs-setup-configure-scheduler"
title: "Scheduler"
url: "https://cerb.ai/docs/setup/configure/scheduler/"
summary: "This page documents Cerb's scheduler -- the system that runs background jobs like mailbox checking, search indexing, nightly maintenance, the parallel queue, automation timers, and reminders. It explains how jobs are scheduled, locked while running, and manually invoked from the UI; how to wire up a cronjob or scheduled task to request /cron every minute; how /cron is authenticated with service tokens; and provides a reference table of every built-in cron job with its default interval."
tags: ["docs"]
---
Deployment options are split into **Development** and **Production**. Development offers an **Auto-Run** option that runs jobs entirely in the browser, with a countdown ring showing the next interval. Production shows an example `/cron` request using a [service token](/docs/records/types/service_token/). Each job displays its run count and duration [metrics](/docs/metrics/), and the **edit** and **run now** actions open in a popup.

 

The **scheduler** is responsible for planning and running _jobs_.

A **job** is an automated background task: checking your mailboxes for new messages, search indexing new records, performing nightly maintenance, draining the [parallel queue](/docs/queues/), triggering [automation](/docs/automations/) timers, etc. There are several built-in jobs (listed below), and [plugins](/docs/plugins/) can add new ones.

Each job is repeated at a specific _interval_ – a number of minutes, hours, or days. A job can be _disabled_ to prevent it from running.

Different jobs can run at the same time. A job is _locked_ while running to prevent multiple copies of itself from starting.

A job's extension manifest can flag it as **parallel**. Parallel jobs limit concurrency through reserved [queue slots](/docs/queues/#concurrency-slots) rather than a hard lock, so multiple invocations can overlap when capacity is available. The built-in [Background Queue](/docs/queues/#background-queue-scheduler) runs this way – it can fan out across slots to drain work in near real time rather than once per minute. Traditional locked jobs remain available for tasks that must not overlap (e.g. mailbox polling).

A parallel job draws from the [slow lane](/docs/queues/#lanes), since it holds a slot for as long as its work takes. There's no per-job concurrency setting: a parallel job takes a slot like any other long-running drain and runs as fast as it can. A job that's currently running concurrently shows a countdown ring of its own rather than a fixed count, but it's excluded from the page's **next to fire** chip – a job that drains as fast as it can has no cadence to be next on.

Each job has a _"run now"_ link that will immediately run the job with logging enabled from inside your web browser. This is useful for troubleshooting and development, but the scheduler should be automated in production environments so that the jobs run without human intervention. **Run now** on a parallel job takes a real slot, so it reports that every slot is busy rather than starting work that has nowhere to run.

- [Automating /cron](#automating-cron)
  - [Using curl](#using-curl)
  - [Using wget](#using-wget)
  - [Adding to crontab](#adding-to-crontab)
  - [Best practices](#best-practices)

- [Built-in jobs](#built-in-jobs)
- [References](#references)

# Automating /cron

For Cerb's scheduled jobs to automatically run in the background, you need to configure a third-party tool to request the `/cron` page every minute. On Unix-based systems this is accomplished with a cronjob[1](#fn:cronjob). On Windows Server you can add a Scheduled Task[2](#fn:windows-scheduled-task).

If you're using **Cerb Cloud**, we handle this for you.

We recommend using **curl** or **wget** to request your scheduler URL every minute.

The `/cron` page doesn't require a worker login. It is authenticated with [service tokens](/docs/records/types/service_token/) – manage them at [Setup » Configure » Security](/docs/setup/configure/security/). The legacy `AUTHORIZED_IPS_DEFAULTS` IP allowlist has been deprecated.

Create a token scoped to `cron:*` (or narrower, like `cron:maint`) so the cronjob can't authenticate against `/debug` or `/update`. Then use one of the examples below.

## Using curl

```
curl --silent --show-error --fail --max-time 60 \
  --header "Authorization: Bearer ${CERB_SERVICE_TOKEN}" \
  --output /dev/null \
  https://cerb.example.com/cron
```

The flags do the following:

| Flag | Purpose |
| --- | --- |
| `--silent --show-error` | Suppress progress output, but still print errors to stderr so cron can email you |
| `--fail` | Treat non-2xx HTTP responses as failures (returns a non-zero exit code) |
| `--max-time 60` | Abort after 60 seconds so a stuck request doesn't pile up minute-after-minute |
| `--header "Authorization: Bearer …"` | Authenticate with a [service token](/docs/records/types/service_token/) |
| `--output /dev/null` | Discard the response body – cron only cares about the exit code |

## Using wget

```
wget --quiet --tries=1 --timeout=60 \
  --header="Authorization: Bearer ${CERB_SERVICE_TOKEN}" \
  --output-document=/dev/null \
  https://cerb.example.com/cron
```

`--quiet` silences successful runs, `--tries=1` prevents automatic retries (Cerb will pick the work back up on the next minute anyway), and `--timeout=60` caps the request.

## Adding to crontab

Edit the crontab with `crontab -e` and add a single line that runs every minute:

```
MAILTO=admin@example.com
CERB_SERVICE_TOKEN=cerb_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Cerb scheduler
* * * * * curl -sSf --max-time 60 -H "Authorization: Bearer $CERB_SERVICE_TOKEN" -o /dev/null https://cerb.example.com/cron
```

By **not** redirecting stderr (no trailing `2>&1 > /dev/null`), cron will email `MAILTO` whenever the request fails. If you'd rather log to a file instead, append:

```
* * * * * curl -sSf --max-time 60 -H "Authorization: Bearer $CERB_SERVICE_TOKEN" -o /dev/null https://cerb.example.com/cron >> /var/log/cerb-cron.log 2>&1
```

…and rotate the log with `logrotate`.

## Best practices

- **Don't use the master `APP_SERVICE_TOKEN` for cron.** Create a dedicated [service token](/docs/records/types/service_token/) per host or cronjob with a narrow scope like `cron:*`. You can revoke individual tokens without disrupting other automation, and the [`cerb.service.token.uses`](/docs/metrics/cerb.service.token.uses/) metric lets you audit each token's traffic.
- **Always use HTTPS.** A service token is a credential – it shouldn't traverse the network in cleartext.
- **Cap the request with a timeout.** A stuck PHP-FPM worker can cause minute-by-minute pile-up otherwise; both `curl --max-time` and `wget --timeout` prevent this.
- **Let cron mail you on failure.** Set `MAILTO=` at the top of the crontab and resist the urge to `2>&1` everything into `/dev/null` – silent failures are the worst kind.
- **Don't add a lock file.** Cerb's scheduler locks each [job](#built-in-jobs) internally, so overlapping `/cron` requests are safe – the second request will just skip any in-flight job.
- **Behind a load balancer**, point cron at a specific internal node (e.g. `https://cerb-internal-01.example.com/cron`) rather than the public hostname. This avoids unnecessary edge/SSL termination cost for traffic you control.

# Built-in jobs

| Job | Default interval | Description |
| --- | --- | --- |
| `cron.automations` | 1 minute | Runs `cron.maint` and `cron.heartbeat` [automation events](/docs/records/types/automation_event/), and dispatches due [automation timers](/docs/records/types/automation_timer/). |
| `cron.background_queue` | 1 minute | Drains the [parallel background queue](/docs/queues/#background-queue-scheduler) – `record.changed` events, [queue jobs](/docs/records/types/queue_job/), metrics, and worker-initiated jobs whose monitors are no longer connected. |
| `cron.heartbeat` | 1 minute | Fires the `cron.heartbeat` event for automations that need to run on a regular cadence. |
| `cron.mail_queue` | 1 minute | Processes outbound email in the mail queue. |
| `cron.mailbox` | 1 minute | Connects to configured [mailboxes](/docs/records/types/mailbox/) and downloads new messages. |
| `cron.maint` | daily | Nightly maintenance – pruning expired records, clearing watcher links on deactivated workers, etc. Also fires the `cron.maint` event. |
| `cron.packages` | 1 minute | Imports queued [packages](/docs/packages/) (e.g. plugin-provided or admin-installed templates). |
| `cron.parser` | 1 minute | Parses inbound email messages into [tickets](/docs/records/types/ticket/), running [`mail.filter`](/docs/automations/events/mail.filter/) automations. |
| `cron.reminders` | 1 minute | Dispatches due [reminders](/docs/records/types/reminder/). |
| `cron.search` | 5 minutes | Updates [search indexes](/docs/records/types/search_index/) with newly created or modified records. |
| `cron.storage` | daily | Moves attachments between configured storage profiles based on age. |

Disabled jobs are skipped until re-enabled.

# References

1. https://en.wikipedia.org/wiki/Cron&nbsp;[↩](#fnref:cronjob)

2. https://technet.microsoft.com/en-us/library/cc748993.aspx&nbsp;[↩](#fnref:windows-scheduled-task)

