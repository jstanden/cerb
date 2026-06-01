{$form_id = uniqid('form')}
<form action="#" method="post" id="{$form_id}">

{if $rows}
	{if 'fieldsets' == $layout.style}
		{include file="devblocks:cerberusweb.core::ui/sheets/render_fieldsets.tpl"}
	{elseif in_array($layout.style, ['columns','grid'])}
		{include file="devblocks:cerberusweb.core::ui/sheets/render_grid.tpl"}
	{else}
		{include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}
	{/if}
{elseif !$active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_CONNECTED_SERVICE}.create")}
	<div class="error-box">
		<p>You must create at least one <b>connected service</b> first.</p>
	</div>
{/if}

{if $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_CONNECTED_SERVICE}.create")}
<div style="margin-top:0.75em;">
	<button data-cerb-button-add-service type="button" style="width:100%;" data-context="{CerberusContexts::CONTEXT_CONNECTED_SERVICE}" data-context-id="0" data-edit="true" data-width="80%"><span class="cerb-icons cerb-icon-circle-plus"></span> Add a connected service</button>
</div>
{/if}
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);
	let $sheet = $popup.find('.cerb-sheet, .cerb-data-sheet, .cerb-sheet-grid, .cerb-sheet-columns');
	let $button_add_service = $popup.find('[data-cerb-button-add-service]');
	let layer = $popup.attr('data-layer');

	Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"Connect to an account at this service:");

		$sheet.on('cerb-sheet--selections-changed', function(e) {
			e.stopPropagation();

			if(!e.hasOwnProperty('row_selections') || !$.isArray(e.row_selections) || 0 === e.row_selections.length)
				return;

			let service_id = parseInt(e.row_selections[0] ?? 0);

			$("<div/>")
				.attr('data-context', '{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}')
				.attr('data-context-id', '0')
				.attr('data-edit', 'service.id:' + service_id)
				.cerbPeekTrigger()
				.on('cerb-peek-saved', function(evt) {
					evt.stopPropagation();
					genericAjaxGet('view{$view_id}','c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
					$(this).remove();
				})
				.on('cerb-peek-aborted', function(evt) {
					evt.stopPropagation();
					$(this).remove();
				})
				.click()
			;
		});

		{if $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_CONNECTED_SERVICE}.create")}
		$button_add_service.cerbPeekTrigger()
			.on('cerb-peek-saved cerb-peek-deleted cerb-peek-aborted', function(evt) {
				evt.stopPropagation();

				// If the connected service library already added an account, we're done
				if(evt.hasOwnProperty('account_id')) {
					genericAjaxGet('view{$view_id}', 'c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
					genericAjaxPopupClose(layer);

				} else {
					genericAjaxPopup(
						layer,
						'c=internal&a=invoke&module=records&action=showPeekPopup&context={CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}&context_id=0&view_id={$view_id}',
						'reuse',
						false
					);
				}
			})
		;
		{/if}
	});
});
</script>
