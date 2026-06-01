{$div_id = uniqid('error')}
<div id="{$div_id}" class="error-box" style="margin-top:10px;">
	{if $error_title}
	<h1>{$error_title}</h1>
	{/if}
	
	<div>
		<div>{$error_message}</div>
		<button type="button"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.ok'|devblocks_translate}</button>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $div = $('#{$div_id}');

	$div.find('button').on('click', function(e) {
		e.stopPropagation();
		$(this).closest('div.error-box').remove();
	});
});
</script>