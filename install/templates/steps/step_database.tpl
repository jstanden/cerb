<h2>Database Setup</h2>

{if $failed && !empty($errors)}
<div class="alert alert-error">
	<span class="icon">{call name="icon" icon="circle-x" size=20}</span>
	<div class="content">
		<strong>Database connection failed</strong>
		<ul>
		{foreach from=$errors item=error}
			<li>{$error}</li>
		{/foreach}
		</ul>
	</div>
</div>
{/if}

<form action="index.php" method="POST">
	<input type="hidden" name="step" value="{$smarty.const.STEP_DATABASE}">

	<fieldset>
		<legend>Connection Settings</legend>

		<div class="form-group">
			<label for="db_engine">Storage Engine</label>
			<select name="db_engine" id="db_engine">
				{foreach from=$engines item=engine key=k}
				<option value="{$k}" {if $k==$db_engine}selected{/if}>{$engine}</option>
				{/foreach}
			</select>
		</div>

		<div class="form-row">
			<div class="form-group">
				<label for="db_server">Host</label>
				<input type="text" name="db_server" id="db_server" value="{$db_server}" placeholder="localhost" autofocus>
			</div>
			<div class="form-group">
				<label for="db_port">Port</label>
				<input type="text" name="db_port" id="db_port" value="{$db_port}" placeholder="3306 (optional)">
				<div class="hint">Leave blank for default</div>
			</div>
		</div>

		<div class="form-group">
			<label for="db_name">Database Name</label>
			<input type="text" name="db_name" id="db_name" value="{$db_name}" placeholder="cerb">
		</div>
	</fieldset>

	<fieldset>
		<legend>Authentication</legend>

		<div class="form-group">
			<label for="db_user">Username</label>
			<input type="text" name="db_user" id="db_user" value="{$db_user}" placeholder="Database username">
		</div>

		<div class="form-group">
			<label for="db_pass">Password</label>
			<input type="password" name="db_pass" id="db_pass" value="{$db_pass}" placeholder="Database password">
		</div>
	</fieldset>

	<div class="button-row">
		<button type="submit">
			Test Connection
			{call name="icon" icon="arrow-right" size=18}
		</button>
	</div>
</form>
