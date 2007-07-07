<div id="tourHeaderMenu"></div>
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="headerMenu">
	<tr>
		{if !empty($visit)}
			{foreach from=$pages item=m}
				{if !empty($m->manifest->params.menutitle)}
					<td width="0%" nowrap="nowrap" {if $page->id==$m->id}id="headerMenuSelected"{/if}><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="10" height="1"><a href="{devblocks_url}c={$m->manifest->params.uri}{/devblocks_url}">{$m->manifest->params.menutitle|lower}</a><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="10" height="1"></td>
					<td width="0%" nowrap="nowrap" valign="bottom"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/menuSep.gif{/devblocks_url}"></td>
				{/if}
			{/foreach}
		{/if}
		<td width="100%"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" height="22" width="1"></td>
	</tr>
</table>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr><td class="headerUnderline"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" height="5" width="1"></td></tr>
</table>
<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" height="5" width="1"><br>