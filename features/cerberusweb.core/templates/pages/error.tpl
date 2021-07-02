<div style="margin:5px 0;">
	<h2>{$page->name}</h2>
</div>

<div class="error-box" style="margin-top:10px;">
	{if $error_title}
		<h1>{$error_title}</h1>
	{/if}

	<div>
		{$error_message}
	</div>
</div>