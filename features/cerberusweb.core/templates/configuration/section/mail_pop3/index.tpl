<h2>POP3 Accounts</h2>

<form onsubmit="return false;" style="margin-bottom:5px;">
<button type="button" onclick="genericAjaxGet('configMailbox','c=config&a=handleSectionAction&section=mail_pop3&action=getMailbox&id=0');"><span class="cerb-sprite sprite-check"></span> {'common.add'|devblocks_translate|capitalize}</button>
</form>

<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
			<fieldset>
				<legend>Accounts</legend>
				
				<ul style="margin:0;padding:0;list-style:none;">
				{if !empty($pop3_accounts)}
					{foreach from=$pop3_accounts item=pop3}
						<li style="line-height:150%;">
							<a href="javascript:;" onclick="genericAjaxGet('configMailbox','c=config&a=handleSectionAction&section=mail_pop3&action=getMailbox&id={$pop3->id}');" style="{if !$pop3->enabled}font-style:italic;color:rgb(150,0,0);{/if}">{$pop3->nickname}</a>
						</li>
					{/foreach}
				{/if}
				</ul>
			</fieldset>
		</td>
		
		<td width="99%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configMailbox" onsubmit="return false;">
				{include file="devblocks:cerberusweb.core::configuration/section/mail_pop3/edit_pop3_account.tpl" pop3=null}
			</form>
		</td>
	</tr>
</table>

