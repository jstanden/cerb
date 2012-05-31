# Create the default reply-to address
INSERT INTO address (id,email) VALUES (1,'do-not-reply@localhost');
INSERT INTO address_outgoing (address_id,is_default,reply_personal) VALUES (1,1,'');

# Set the helpdesk title
#INSERT INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','helpdesk_title','');

# Configure SMTP
INSERT INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','smtp_host','localhost');
INSERT INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','smtp_port','25');
INSERT INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','smtp_enc','None'); #TLS/SSL/None
## SMTP Authentication
INSERT INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','smtp_auth_enabled','0'); #0/1
#INSERT INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','smtp_auth_user','');
#INSERT INTO devblocks_setting (plugin_id, setting, value) VALUES ('cerberusweb.core','smtp_auth_pass','');

# Configure POP3/IMAP (pop3:110,pop3-ssl:995,imap:143,imap-ssl:993)
#INSERT INTO pop3_account (id, enabled, nickname, protocol, host, username, password, port) VALUES (1, 1, 'Dropbox', 'pop3', 'mail.example.com', 'user', 'password', 110);

# Default Role
INSERT INTO worker_role (id, name, params_json) VALUES (1, 'Default', '{"who":"all","what":"all"}');

# Default Groups
INSERT INTO worker_group (id, name, is_default) VALUES (1, 'Dispatch', 1);
INSERT INTO worker_group (id, name, is_default) VALUES (2, 'Support', 0);
INSERT INTO worker_group (id, name, is_default) VALUES (3, 'Sales', 0);

# Default Buckets
INSERT INTO bucket (id, group_id, name, is_assignable) VALUES (1, 1, 'Spam', 1);
INSERT INTO bucket (id, group_id, name, is_assignable) VALUES (2, 2, 'Spam', 1);
INSERT INTO bucket (id, group_id, name, is_assignable) VALUES (3, 3, 'Spam', 1);

# Add Superuser
INSERT INTO worker (id, title, email, pass, is_superuser, first_name, last_name) VALUES (1, 'Administrator', 'superuser@localhost', MD5('password'), 1, 'Super', 'User');
INSERT INTO address (id,email) VALUES(2,'superuser@localhost');
INSERT INTO address_to_worker (address, worker_id, is_confirmed) VALUES ('superuser@localhost', 1, 1);
## Preferences
INSERT INTO worker_pref (worker_id, setting, value) VALUES (1, 'timezone', 'America/Los_Angeles');
INSERT INTO worker_pref (worker_id, setting, value) VALUES (1, 'locale', 'en_US');
## Memberships
INSERT INTO worker_to_group (worker_id, group_id, is_manager) VALUES (1, 1, 1); # Dispatch manager
INSERT INTO worker_to_group (worker_id, group_id, is_manager) VALUES (1, 2, 1); # Support manager
INSERT INTO worker_to_group (worker_id, group_id, is_manager) VALUES (1, 3, 1); # Sales manager

# Scheduler defaults
## cron.pop3
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.pop3', 'enabled', '1');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.pop3', 'duration', '5');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.pop3', 'term', 'm');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.pop3', 'lastrun', '0');
## cron.parser
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.parser', 'enabled', '1');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.parser', 'duration', '1');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.parser', 'term', 'm');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.parser', 'lastrun', '0');
## cron.maint
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.maint', 'enabled', '1');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.maint', 'duration', '24');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.maint', 'term', 'h');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.maint', 'lastrun', '0');
## cron.heartbeat
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.heartbeat', 'enabled', '1');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.heartbeat', 'duration', '5');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.heartbeat', 'term', 'm');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.heartbeat', 'lastrun', '0');
## cron.search
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.search', 'enabled', '1');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.search', 'duration', '15');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.search', 'term', 'm');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.search', 'lastrun', '0');
## cron.mail_queue
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mail_queue', 'enabled', '1');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mail_queue', 'duration', '1');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mail_queue', 'term', 'm');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.mail_queue', 'lastrun', '0');
## cron.virtual_attendant.scheduled_behavior
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.virtual_attendant.scheduled_behavior', 'enabled', '1');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.virtual_attendant.scheduled_behavior', 'duration', '1');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.virtual_attendant.scheduled_behavior', 'term', 'm');
REPLACE INTO cerb_property_store (extension_id, property, value) VALUES ('cron.virtual_attendant.scheduled_behavior', 'lastrun', '0');

# Default Organization
INSERT INTO contact_org (id, name, street, city, province, postal, country, phone, website, created) VALUES (1, 'WebGroup Media, LLC.', 'PO BOX 1206', 'Brea', 'California', '92822', 'USA', '+1 714-224-2254', 'http://www.cerberusweb.com/', UNIX_TIMESTAMP());

# Custom Fields
# [TODO]

# Virtual Attendants
# [TODO]

# Support Center portal
# [TODO]

# Default Ticket
# [TODO]