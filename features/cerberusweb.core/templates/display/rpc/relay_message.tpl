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

	<ul class="bubbles" style="display:block;"></ul>
	
	<input type="text" size="64" class="input_search filter" style="width:90%;">


	<ul class="cerb-popupmenu" id="{$menu_divid}" style="display:block;margin-bottom:5px;max-height:200px;overflow-x:hidden;overflow-y:auto;">
		{foreach from=$workers_with_relays item=worker}
			{if !empty($worker->relay_emails)}
				{$object_addys = DAO_Address::getIds($worker->relay_emails)}
			
				{foreach from=$object_addys item=addy}
				<li email="{$addy->email}" label="{$addy->email}">
					<div class="item">
						<a href="javascript:;">{$addy->email}</a><br>
						<div style="margin-left:10px;">{$worker->getName()}</div>
					</div>
				</li>
				{/foreach}
				
			{/if}
		{/foreach}
	</ul>
	
</fieldset>

<button type="button" class="ok"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.ok'|devblocks_translate|capitalize}</button>
<button type="button" class="cancel"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
<br>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('relay');
	
	$popup.one('popup_open',function(event,ui) {
		var $this = $(this);
		
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
		
		var $menu = $('#{$menu_divid}');
		var $input = $menu.prevAll('input.filter');
		$input.focus();

		$input.keypress(
			function(e) {
				var code = e.keyCode || e.which;
				if(code == 13) {
					e.preventDefault();
					e.stopPropagation();
					$(this).select().focus();
					return false;
				}
			}
		);
			
		$input.keyup(
			function(e) {
				var term = $(this).val().toLowerCase();
				var $menu = $(this).nextAll('ul.cerb-popupmenu');
				$menu.find('> li > div.item').each(function(e) {
					if(-1 != $(this).html().toLowerCase().indexOf(term)) {
						$(this).parent().show();
					} else {
						$(this).parent().hide();
					}
				});
			}
		);

		$menu.find('> li').click(function(e) {
			e.stopPropagation();
			if($(e.target).is('a'))
				return;

			$(this).find('a').trigger('click');
		});

		$menu.find('> li > div.item a').click(function() {
			var $li = $(this).closest('li');
			var $frm = $(this).closest('form');
			
			var $ul = $li.closest('ul');
			var $bubbles = $ul.siblings('ul.bubbles');
			
			var email = $li.attr('email');
			var label = $li.attr('label');

			// Check for dupe context pair
			if($bubbles.find('li input:hidden[value="'+email+'"]').length > 0)
				return;
			
			var $bubble = $('<li/>').text(label);
			$bubble.append($('<input type="hidden">').attr('name','emails[]').attr('value',email));
			$bubble.append('<a href="javascript:;" onclick="$li=$(this).closest(\'li\');$li.remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>');
			
			$bubbles.append($bubble);
		});
		
	});
});
</script>
