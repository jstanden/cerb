{$menu_divid = "{uniqid()}"}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmRelayMessage" name="frmRelayMessage">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveRelayMessagePopup">
<input type="hidden" name="id" value="{$message->id}">

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
## Instructions: http://wiki.cerbweb.com/Email_Relay
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
			
				{foreach from=$worker->relay_emails item=email}
				<li email="{$email}" label="{$email}">
					<div class="item">
						<a href="javascript:;">{$email}</a><br>
						<div style="margin-left:10px;">{$worker->getName()}</div>
					</div>
				</li>
				{/foreach}
				
			{/if}
		{/foreach}
	</ul>
	
</fieldset>

<button type="button" class="ok"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.ok'|devblocks_translate|capitalize}</button>
<button type="button" class="cancel"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('relay');
	$popup.one('popup_open',function(event,ui) {
		var $this = $(this);
		
		$this.dialog('option','title','Relay message to external worker email');
		
		$this.find('button.ok').click(function() {
			genericAjaxPost('frmRelayMessage','','',function(json) {
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
		
		$menu = $('#{$menu_divid}');
		$input = $menu.prevAll('input.filter');
		$input.focus();

		$input.keypress(
			function(e) {
				code = (e.keyCode ? e.keyCode : e.which);
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
				term = $(this).val().toLowerCase();
				$menu = $(this).nextAll('ul.cerb-popupmenu');
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
			$li = $(this).closest('li');
			$frm = $(this).closest('form');
			
			$ul = $li.closest('ul');
			$bubbles = $ul.siblings('ul.bubbles');
			
			email = $li.attr('email');
			label = $li.attr('label');

			// Check for dupe context pair
			if($bubbles.find('li input:hidden[value="'+email+'"]').length > 0)
				return;
			
			$bubble = $('<li></li>');
			$bubble.append($('<input type="hidden" name="emails[]" value="'+email+'">'));
			$bubble.append(label);
			$bubble.append('<a href="javascript:;" onclick="$li=$(this).closest(\'li\');$li.remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a>');
			
			$bubbles.append($bubble);
		});
		
	});
</script>
