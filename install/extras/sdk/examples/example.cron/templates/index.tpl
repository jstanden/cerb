<h1>This is the example cron</h1>
<br>
<h2>Overview</h2>
The <strong>cerberusweb.cron</strong> extension point allows you to create scheduled jobs that are run when the /cron controller is access by your server's cronjob. This allows for timed functionality like maintenance, escalations, auto-generated reports, and any other behavior you want to automate.

<h2>Plugin Manifest</h2>
<strong>plugin.xml</strong>
<pre>
&lt;!DOCTYPE plugin SYSTEM "../../libs/devblocks/plugin.dtd"&gt;
&lt;plugin&gt;
	&lt;id&gt;example.crontab&lt;/id&gt;
	&lt;name&gt;[Examples] Example Activity Tab&lt;/name&gt;
	&lt;description&gt;This example plugin adds an example tab to the cron page&lt;/description&gt;
	&lt;author&gt;WebGroup Media, LLC.&lt;/author&gt;
	&lt;revision&gt;0&lt;/revision&gt;
	&lt;link&gt;http://wiki.cerberusweb.com/wiki/5.x/Extension:cerberusweb.cron.tab&lt;/link&gt;

	&lt;!-- Plugin Dependencies --&gt;

	&lt;dependencies&gt;
		&lt;require plugin_id="cerberusweb.core" version="5.1.0" /&gt;
	&lt;/dependencies&gt;

	&lt;!-- Exported Classes --&gt;

	&lt;extensions&gt;
		&lt;extension point="cerberusweb.cron.tab"&gt;
			&lt;id&gt;example.cron&lt;/id&gt;
			&lt;name&gt;Example Config Tab&lt;/name&gt;
			&lt;class&gt;
				&lt;file&gt;api/App.php&lt;/file&gt;
				&lt;name&gt;ExConfigTab&lt;/name&gt;
			&lt;/class&gt;
			&lt;params /&gt;
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
		<strong>&lt;params&gt;</strong> allow the manifest to pass information to each implementation of an extension. Each &lt;param&gt; has a key and value attribute. These are static values that are not expected to change. You'll need to implement properties that are cronured by the user; see cerberusweb.cron.tab.
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
	abstract class CerberusCronPageExtension extends DevblocksExtension {

		/**
		 * runs scheduled task
		 *
		 */
		function run() {
		    // Overloaded by child
		}

		function _run() {
		    $this->run();

			$duration = $this->getParam(self::PARAM_DURATION, 5);
			$term = $this->getParam(self::PARAM_TERM, 'm');
		    $lastrun = $this->getParam(self::PARAM_LASTRUN, time());

		    $secs = self::getIntervalAsSeconds($duration, $term);
		    $ran_at = time();

		    if(!empty($secs)) {
			    $gap = time() - $lastrun; // how long since we last ran
			    $extra = $gap % $secs; // we waited too long to run by this many secs
			    $ran_at = time() - $extra; // go back in time and lie
		    }

		    $this->setParam(self::PARAM_LASTRUN,$ran_at);
		    $this->setParam(self::PARAM_LOCKED,0);
		}

		/**
		 * @param boolean $is_ignoring_wait Ignore the wait time when deciding to run
		 * @return boolean
		 */
		public function isReadyToRun($is_ignoring_wait=false) {
			$locked = $this->getParam(self::PARAM_LOCKED, 0);
			$enabled = $this->getParam(self::PARAM_ENABLED, false);
			$duration = $this->getParam(self::PARAM_DURATION, 5);
			$term = $this->getParam(self::PARAM_TERM, 'm');
			$lastrun = $this->getParam(self::PARAM_LASTRUN, 0);

			// If we've been locked too long then unlock
		    if($locked && $locked < (time() - 10 * 60)) {
		        $locked = 0;
		    }

		    // Make sure enough time has elapsed.
		    $checkpoint = ($is_ignoring_wait)
		    	? (0) // if we're ignoring wait times, be ready now
		    	: ($lastrun + self::getIntervalAsSeconds($duration, $term)) // otherwise test
		    	;

		    // Ready?
		    return (!$locked && $enabled && time() >= $checkpoint) ? true : false;
		}
		
		public function configure($instance) {}

		public function saveConfigurationAction() {}
	};
</pre>
<ul>
	<li>
		<strong>run()</strong> executes your custom code when the scheduled task is triggered.
	</li>
	<li>
		<strong>configure()</strong> displays a template when a scheduled task is configured from 'Helpdesk Setup -> Scheduler'
	</li>
	<li>
		<strong>saveConfigurationAction()</strong> is called when the form from configure()'s template is submitted.
	</li>
</ul>