{if !$smtp_test && !empty($smtp_test_output)}
	<div class="error">
		{$smtp_test_output}
	</div>
	<br>
{elseif $smtp_test===true}
	<div class="success">
		Outgoing mail settings were tested successfully.
	</div>
	<br>
{/if}
