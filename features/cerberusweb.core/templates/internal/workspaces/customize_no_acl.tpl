<fieldset class="delete" style="margin-top:5px;">
	<legend>Customize: Access Denied</legend>
	
	<div style="margin-bottom:5px;">
		You do not have permission to modify this worklist.
	</div>
	
	<button type="button"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
$(function() {
	let $script = $('#{$script_uid}');

	$script.prev('fieldset').find('button').on('click', function(e) {
		e.stopPropagation();
		$(this).closest('fieldset').remove();
	});
});
</script>