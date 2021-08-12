Security Policy
===============

If you believe you've found a security issue in [Cerb](https://cerb.ai/) or Cerb Cloud, please notify us privately at: [team@cerb.ai](mailto:team@cerb.ai)

You may optionally encrypt your report with our PGP key at: https://keybase.io/wgm

No technology is perfect, and we believe that working with skilled security researchers across the globe is crucial in identifying weaknesses in any technology.

## Disclosure Policy
* Please let us know as soon as possible upon discovery of a potential security issue, and we'll make every effort to quickly resolve the issue.
* Provide us with a reasonable amount of time to resolve the issue before any disclosure to the public or a third-party.
* Make a good faith effort to avoid privacy violations, destruction of data, and interruption or degradation of our service. Only interact with accounts you own or with explicit permission of the account holder.
* At our discretion, we reward security-related bug bounties based on severity and responsible disclosure.

## What to look for
* The usual culprits: XSS, CSRF, SQL injection, etc.
* Exfiltration of worker session cookies or CSRF tokens.
* Exfiltration of private information to unauthenticated third-parties (e.g. attachment contents, password hashes, OAuth tokens/credentials).
* Privilege escalation from worker accounts to administrator accounts.
* Authentication bypass in the web app or portals.
* Unexpected modification of records by unauthenticated third parties.

## Exclusions
While researching vulnerabilities, please refrain from:
* Denial of service
* Spamming
* Social engineering (including phishing) of Cerb staff, customers, or contractors
* Any physical attempts against Cerb property or data centers

## Out-of-scope
* Vulnerability reports from automated security scanners that lack a clearly defined security impact and proof of concept.
* Attacks that require direct access to the database.
* Unprotected web server access to the `/storage` directory due to neglecting our Security Best Practices <https://cerb.ai/docs/security/>.
* Reflected XSS in records, fields, and actions that only an administrator account could perform.
* XSS in any component that intentionally accepts HTML input from authenticated admin and worker accounts (e.g. automations, Custom HTML widgets on dashboards, conversational bots that respond with HTML/Javascript).

Thank you for helping keep Cerb and our users safe!