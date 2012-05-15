<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="mail_pop3">
<input type="hidden" name="action" value="saveMailboxJson">

<fieldset>
	<legend>
		{if empty($pop3_account->id)}
		Add New Mail Server
		<input type="hidden" name="account_id" value="0">
		{else}
		Mail Server '{$pop3_account->nickname}'
		<input type="hidden" name="account_id" value="{$pop3_account->id}">
		{/if}
	</legend>

	<table cellpadding="2" cellspacing="0" border="0">
		<tr>
			<td width="0%" nowrap="nowrap"><b>Enabled:</b></td>
			<td width="100%"><input type="checkbox" name="pop3_enabled" value="1" {if $pop3_account->enabled || empty($pop3_account)}checked{/if}></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap"><b>Nickname:</b></td>
			<td width="100%"><input type="text" name="nickname" value="{$pop3_account->nickname}" size="45"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap"><b>Protocol:</b></td>
			<td width="100%"><select name="protocol">
				<option value="pop3" {if $pop3_account->protocol=='pop3'}selected{/if}>POP3
				<option value="pop3-ssl" {if $pop3_account->protocol=='pop3-ssl'}selected{/if}>POP3-SSL
				<option value="imap" {if $pop3_account->protocol=='imap'}selected{/if}>IMAP
				<option value="imap-ssl" {if $pop3_account->protocol=='imap-ssl'}selected{/if}>IMAP-SSL
			</select></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap"><b>Host:</b></td>
			<td width="100%"><input type="text" name="host" value="{$pop3_account->host}" size="45"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap"><b>Username:</b></td>
			<td width="100%"><input type="text" name="username" value="{$pop3_account->username}"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap"><b>Password:</b></td>
			<td width="100%"><input type="password" name="password" value="{$pop3_account->password}"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap"><b>Port:</b></td>
			<td width="100%"><input type="text" name="port" value="{$pop3_account->port}" size="5"> (leave blank for default)</td>
		</tr>
		{if !empty($pop3_account->id)}
		<tr>
			<td width="0%" nowrap="nowrap"><b>Delete:</b></td>
			<td width="100%"><label style="background-color:rgb(255,220,220);"><input type="checkbox" name="delete" value="{$pop3_account->id}"> Delete this mail account</label></td>
		</tr>
		{/if}
	</table>
	<br>
	
	<div class="status"></div>	

	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
	<button type="button" class="tester"><span class="cerb-sprite2 sprite-gear"></span> Test Mailbox</button>
</fieldset>

<script type="text/javascript">
$('#configMailbox BUTTON.submit')
	.click(function(e) {
		genericAjaxPost('configMailbox','',null,function(json) {
			$o = $.parseJSON(json);
			if(false == $o || false == $o.status) {
				Devblocks.showError('#configMailbox div.status',$o.error);
			} else {
				document.location.href = '{devblocks_url}c=config&a=mail_pop3{/devblocks_url}';
			}
		});
	})
;
$('#configMailbox BUTTON.tester')
	.click(function(e) {
		$this = $(this);
		$this.hide();

		Devblocks.showSuccess('#configMailbox div.status', "Testing mailbox... please wait.", false, false);
		
		$frm = $('#configMailbox');
		$action = $frm.find('input:hidden[name=action]');
		$action.val('testMailboxJson');
		
		genericAjaxPost('configMailbox','',null,function(json) {
			$o = $.parseJSON(json);
			if(false == $o || false == $o.status) {
				Devblocks.showError('#configMailbox div.status',$o.error);
			} else {
				Devblocks.showSuccess('#configMailbox div.status','Connected to your mailbox successfully!');
			}
			
			$action.val('saveMailboxJson');
			$this.show();
		});
	})
;
</script>
