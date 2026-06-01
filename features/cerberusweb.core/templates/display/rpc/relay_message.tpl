{$menu_divid = "{uniqid()}"}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmRelayMessage" name="frmRelayMessage">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="message">
<input type="hidden" name="action" value="saveRelayMessagePopup">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>Message contents</legend>
	
	{$sender_name = $sender->getName()}
	<b>From: </b> {if !empty($sender_name)}{$sender->getName()} &lt;{$sender->email}&gt;{else}{$sender->email}{/if} 
	<br>
	<b>Subject:</b> {$ticket->subject}
	<br>
	<textarea name="content" rows="15" cols="60" style="width:98%;">
## Relayed from {devblocks_url full=true}c=profiles&w=ticket&mask={$ticket->mask}{/devblocks_url}
## 
## Your reply to this message will be sent to the requesters.
## Instructions: https://cerb.ai/guides/mail/relaying/
##
## {if !empty($sender_name)}{$sender->getName()} &lt;{$sender->email}&gt;{else}{$sender->email}{/if} wrote:
{$message->getContent()}</textarea>

	<label><input type="checkbox" name="include_attachments" value="1"> Include attachments</label>
</fieldset>

<fieldset class="peek">
	<legend>Relay to:</legend>
	<button type="button" class="chooser-abstract" data-field-name="address_ids[]" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query-required="worker.id:!0" data-query="" data-autocomplete="worker.id:!0"><span class="cerb-icons cerb-icon-search"></span></button>
	<ul class="bubbles chooser-container"></ul>
</fieldset>

<button type="button" class="ok"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.ok'|devblocks_translate|capitalize}</button>
<button type="button" class="cancel"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
<br>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFetch('relay');
	
	$popup.one('popup_open',function() {
		let $this = $(this);
		
		$this.dialog('option','title','Relay message to external worker email');
		
		$this.find('button.ok').click(function() {
			genericAjaxPost('frmRelayMessage', null, null, function(json) {
				// [TODO] On failure, display an error popup

				// Reload the selected tab
				var $tabs = $('#displayTabs');
				$tabs.tabs('load', $tabs.tabs('option','active'));
				
				// Close the popup
				genericAjaxPopupClose('relay');
			});
		});
		
		$this.find('button.cancel').click(function() {
			genericAjaxPopupClose('relay');
		});

		// Chooser
		$popup.find('.chooser-abstract').cerbChooserTrigger();
	});
});
</script>
