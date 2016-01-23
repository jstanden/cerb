<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formDisplayReq" name="formDisplayReq" onsubmit="return false;">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveRequestersPanel">
<input type="hidden" name="ticket_id" value="{$ticket_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>{'display.ui.add_to_recipients'|devblocks_translate}:</b><br>

<button type="button" class="chooser-abstract" data-field-name="address_id[]" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="" data-autocomplete="true" data-create="true"><span class="glyphicons glyphicons-search"></span></button>

<ul class="bubbles chooser-container" style="display:block;margin-top:10px;">
	{if !empty($requesters)}
	{foreach from=$requesters item=requester}
		<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$requester->id}{/devblocks_url}?v={$requester->updated}"><input type="hidden" name="address_id[]" value="{$requester->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$requester->id}">{$requester->getNameWithEmail()}</a></li>
	{/foreach}
	{/if}
</ul>
<br>

<button id="btnSaveRequestersPanel" type="button"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<br>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title','{'common.participants'|devblocks_translate|capitalize|escape:'javascript'}');
		
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		// Save button
		$('#btnSaveRequestersPanel').bind('click', function() {
			var $div = $('#displayTicketRequesterBubbles');
			
			genericAjaxPost('formDisplayReq','','',
				function(html) {
					genericAjaxGet($div, 'c=display&a=requestersRefresh&ticket_id={$ticket_id}', function(html) {
						genericAjaxPopupClose($popup);
					});
				}
			);
		});
		
		$popup.find('input:text:first').focus();
		
		$('#formDisplayReq button.chooser_address').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.address','address_id');
		});
	});
});
</script>