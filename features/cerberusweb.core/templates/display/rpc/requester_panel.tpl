<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formDisplayReq" name="formDisplayReq">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveRequestersPanel">
<input type="hidden" name="ticket_id" value="{$ticket_id}">

{if !empty($requesters)}
<b>Remove checked:</b><br>
{foreach from=$requesters item=requester}
	<label><input type="checkbox" name="req_deletes[]" value="{$requester->id}"> {$requester->email}</label>
	<br>
{/foreach}
<br>
{/if}

<b>Add new requesters:</b> (one e-mail per line)<br>
<textarea name="req_adds" rows="4" cols="35" style="width:98%;"></textarea><br>
<br>

<button id="btnSaveRequestersPanel" type="button"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript" language="JavaScript1.2">
	var $popup = genericAjaxPopupFetch('peek');
	$popup.one('dialogopen',function(event,ui) {
		$popup.dialog('option','title','Recipients');
		
		//ajax.emailAutoComplete('#formDisplayReq textarea[name=req_adds]', { multiple: true } );
		
		$('#btnSaveRequestersPanel').bind('click', function() {
			genericAjaxPost('formDisplayReq','','',
				function(html) {
					genericAjaxPopupClose('peek');
					genericAjaxGet('displayTicketRequesterBubbles', 'c=display&a=requestersRefresh&ticket_id={$ticket_id}');
				}
			);
		} );
	});
</script>
