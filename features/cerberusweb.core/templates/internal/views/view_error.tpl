<div class="error-box" style="margin-top:10px;">
	{if $error_title}
	<h1>{$error_title}</h1>
	{/if}
	
	<div>
		<div>
			{$error_message}
		</div>
		
		<button type="button" onclick="$(this).closest('div.error-box').remove();"><span class="glyphicons glyphicons-circle-ok"></span> {'common.ok'|devblocks_translate}</button>
	</div>
</div>