<h1>This is the example activity tab</h1>
<br>
<h2>Overview</h2>
The <strong>cerberusweb.mail.tab</strong> extension point allows you to create new tabs on the Mail page.


<h2>Plugin Manifest</h2>
<strong>plugin.xml</strong>
<pre>
&lt;!DOCTYPE plugin SYSTEM "../../libs/devblocks/plugin.dtd"&gt;
&lt;plugin&gt;
	&lt;id&gt;example.mailtab&lt;/id&gt;
	&lt;name&gt;[Examples] Example Mail Tab&lt;/name&gt;
	&lt;description&gt;This example plugin adds an example tab to the mail page&lt;/description&gt;
	&lt;author&gt;WebGroup Media, LLC.&lt;/author&gt;
	&lt;revision&gt;0&lt;/revision&gt;
	&lt;link&gt;http://wiki.cerberusweb.com/wiki/5.x/Extension_Point:cerberusweb.mail.tab&lt;/link&gt;

    &lt;!-- Plugin Dependencies --&gt;

	&lt;dependencies&gt;
		&lt;require plugin_id="cerberusweb.core" version="5.1.0" /&gt;
	&lt;/dependencies&gt;

    &lt;!-- Exported Classes --&gt;

	&lt;extensions&gt;
		&lt;extension point="cerberusweb.mail.tab"&gt;
			&lt;id&gt;example.mail.tab&lt;/id&gt;
			&lt;name&gt;Example Mail Tab&lt;/name&gt;
			&lt;class&gt;
				&lt;file&gt;api/App.php&lt;/file&gt;
				&lt;name&gt;ExMailTab&lt;/name&gt;
			&lt;/class&gt;
			&lt;params&gt;
				&lt;param key="uri" value="example" /&gt;
    			&lt;param key="title" value="example.mail.tab" /&gt;
			&lt;/params&gt;
		&lt;/extension&gt;
	&lt;/extensions&gt;
&lt;/plugin&gt;
</pre>

<ul>
	<li>
		<strong>&lt;extension point="..."&gt;</strong> binds a new extension on the specified extension point.
	</li>
	<li>
		<strong>&lt;id&gt;</strong> must be unique across all Cerb5 plugins. This is a dot-delimited namespace string. The name is entirely up to you, but the namespace should follow the name of your plugin, and the ID itself should have a hierarchy. If your plugin is named xyzcompany.myplugin then your extension IDs should follow the convention xyzcompany.myplugin.point.name where point.name represents the extension point and a unique identifier for each particular extension.
	</li>
	<li>
		<strong>&lt;name&gt;</strong> is a human-readable name for your extension. This can be anything you want. There are situations where you'll want to retrieve your extensions name and use it in functionality exposed to the user (e.g. in a dropdown list); and it's a great approach because the name can be quickly retrieved from the manifest (in memory) without running any plugin-level code.
		</li>
	<li>
		<strong>&lt;file&gt;</strong> and <strong>&lt;class&gt;</strong> tell Devblocks where to find the extension's implementation in the source code. &lt;file&gt; is relative to your plugin's directory.
	</li>
	<li>
		<strong>&lt;params&gt;</strong> allow the manifest to pass information to each implementation of an extension. Each &lt;param&gt; has a key and value attribute. These are static values that are not expected to change. You'll need to implement properties that are configured by the user; see cerberusweb.config.tab.
	</li>
	<li>
		<strong>key="uri"</strong> defines the URI for the tab, which can be used to select a specific tab on a page from a URL. A tab for searching with the URI 'search' on a page called 'records' may result in a URL like /cerb4/records/search. The URI for a tab may appear at any depth depending on how a cerberusweb.page controller is implemented.
	</li>
	<li>
		<strong>key="title"</strong> points to a namespaced ID from the translation system; usually defined in the plugin's strings.xml file. This is the text that will be displayed on the tab. (You can also enter literal text here, but you should try to always use the translation system)
	</li>
</ul>

<h2>Implementation</h2>
<pre>
	abstract class Extension_PreferenceTab extends DevblocksExtension {
		function __construct($manifest) {
			$this->DevblocksExtension($manifest,1);
		}

		function showTab() {}
		function saveTab() {}
	};
</pre>
<ul>
	<li>
		<strong>showTab()</strong> renders content via Ajax when a tab is selected.
	</li>
	<li>
		<strong>saveTab()</strong> allows a default implementation for saving tab content. This convenience removes the need for a controller to handle the actions of every tab; however, you may decide to implement more complex functionality and you can simply ignore this method. Tabs don't have to be limited to a single page of content with a submit form; they can contain lists, sub-tabs, and other dynamic functionality that make use of several actions on your own control
	</li>
</ul>