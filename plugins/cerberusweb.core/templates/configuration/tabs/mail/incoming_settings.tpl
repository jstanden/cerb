<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td><h2>Incoming Mail</h2></td>
				</tr>
				<tr>
					<td>
						[ <a href="javascript:;" onclick="genericAjaxGet('configMailbox','c=config&a=getMailbox&id=0');">add new mail server</a> ]
					</td>
				</tr>
				<tr>
					<td>
						<div style="margin:0px;padding:3px;width:200px;overflow:auto;">
						{if !empty($pop3_accounts)}
							{foreach from=$pop3_accounts item=pop3}
								&#187;  <a href="javascript:;" onclick="genericAjaxGet('configMailbox','c=config&a=getMailbox&id={$pop3->id}');">{$pop3->nickname}</a><br>
							{/foreach}
						{/if}
						</div>
					</td>
				</tr>
			</table>
			</div>
			
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configMailbox">
				{include file="$core_tpl/configuration/tabs/mail/edit_pop3_account.tpl" pop3=null}
			</form>
		</td>
		
	</tr>
</table>
<br>

<div class="block" id="configMailboxIncoming">
<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td><h2>Incoming Mail Preferences</h2></td>
	</tr>
	<tr>
		<td>
			<form action="{devblocks_url}{/devblocks_url}#incoming" method="post">
			<input type="hidden" name="c" value="config">
			<input type="hidden" name="a" value="saveIncomingMailSettings">

			<b>Reply to All:</b><br>
			<label><input type="checkbox" name="parser_autoreq" value="1" {if $settings->get('parser_autoreq')}checked{/if}> Send helpdesk replies to every recipient (To:/Cc:) on the original message.</label><br>
			<br>

			<div style="padding-left:10px;">
				<b>Always Exclude These Recipients:</b><br>
				<textarea name="parser_autoreq_exclude" rows="4" cols="76">{$settings->get('parser_autoreq_exclude')|escape:"html"}</textarea><br>
				<i>(one address per line)</i> &nbsp;  
				<i>use * for wildcards, like: *@do-not-reply.com</i><br>
				<br>
			</div>

			<b>Attachments:</b><br>
			<label><input type="checkbox" name="attachments_enabled" value="1" {if $settings->get('attachments_enabled')}checked{/if}> Allow Incoming Attachments</label><br>
			<br>
			
			<div style="padding-left:10px;">
				<b>Maximum Attachment Size:</b><br>
				<input type="text" name="attachments_max_size" value="{$settings->get('attachments_max_size')|escape:"html"}" size="5"> MB<br>
				<i>(attachments larger than this will be ignored)</i><br>
				<br>
			</div>

			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			</form>
		</td>
	</tr>
</table>
</div>
