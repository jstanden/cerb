<table cellpadding="0" cellspacing="0" border="0" width="100%" class="headerMenu">
	<tr>
		{foreach from=$modules item=module}
			{if !empty($module->manifest->params.menutitle)}
				<td width="0%" nowrap="nowrap" {if $activeModule==$module->id}id="headerMenuSelected"{/if}><img src="{$smarty.const.DEVBLOCKS_WEBPATH}images/spacer.gif" width="10" height="1"><a href="{$module->getLink()}">{$module->manifest->params.menutitle|lower}</a><img src="{$smarty.const.DEVBLOCKS_WEBPATH}images/spacer.gif" width="10" height="1"></td>
				<td width="0%" nowrap="nowrap" valign="bottom"><img src="{$smarty.const.DEVBLOCKS_WEBPATH}images/menuSep.gif"></td>
			{/if}
		{/foreach}
		<td width="100%"><img src="{$smarty.const.DEVBLOCKS_WEBPATH}images/spacer.gif" height="22" width="1"></td>
	</tr>
</table>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr><td class="headerUnderline"><img src="{$smarty.const.DEVBLOCKS_WEBPATH}images/spacer.gif" height="5" width="1"></td></tr>
</table>
<img src="{$smarty.const.DEVBLOCKS_WEBPATH}images/spacer.gif" height="5" width="1"><br>