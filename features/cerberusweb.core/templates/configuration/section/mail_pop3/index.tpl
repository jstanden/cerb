<h2>Mailbox Accounts</h2>

<form onsubmit="return false;" style="margin-bottom:5px;">
<button type="button" onclick="genericAjaxGet('configMailbox','c=config&a=handleSectionAction&section=mail_pop3&action=getMailbox&id=0');"><span class="glyphicons glyphicons-circle-plus" style="color:rgb(0,180,0);"></span> {'common.add'|devblocks_translate|capitalize}</button>
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
							{if !$pop3->enabled}
							<span class="glyphicons glyphicons-circle-remove" style="font-size:16px;color:rgb(150,150,150);"></span>
							{elseif $pop3->num_fails}
							<span class="glyphicons glyphicons-circle-exclamation-mark" style="font-size:16px;color:rgb(200,0,0);"></span>
							{else}
							<span class="glyphicons glyphicons-circle-ok" style="font-size:16px;color:rgb(0,180,0);"></span>
							{/if}
							<a href="javascript:;" onclick="genericAjaxGet('configMailbox','c=config&a=handleSectionAction&section=mail_pop3&action=getMailbox&id={$pop3->id}');" style="{if !$pop3->enabled}font-style:italic;{/if}">{$pop3->nickname}</a>
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

