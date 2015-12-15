<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formDisplayReq" name="formDisplayReq" onsubmit="return false;">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveRequestersPanel">
<input type="hidden" name="ticket_id" value="{$ticket_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>{'display.ui.add_to_recipients'|devblocks_translate}:</b><br>
<button type="button" class="chooser_address"><span class="glyphicons glyphicons-circle-plus"></span></button>
<ul class="chooser-container bubbles">
{if !empty($requesters)}
{foreach from=$requesters item=requester}
<li class="bubble-gray"><img src="{devblocks_url}c=avatars&context=contact&context_id={$requester->contact_id}{/devblocks_url}?v={$requester->updated}" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;"> {$requester->email}<input type="hidden" name="address_id[]" value="{$requester->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
{/foreach}
{/if}	
</ul>
<br>
<br>

<b>Add new email addresses:</b> (comma-separated)<br>
<input type="text" name="lookup" style="width:100%;">
<br>
<br>

<button id="btnSaveRequestersPanel" type="button"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<br>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title','{'common.participants'|devblocks_translate|capitalize|escape:'javascript'}');
		
		// Add an autocomplete for single address entry (including new IDs)
		ajax.emailAutoComplete('#formDisplayReq input:text[name=lookup]', { multiple: true } );
		
		// Save button		
		$('#btnSaveRequestersPanel').bind('click', function() {
			var $div = $('#displayTicketRequesterBubbles');
			$div.fadeTo('fast', 0.5);
			
			genericAjaxPost('formDisplayReq','','',
				function(html) {
					genericAjaxGet('', 'c=display&a=requestersRefresh&ticket_id={$ticket_id}', function(html) {
						$div.html(html).find('.cerb-peek-trigger').cerbPeekTrigger();
						$div.fadeTo('fast', 1.0);
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