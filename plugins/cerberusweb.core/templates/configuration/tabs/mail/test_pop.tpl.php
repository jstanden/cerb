{if !$pop_test && !empty($pop_test_output)}
	<div class="error">
		{$pop_test_output}
	</div>
	<br>
{elseif $pop_test===true}
	<div class="success">
		Incoming mailbox were tested successfully.
	</div>
	<br>
{/if}
