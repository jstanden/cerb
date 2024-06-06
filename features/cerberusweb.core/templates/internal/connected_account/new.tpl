{$form_id = uniqid()}
<form action="#" method="post" id="{$form_id}">

{foreach from=$services item=service}
<div class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-context-id="0" data-edit="service.id:{$service->id}" style="cursor:pointer;display:inline-block;margin:5px;padding:10px;font-size:120%;border-radius:5px;border:1px solid var(--cerb-color-background-contrast-150);background-color:var(--cerb-color-background-contrast-240);">{$service->name}</div>
{/foreach}

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"Connect to an account:");
		
		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger({ 'view_id': '{$view_id}' })
		;
	});
});
</script>
