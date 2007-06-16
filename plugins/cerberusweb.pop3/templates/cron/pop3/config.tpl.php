<!-- 
<H3>Global Settings</H3>
<b>Max messages to download per mailbox check:</b><br>
<input type="text" name="max_messages" size="4" maxlength="3" value="{""}"><br>
<br>
-->
 
{if !empty($pop3_accounts)}
	{foreach from=$pop3_accounts item=pop3}
		{include file="$path/cron/pop3/edit_pop3_account.tpl.php" pop3_account=$pop3}
	{/foreach}
{/if}

{include file="$path/cron/pop3/edit_pop3_account.tpl.php" pop3_account=null}
