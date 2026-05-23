<h2>Save Configuration File</h2>

{if $failed}
<div class="alert alert-error">
	<span class="icon">{call name="icon" icon="circle-x" size=20}</span>
	<div class="content">
		<strong>Configuration not detected</strong>
		<p class="mb-0">The framework.config.php file does not appear to have the updated settings. Please try again.</p>
	</div>
</div>
{/if}

<div class="alert alert-info mb-3">
	<span class="icon">{call name="icon" icon="circle-alert" size=20}</span>
	<div class="content">
		Your environment does not support automatic writing of the configuration file.
		Please manually update the file with the contents below.
	</div>
</div>

<form action="index.php" method="POST">
	<input type="hidden" name="step" value="{$smarty.const.STEP_SAVE_CONFIG_FILE}">
	<input type="hidden" name="overwrite" value="1">
	<input type="hidden" name="db_engine" value="{$db_engine}">
	<input type="hidden" name="db_server" value="{$db_server}">
	<input type="hidden" name="db_port" value="{$db_port}">
	<input type="hidden" name="db_name" value="{$db_name}">
	<input type="hidden" name="db_user" value="{$db_user}">
	<input type="hidden" name="db_pass" value="{$db_pass}">

	<div class="form-group">
		<label>File Path</label>
		<code style="display:block; padding:0.75rem; background:var(--cerb-installer-bg); border-radius:6px;">{$config_path}</code>
	</div>

	<div class="form-group">
		<label for="result">File Contents</label>
		<textarea name="result" id="result" rows="12" style="font-family:monospace; font-size:0.875rem;">{$result}</textarea>
		<div class="hint">Copy this content and paste it into the file above, then click the button below.</div>
	</div>

	<div class="button-row">
		<button type="submit" autofocus>
			Verify Configuration
			{call name="icon" icon="arrow-right" size=18}
		</button>
	</div>
</form>
