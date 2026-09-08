---
id: "docs-plugins"
title: "Plugins"
url: "https://cerb.ai/docs/plugins/"
summary: "This page provides a comprehensive guide on using plugins in Cerb to enhance and customize its functionality without conflicting with the core platform updates. It explains the structure and components of plugins, including IDs, manifests, extensions, events, patches, classloader, permissions, translations, resources, templates, and activity points. The page details how plugins can integrate with third-party services, add new record types, augment automations, and expand workspaces and dashboards. It also covers the requirements and dependencies for plugins, how to register extensions, and the use of the Smarty template engine for plugin templates. Additionally, it outlines the library of features, authentication, integration, legacy components, localization, record types, and storage options available through plugins. The guide emphasizes the importance of using plugins to maintain simplicity and efficiency in Cerb while allowing for extensive customization and functionality expansion."
tags: ["docs"]
---
While Cerb's source code is 100% public, any customizations you make to the platform itself will likely _"conflict"_ with ongoing improvements made by the official developers. This makes it more difficult for you to [upgrade](/docs/upgrading/).

You can avoid these issues by using **plugins** – optional bundles of files that seamlessly contribute new functionality to Cerb.

Even the built-in functionality in Cerb is contributed by plugins. This way, as we continue to improve Cerb, we're also automatically expanding the ability for other people to build their own customizations too.

Common uses for plugins are:

- Integration with [third-party services](/docs/connected-services/)
- Adding new [record types](/docs/records/)
- Augmenting [automations](/docs/automations/) with new events and commands
- Expanding [workspaces](/docs/workspaces/) and dashboards with new widgets and data sources
- …and much more

Plugins also allow unused functionality to be removed to keep everything simpler and more efficient.

- [IDs](#ids)
- [Structure](#structure)
- [Manifests](#manifests)
  - [Plugin metadata](#plugin-metadata)
  - [Requirements](#requirements)
  - [Dependencies](#dependencies)
  - [Everything else](#everything-else)

- [Extensions](#extensions)
  - [Extension points](#extension-points)

- [Events](#events)
- [Patches](#patches)
- [Classloader](#classloader)
- [Permissions](#permissions)
- [Translations](#translations)
- [Resources](#resources)
- [Templates](#templates)
- [Activity Points](#activity-points)
- [Library](#library)
  - [Active](#active)
  - [Deprecated](#deprecated)

### IDs

Every plugin must have a unique ID comprised of lowercase letters (`a-z`), numbers (`0-9`), underscores (`_`), and dots (`.`).

By convention, the first segment of a plugin's ID is a namespace unique to its author. One way to ensure uniqueness is to base your namespace on a domain name you own.

For instance: `com.example.plugin_name`

### Structure

Every plugin is a directory with the same name as its ID, using the following filesystem structure:

| Path | Description |
| --- | --- |
| **`api/`** | [Extensions](/docs/plugins/#extensions) |
| **`patches/`** | [Patches](/docs/plugins/#patches) |
| **`resources/`** | [Resources](/docs/plugins/#resources) (images, scripts, stylesheets) |
| **`templates/`** | [Templates](/docs/plugins/#templates) |
| `plugin.xml` | [Manifest](/docs/plugins/#manifests) |
| `strings.xml` | [Translations](/docs/plugins/#translations) |

The minimal set of plugins required for Cerb to work properly are called **features**. You'll find them in the `features/` directory.

Third-party plugins are found in the `storage/plugins/` directory. These plugins are installed and automatically updated from the Plugin Library.

# Manifests

Each plugin must have a **manifest** file named `plugin.xml` that describes its contents. This tells Cerb what kinds of new functionality the plugin is contributing.

Here's a minimal manifest:

```
<?xml version="1.0" encoding="UTF-8"?>
<plugin xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.devblocks.com/schema/plugin.xsd">
	<id>example.plugin</id>
	<name>Plugin Name</name>
	<description>This explains what your plugin does.</description>
	<author>Webgroup Media, LLC.</author>
	<version>0.0.0</version>
	<link>https://cerb.example/path/to/docs</link>
	<image>plugin.png</image>

	<requires>
		<app_version min="11.0" max="11.0.99" />
		<!--<php_extension name="curl" />-->
	</requires>

	<dependencies>
		<require plugin_id="cerberusweb.core" version="11.0.0" />
	</dependencies>

	<patches/>
	<class_loader/>
	<event_points/>
	<acl/>
	<activity_points/>
	<extensions/>
</plugin>
```

### Plugin metadata

- **`<id>`** is the globally unique [ID](/docs/plugins/#ids) of the plugin, prefixed with the author's namespace. This should only contain lowercase letters (`a-z`), numbers (`0-9`), underscore (`_`), and dots (`.`).

- **`<name>`** is the human-friendly name of the plugin.

- **`<description>`** is a brief description of the purpose and contributions of the plugin.

- **`<author>`** is the name used for attribution of the plugin's author.

- **`<version>`** is the semantic version of the plugin in `<generation>.<major>.<minor>` format. This should start with `0.0.0` and be incremented for each update.

- **`<link>`** is a URL to the plugin's documentation page.

- **`<image>`** is a path to the plugin's icon. This is relative to the plugin's `resources/` directory.

### Requirements

The **`<requires>`** block specifies the requirements for installing and enabling this plugin.

This block must contain one **`<app_version>`** element specifying the minimum and maximum version of Cerb that are verified compatible with this build of the plugin.

The block may contain any number of **`<php_extension>`** elements if specific PHP extensions are required for the plugin to operate (e.g. `ldap`, `oauth`, `zip`).

### Dependencies

The optional **`<dependencies>`** block specifies if this plugin depends on another plugin.

This block may contain any number of **`<require>`** elements specifying a required `plugin_id` and its minimum compatible `version`.

### Everything else

The other elements will be covered in more detail in the subsequent sections:

- [**`<extensions>`**](/docs/plugins/#extensions)
- [**`<event_points>`**](/docs/plugins/#events)
- [**`<patches>`**](/docs/plugins/#patches)
- [**`<class_loader>`**](/docs/plugins/#classloader)
- [**`<acl>`**](/docs/plugins/#permissions)

# Extensions

Plugins contribute new functionality by registering **extensions** on **extension points**.

Extensions are defined in a plugin's [manifest](/docs/plugins/#manifests) within the **`<extensions>`** block.

Each extension entry looks like:

```
<extension point="com.example.extension_point">
	<id>com.example.extension_name</id>
	<name>Extension name</name>
	<class>
		<file>relative/path/to/file.php</file>
		<name>Class_Name</name>
	</class>
	<params/>
</extension>
```

- **`<extension point="...">`** specifies the [extension point](#extension-points) of the extension.

- **`<id>`** is the globally unique ID of the extension. Like plugins, this should only contain lowercase letters (`a-z`), numbers (`0-9`), underscores (`_`), and dots (`.`). The extension ID should always start with the [ID](/docs/plugins/#ids) of the plugin.

- **`<name>`** is the human-friendly name of the extension.

- **`<class>`** assigns code from the plugin to the extension. Each extension point provides a parent _class_ which must be _extended_ by the plugin's extension. The **`<name>`** element in this block specifies the class name of this implementation, and **`<file>`** is the path to a source code file, relative to the plugin's directory. This almost always starts with `api/`.

- **`<params>`** is where each extension manifest can set configuration details based on the extension point.

## Extension points

| Name | Extension Point |
| --- | --- |
| [Automation Trigger](/docs/plugins/extensions/points/cerb.automation.trigger/) | `devblocks.event.action` |
| [Bot Action](/docs/plugins/extensions/points/devblocks.event.action/) | `devblocks.event.action` |
| [Bot Event](/docs/plugins/extensions/points/devblocks.event/) | `devblocks.event` |
| [Cache Engine](/docs/plugins/extensions/points/devblocks.cache.engine/) | `devblocks.cache.engine` |
| [Calendar Datasource](/docs/plugins/extensions/points/cerberusweb.calendar.datasource/) | `cerberusweb.calendar.datasource` |
| [Card Widget Type](/docs/plugins/extensions/points/cerb.card.widget/) | `cerb.card.widget` |
| [Community Portal](/docs/plugins/extensions/points/cerb.portal/) | `cerb.portal` |
| [Connected Service Provider](/docs/plugins/extensions/points/cerb.connected_service.provider/) | `cerb.connected_service.provider` |
| [Controller](/docs/plugins/extensions/points/devblocks.controller/) | `devblocks.controller` |
| [Custom Field Type](/docs/plugins/extensions/points/cerb.custom_field/) | `cerb.custom_field` |
| [Event Listener](/docs/plugins/extensions/points/devblocks.listener.event/) | `devblocks.listener.event` |
| [Http Request Listener](/docs/plugins/extensions/points/devblocks.listener.http/) | `devblocks.listener.http` |
| [Mail Transport Type](/docs/plugins/extensions/points/cerberusweb.mail.transport/) | `cerberusweb.mail.transport` |
| [Page Menu Item](/docs/plugins/extensions/points/cerberusweb.ui.page.menu.item/) | `cerberusweb.ui.page.menu.item` |
| [Page Section](/docs/plugins/extensions/points/cerberusweb.ui.page.section/) | `cerberusweb.ui.page.section` |
| [Page Type](/docs/plugins/extensions/points/cerberusweb.page/) | `cerberusweb.page` |
| [Prebody Renderer](/docs/plugins/extensions/points/cerberusweb.renderer.prebody/) | `cerberusweb.renderer.prebody` |
| [Profile Tab Type](/docs/plugins/extensions/points/cerb.profile.tab/) | `cerb.profile.tab` |
| [Profile Widget Type](/docs/plugins/extensions/points/cerb.profile.tab.widget/) | `cerb.profile.tab.widget` |
| [Record Type](/docs/plugins/extensions/points/devblocks.context/) | `devblocks.context` |
| [Resource Type](/docs/plugins/extensions/points/cerb.resource.type/) | `cerb.resource.type` |
| [Rest API Controller](/docs/plugins/extensions/points/cerberusweb.rest.controller/) | `cerberusweb.rest.controller` |
| [Scheduled Job](/docs/plugins/extensions/points/cerberusweb.cron/) | `cerberusweb.cron` |
| [Sensor Type](/docs/plugins/extensions/points/cerberusweb.datacenter.sensor/) | `cerberusweb.datacenter.sensor` |
| [Storage Engine](/docs/plugins/extensions/points/devblocks.storage.engine/) | `devblocks.storage.engine` |
| [Storage Schema](/docs/plugins/extensions/points/devblocks.storage.schema/) | `devblocks.storage.schema` |
| [Support Center Controller](/docs/plugins/extensions/points/usermeet.sc.controller/) | `usermeet.sc.controller` |
| [Support Center Login Authenticator](/docs/plugins/extensions/points/usermeet.login.authenticator/) | `usermeet.login.authenticator` |
| [Support Center RSS Feed](/docs/plugins/extensions/points/usermeet.sc.rss.controller/) | `usermeet.sc.rss.controller` |
| [Workspace Page Type](/docs/plugins/extensions/points/cerberusweb.ui.workspace.page/) | `cerberusweb.ui.workspace.page` |
| [Workspace Tab Type](/docs/plugins/extensions/points/cerberusweb.ui.workspace.tab/) | `cerberusweb.ui.workspace.tab` |
| [Workspace Widget Datasource](/docs/plugins/extensions/points/cerberusweb.ui.workspace.widget.datasource/) | `cerberusweb.ui.workspace.widget.datasource` |
| [Workspace Widget Type](/docs/plugins/extensions/points/cerberusweb.ui.workspace.widget/) | `cerberusweb.ui.workspace.widget` |

# Events

Plugins can add new **events** to Cerb based on the contributed functionality. The [activity log](/docs/activity-log/) will record the new events on [records](/docs/records/), [automations](/docs/automations/) can listen for them, etc.

```
<event_points>
	<event id="example.event">
		<name>Example Event</name>
		<param key="field_name" />
	</event>
</event_points>
```

- **`<event id="...">`** specifies the ID of the event.

- **`<name>`** is the human-friendly name of the event.

- **`<param key="...">`** is a list of available parameters on the event.

If you create a [Bot Event](/docs/plugins/extensions/points/devblocks.event/) extension you do not need to add a separate event here.

# Patches

Plugins that need to maintain a _schema_ in the database can do so with **patches**. A patch is a collection of changes used to migrate data between versions during an upgrade.

When you skip several versions of a plugin to upgrade to the latest version, Cerb will automatically handle the migration of your data through the intervening versions. This is the same thing that happens when you upgrade Cerb itself.

```
<patches>
	<patch version="9.0.0" revision="1" file="patches/9.0.0.php" />
</patches>
```

# Classloader

The **class loader** is a map of source code classes and their filesystem paths. This enables Cerb to efficiently only load the files necessary to serve a specific request.

If your plugin introduces classes that will be referenced by code outside of the plugin, you should register them here. Class loader entries are automatically created for any [extensions](/docs/plugins/extensions/) you register.

```
<class_loader>
	<file path="api/dao/example.php">
		<class name="Context_Example" />
		<class name="DAO_Example" />
		<class name="Model_Example" />
		<class name="Plugin_Example" />
		<class name="SearchFields_Example" />
		<class name="View_Example" />
	</file>
</class_loader>
```

# Permissions

Plugins can introduce new privileges into [roles](/docs/roles/).

```
<acl>
	<priv id="example.permission" label="acl.example.permission" />
</acl>
```

- **`id="..."`** is the ID of the new privilege. This uses dot-notation like plugins and extensions. It should also use your plugin ID as a namespace prefix.

- **`label="..."`** is the [translation](/docs/plugins/#translations) ID of the human-readable label for the privilege.

# Translations

Most of the text you see in Cerb is provided by the **translation** system using _American English_ defaults. All of this text is able to be translated into any other language using our built-in [Translation Editor](/docs/plugins/cerberusweb.translators/) plugin.

Plugins can add new text to the translation system with a `strings.xml` file in TMX format, which can then be translated into any language by anyone, as well as shared in our official translation packs.

```
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE tmx PUBLIC "-//LISA OSCAR:1998//DTD for Translation Memory eXchange//EN" "tmx14.dtd">
<tmx version="1.4">
	<header creationtool="Cerb" creationtoolversion="11.1.0" srclang="en_US" adminlang="en" datatype="unknown" o-tmf="unknown" segtype="sentence" creationid="" creationdate=""/>
	<body>

		<tu tuid='example.plugin.string_name'>
			<tuv xml:lang="en_US"><seg>Replace this with your own text.</seg></tuv>
		</tu>
		
	</body>
</tmx>
```

# Resources

Plugins can add new sharable resources like:

- Images

- Scripts (Javascript)

- Stylesheets

These must be stored in the `resources/` directory within the plugin.

Resources can then be accessed by URL with the format:

`/resource/` **`<plugin-id>`** `/path/to/resource/file.ext`

In [templates](/docs/plugins/#templates):

```
{devblocks_url}c=resource&plugin=example.plugin&f=path/to/resource/file.ext{/devblocks_url}
```

From [automation scripting](/docs/scripting/):

```
{{cerb_url('c=resource&plugin=example.plugin&f=path/to/resource/file.ext')}}
```

All plugin resources are public (world readable) and do not require a valid session to access. Do not store private content in this directory.

# Templates

Cerb plugins use the Smarty template engine.

Templates are stored in the plugin's `templates/` directory.

They are referenced from plugin code like:

```
$tpl = DevblocksPlatform::services()->template();
$tpl->assign('name', 'Kina Halpue');
$tpl->display('devblocks:example.plugin::path/to/template.tpl');
```

In `->display()`, `example.plugin` should be your plugin's [ID](/docs/plugins/#ids). The `path/to/` is relative to the plugin's `templates/` directory.

Here's an example template:

```
<div>
	Hello, <b>{$name}</b>!
</div>
```

# Activity Points

We previously mentioned **events** when discussing [automations](/docs/automations/) and the [activity log](/docs/activity-log/). Plugins can add new events to Cerb based on the contributed functionality. The activity log will record the new events on records, automations can listen for them, etc.

```
<activity_points>
	<activity point="example.event">
		<param key="label_key" value="Example Event" />
		<param key="string_key" value="activities.example_event" />
		<param key="options" value="api_create, notifications" />
	</activity>
</activity_points>
```

# Library

### Active

| Plugin | ID |
| --- | --- |
| [Cerb Core](/docs/plugins/cerberusweb.core/) | `cerberusweb.core` |
| [Devblocks](/docs/plugins/devblocks.core/) | `devblocks.core` |
| [Interactions for Websites](/docs/plugins/cerb.website.interactions/) | `cerb.website.interactions` |
| [Project Boards](/docs/plugins/cerb.project_boards/) | `cerb.project_boards` |
| [Time Tracking](/docs/plugins/cerberusweb.timetracking/) | `cerberusweb.timetracking` |
| [Translation Editor](/docs/plugins/cerberusweb.translators/) | `cerberusweb.translators` |
| [Web Services API (JSON/XML)](/docs/plugins/cerberusweb.restapi/) | `cerberusweb.restapi` |
| [Webhooks](/docs/plugins/cerb.webhooks/) | `cerb.webhooks` |

### Deprecated

These plugins are still bundled with Cerb for backward compatibility, but new functionality should be built using [automations](/docs/automations/) and [custom records](/docs/records/) instead.

| Plugin | ID |
| --- | --- |
| [Bot Behaviors](/docs/plugins/cerb.behaviors.legacy/) | `cerb.behaviors.legacy` |
| [Call Logging](/docs/plugins/cerberusweb.calls/) | `cerberusweb.calls` |
| [Chat Bots for Websites](/docs/plugins/cerb.bots.portal.widget/) | `cerb.bots.portal.widget` |
| [Classifiers](/docs/plugins/cerb.classifiers/) | `cerb.classifiers` |
| [Collaborative Feed Reader](/docs/plugins/cerberusweb.feed_reader/) | `cerberusweb.feed_reader` |
| [Domains](/docs/plugins/cerberusweb.datacenter.domains/) | `cerberusweb.datacenter.domains` |
| [JIRA Integration](/docs/plugins/wgm.jira/) | `wgm.jira` |
| [LDAP Integration](/docs/plugins/wgm.ldap/) | `wgm.ldap` |
| [Legacy Knowledgebase](/docs/plugins/cerberusweb.kb/) | `cerberusweb.kb` |
| [Notifications Emailer](/docs/plugins/wgm.notifications.emailer/) | `wgm.notifications.emailer` |
| [Opportunity Tracking](/docs/plugins/cerberusweb.crm/) | `cerberusweb.crm` |
| [S3 Gatekeeper Storage Engine](/docs/plugins/wgm.storage.s3.gatekeeper/) | `wgm.storage.s3.gatekeeper` |
| [Servers](/docs/plugins/cerberusweb.datacenter.servers/) | `cerberusweb.datacenter.servers` |
| [Support Center](/docs/plugins/cerberusweb.support_center/) | `cerberusweb.support_center` |

