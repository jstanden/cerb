---
id: "tips-recover-admin-account"
title: "Recover your administrator account"
url: "https://cerb.ai/tips/recover-admin-account/"
summary: "This page provides a step-by-step guide on how to recover an administrator account in Cerb if you become locked out, such as when an LDAP server is unavailable. It explains how to switch back to password authentication directly from the database by connecting to the database, identifying the administrator's worker ID, and updating the authentication settings. It also includes instructions for resetting the password and clearing the server-side cache. For Cerb Cloud users, it advises contacting support for assistance."
tags: ["tips"]
---
In rare situations, it's possible to become locked out of your administrator account in Cerb. For example, if you authenticate against an LDAP server that becomes unavailable then you won't be able to log in.

In this situation, you can recover your administrator account by switching back to password authentication directly from the database.

Connect to your database from the console or a tool like phpMyAdmin.

Find your administrator's worker ID:

```
SELECT id, CONCAT_WS(' ',first_name,last_name) AS name FROM worker;
```

Let's assume your ID is `123`.

Change back to password authentication for your account:

```
UPDATE worker SET is_password_disabled = 0 WHERE id = 123;
```

You can also reset your password if needed (replace `s3cr3t` below with your new password):

```
UPDATE worker_auth_hash SET pass_hash = SHA1(CONCAT(pass_salt,MD5('s3cr3t'))) WHERE worker_id = 123;
```

You then need to [clear the server-side cache](/tips/clear-server-cache/).

If you use Cerb Cloud, [contact us](/help/#email) to resolve this issue for you.

