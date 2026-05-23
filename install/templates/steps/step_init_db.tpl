<h2>Database Initialization</h2>

<div class="alert alert-error">
	<span class="icon">{call name="icon" icon="circle-x" size=20}</span>
	<div class="content">
		<strong>Database Error</strong>
		<p class="mb-0">{$error|default:'Database initialization failed.'}</p>
	</div>
</div>

<form action="index.php" method="POST">
	<input type="hidden" name="step" value="{$smarty.const.STEP_INIT_DB}">

	<div class="button-row">
		<button type="submit" autofocus>
			Try Again
			{call name="icon" icon="arrow-right" size=18}
		</button>
	</div>
</form>
