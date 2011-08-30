<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formDisplayReq" name="formDisplayReq" onsubmit="return false;">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveRequestersPanel">
<input type="hidden" name="ticket_id" value="{$ticket_id}">

<b>{'display.ui.add_to_recipients'|devblocks_translate}:</b><br>
<button type="button" class="chooser_address"><span class="cerb-sprite2 sprite-plus-circle-frame"></span></button>
<ul class="chooser-container bubbles">
{if !empty($requesters)}
{foreach from=$requesters item=requester}
<li>{$requester->email}<input type="hidden" name="address_id[]" value="{$requester->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
{/foreach}
{/if}	
</ul>
<br>
<br>

<b>Add new e-mail addresses:</b> (comma-separated)<br>
<input type="text" name="lookup" style="width:100%;">
<br>
<br>

<button id="btnSaveRequestersPanel" type="button"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','Recipients');
		
		// Add an autocomplete for single address entry (including new IDs)
		ajax.emailAutoComplete('#formDisplayReq input:text[name=lookup]', { multiple: true } );
		
		// Save button		
		$('#btnSaveRequestersPanel').bind('click', function() {
			genericAjaxPost('formDisplayReq','','',
				function(html) {
					genericAjaxPopupClose('peek');
					genericAjaxGet('displayTicketRequesterBubbles', 'c=display&a=requestersRefresh&ticket_id={$ticket_id}');
				}
			);
		});
		
		$(this).find('input:text:first').focus();
	});
	$('#formDisplayReq button.chooser_address').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.address','address_id');
	});
</script>
