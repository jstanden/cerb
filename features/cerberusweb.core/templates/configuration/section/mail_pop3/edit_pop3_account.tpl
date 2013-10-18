<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="mail_pop3">
<input type="hidden" name="action" value="saveMailboxJson">
<input type="hidden" name="do_delete" value="0">

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
		{if $pop3_account->enabled && $pop3_account->num_fails}
		<tr>
			<td colspan="2">
				<div class="ui-widget">
					<div class="ui-state-error ui-corner-all" style="padding: 0.7em; margin: 0.2em; "> 
						<span class="cerb-sprite2 sprite-exclamation-red"></span>
						<strong>Error!</strong>
						This mailbox has failed to check mail for {$pop3_account->num_fails} consecutive attempt{if $pop3_account->num_fails > 1}s{/if}.
					</div>
				</div>
			</td>
		</tr>
		{/if}
		<tr>
			<td width="0%" nowrap="nowrap"><b>Enabled:</b></td>
			<td width="100%">
				<input type="checkbox" name="pop3_enabled" value="1" {if $pop3_account->enabled || empty($pop3_account)}checked{/if}>
			</td>
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
		<tr>
			<td colspan="2" style="padding-top:10px;">
				<b>Note:</b> Messages in this mailbox will be deleted once they are downloaded. If this is not desirable 
				behavior (e.g. IMAP), please create a disposible mailbox to use instead and have copies of your incoming 
				mail sent to it.
			</td>
		</tr>
	</table>
	<br>
	
	<div class="status"></div>	

	{if !empty($pop3_account->id)}
	<fieldset class="delete" style="display:none;">
		<legend>Delete this mailbox?</legend>
		<p>Are you sure you want to permanently delete this mailbox?  This will not affect any mail you have already downloaded.</p>
		<button type="button" class="green"> {'common.yes'|devblocks_translate|capitalize}</button>
		<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('div.toolbar').fadeIn();"> {'common.no'|devblocks_translate|capitalize}</button>
	</fieldset>
	{/if}

	
	<div class="toolbar">
		<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		<button type="button" class="tester"><span class="cerb-sprite2 sprite-gear"></span> Test Mailbox</button>
		<button type="button" class="delete" onclick="$(this).closest('div.toolbar').hide().prev('fieldset.delete').fadeIn();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>
	</div>
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

$('#configMailbox FIELDSET.delete BUTTON.green').click(function(e) {
	var $frm=$(this).closest('form');
	$frm.find('input:hidden[name=do_delete]').val('1');
	$frm.find('BUTTON.submit').click();
});

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
