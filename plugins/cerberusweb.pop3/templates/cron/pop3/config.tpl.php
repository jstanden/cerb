{if !empty($pop3_accounts)}
	{foreach from=$pop3_accounts item=pop3}
		{include file="$path/cron/pop3/edit_pop3_account.tpl.php" pop3_account=$pop3}
	{/foreach}
{/if}

{include file="$path/cron/pop3/edit_pop3_account.tpl.php" pop3_account=null}
