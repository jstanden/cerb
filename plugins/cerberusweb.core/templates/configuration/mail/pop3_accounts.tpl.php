<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top">
		
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0" width="100%">
				<tr>
					<td nowrap="nowrap"><h2>POP3 Accounts</h2></td>
				</tr>
				<tr>
					<td>[ <a href="javascript:;" onclick="configAjax.getPop3Account('0');">add new account</a> ]</td>
				</tr>
				<tr>
					<td nowrap="nowrap">
						<div style="margin:0px;padding:3px;height:150px;width:200px;overflow:auto;">
						{if !empty($pop3_accounts)}
							{foreach from=$pop3_accounts item=pop3}
							&#187; <a href="javascript:;" onclick="configAjax.getPop3Account('{$pop3->id}');">{$pop3->nickname}</a><br>
							{/foreach}
						{/if}
						</div>
					</td>
				</tr>
			</table>
			</div>
			
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}#pop3" method="post" id="configPop3">
				{include file="$path/configuration/mail/edit_pop3_account.tpl.php" pop3_account=null}
			</form>
		</td>
		
	</tr>
</table>
