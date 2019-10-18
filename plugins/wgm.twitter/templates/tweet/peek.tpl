{$peek_context = Context_TwitterMessage::ID}
{$is_writeable = Context_ConnectedAccount::isReadableByActor($message->connected_account_id, $active_worker)}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmTwitterMessage" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="twitter_message">
<input type="hidden" name="action" value="savePeekPopup">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:10px;">
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">
			<img src="{$message->user_profile_image_url}" style="margin-right:5px;border-radius:5px;" width="48" height="48">
		</td>
		<td width="99%" valign="top">
			<div>
				<b class="subject" title="{$message->user_name}">{$message->user_name}</b>
				<span style="color:rgb(150,150,150);">
					@{$message->user_screen_name}
					&middot; 
					<span title="{$message->created_date|devblocks_date}">{$message->created_date|devblocks_date:'d M Y'}</span>
				</span>
			</div>
			<div style="padding:5px;">
				{$message->content|escape|devblocks_hyperlinks nofilter}
			</div> 
		</td>
	</tr>
</table>

{if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
<fieldset class="peek">
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><label><b>Reply:</b> <input type="checkbox" name="do_reply" value="1" checked="checked"></label></td>
			<td width="99%" valign="top">
				<textarea name="reply" rows="5" cols="80" style="width:98%;height:50px;"></textarea>
				<div class="tweet-counter"></div>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'status.resolved'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<label><input type="radio" name="is_closed" value="1" checked="checked"> {'common.yes'|devblocks_translate|lower}</label>
				<label><input type="radio" name="is_closed" value="0"> {'common.no'|devblocks_translate|lower}</label>
			</td>
		</tr>
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=Context_TwitterMessage::ID context_id=$message->id}

<div class="toolbar">
	<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView(null,'frmTwitterMessage','{$view_id}',false,'twitter_message_save');"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
{/if}

</form>

<script type="text/javascript" src="{devblocks_url}c=resource&p=wgm.twitter&f=twitter-text-2.0.0.min.js{/devblocks_url}"></script>
<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		{$account = $accounts.{$message->account_id}}
		$popup.dialog('option','title',"{'wgm.twitter.common.message'|devblocks_translate|capitalize|escape:'javascript' nofilter}{if !empty($account)} @{$account->screen_name|escape:'javascript' nofilter}{/if}");
		
		{if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
		var $txt = $popup.find('textarea:first').autosize().insertAtCursor('@{$message->user_screen_name|escape:'javascript'} ');
		var $counter = $popup.find('div.tweet-counter');
		
		$txt.on('keyup', function() {
			var parsedTweet = twttr.txt.parseTweet($txt.val());
			var percentage = Math.round(parsedTweet.permillage / 10);
			$counter.text(percentage + '%');
			
			if(percentage >= 100) {
				$counter.css('color','red');
			} else {
				$counter.css('color','green');
			}
		});
		
		$popup.find('input:checkbox[name=do_reply]').click(function(e) {
			if($(this).is(':checked')) {
				$txt.show();
				$txt.focus();
			} else {
				$txt.hide().blur();
			}
		});
		{/if}
	});
});
</script>