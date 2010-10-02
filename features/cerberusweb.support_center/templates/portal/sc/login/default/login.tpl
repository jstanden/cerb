<table cellpadding="0" cellspacing="0" border="0" class="sidebar">
	<tr>
		<th width="100%" colspan="2">{$translate->_('portal.sc.public.themes.log_in')}</th>
	</tr>
	<tr>
		<td width="0%">{$translate->_('common.email')|lower}:</td>
		<td width="100%"><input type="text" name="email" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="0%">{$translate->_('common.password')|lower}:</td>
		<td width="100%"><input type="password" name="pass" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="100%" colspan="2"><button type="submit">{$translate->_('portal.sc.public.themes.click_to_log_in')}</button></td>
	</tr>
	<tr>
		<td width="100%" colspan="2" align="center">
			{assign var=mod_register value='sc.controller.register'}
			{if isset($visible_modules.$mod_register)}<a href="{devblocks_url}c=register{/devblocks_url}">{$translate->_('portal.sc.public.register')|lower}</a> | {/if} 
			<a href="{devblocks_url}c=register&a=forgot{/devblocks_url}">{$translate->_('portal.sc.public.themes.forgot')}</a>
		</td>
	</tr>
</table>
