<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top">
			<table cellpadding="2" cellspacing="0" border="0" width="100%" class="tableBlue">
				<tr>
					<td class="tableThBlue" nowrap="nowrap">POP3 Accounts</td>
				</tr>
				<tr>
					<td style="background-color:rgb(220, 220, 255);border-bottom:1px dotted rgb(0, 153, 51);"><a href="javascript:;" onclick="configAjax.getPop3Account('0');">add new account</a></td>
				</tr>
				<tr>
					<td>
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
		</td>
		
		<td width="100%" valign="top">
			<form action="index.php#pop3" method="post" id="configPop3">
				{include file="$path/configuration/workflow/edit_pop3_account.tpl.php" pop3_account=null}
			</form>
		</td>
		
	</tr>
</table>

