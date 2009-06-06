<html>
<head>
	<meta content="text/html; charset={$smarty.const.LANG_CHARSET_CODE}" http-equiv="content-type">
	<title>{$page_title}</title>
	<link rel="stylesheet" href="{devblocks_url}c=resource&p=usermeet.core&f=css/sc/default.css{/devblocks_url}">
	<script type="text/javascript" src="{devblocks_url}c=resource&p=usermeet.core&f=js/jquery.js{/devblocks_url}"></script>
	<script type="text/javascript" src="{devblocks_url}c=resource&p=usermeet.core&f=js/cerb4.common.js{/devblocks_url}"></script>
	
	{if !empty($style_css)}
	<style type='text/css'>
		{$style_css}
	</style>
	{/if}
</head>

<body>

<div align="center" id="logo">
{if empty($logo_url)}
	<a href="{devblocks_url}c=home{/devblocks_url}"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/_wgm/logo.gif{/devblocks_url}" alt="Logo" border="0"></a><br>
{else}
	<a href="{devblocks_url}c=home{/devblocks_url}"><img src="{$logo_url}" alt="Logo" border="0"></a><br>
{/if}
</div>

<table cellpadding="5" cellspacing="0" border="0" width="100%" align="center">
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">
		
			<!-- Menu -->
			{if !empty($menu)}
			<table cellpadding="0" cellspacing="0" border="0" class="sidebar" id="menu">
				<tr>
					<th>{$translate->_('portal.sc.public.themes.main_menu')}</th>
				</tr>
				<tr>
				<td>
				{foreach from=$menu item=item name=menu}
					{if !empty($item->manifest->params.icon)}<img src="{devblocks_url}c=resource&p={$item->manifest->plugin_id}&f={$item->manifest->params.icon}{/devblocks_url}" align="top" style="padding:1px;">{/if}
					<a href="{devblocks_url}c={$item->manifest->params.uri}{/devblocks_url}" {if !empty($module) && 0==strcasecmp($module->manifest->params.uri,$item->manifest->params.uri)}class="selected"{/if}>{$item->manifest->params.menu_title|capitalize}</a>
					<br>
				{/foreach}
				</td>
				</tr>
			</table>
			<br>
			{/if}
			
			<!-- Login Form -->
			{if $allow_logins}
			{if !empty($active_user)}
				<form action="{devblocks_url}c=logout{/devblocks_url}" method="post" name="loginForm">
				<table cellpadding="0" cellspacing="0" border="0" class="sidebar">
					<tr>
						<th>{$translate->_('portal.sc.public.themes.logged_in')}</th>
					</tr>
					<tr>
						<td><button type="submit">{$translate->_('portal.sc.public.themes.click_to_log_out')}</button></td>
					</tr>
				</table>
				</form> 
			{else}
				<form action="{devblocks_url}c=login{/devblocks_url}" method="post">
				<table cellpadding="0" cellspacing="0" border="0" class="sidebar">
					<tr>
						<th width="100%" colspan="2">{$translate->_('portal.sc.public.themes.log_in')}</th>
					</tr>
					<tr>
						<td width="0%">{$translate->_('common.email')|lower}:</td>
						<td width="100%"><input type="text" name="email" style="width:98%;border:1px solid rgb(153,153,153);"></td>
					</tr>
					<tr>
						<td width="0%">{$translate->_('common.password')|lower}:</td>
						<td width="100%"><input type="password" name="pass" style="width:98%;border:1px solid rgb(153,153,153);"></td>
					</tr>
					<tr>
						<td width="100%" colspan="2"><button type="submit">{$translate->_('portal.sc.public.themes.click_to_log_in')}</button></td>
					</tr>
					<tr>
						<td width="100%" colspan="2" align="center">
							{if in_array('sc.controller.register',$enabled_modules)}<a href="{devblocks_url}c=register{/devblocks_url}">{$translate->_('portal.sc.public.register')|lower}</a> | {/if} 
							<a href="{devblocks_url}c=register&a=forgot{/devblocks_url}">{$translate->_('portal.sc.public.themes.forgot')}</a>
						</td>
					</tr>
				</table>
				</form>
			{/if}
			{/if}
			
		</td>
		
		<td width="99%" valign="top">
			<div id="content">
			{if !empty($module)}
			{$module->writeResponse($module_response)}
			{/if}
			</div>
		</td>
	</tr>
	
	<tr>
		<td colspan="2" id="footer">{$footer_html}</td>
	</tr>

	<tr>
		<td colspan="2" id="tagline">
			<a href="http://www.cerberusweb.com/" target="_blank"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/_wgm/logo_small.gif{/devblocks_url}" border="0"></a>
		</td>
	</tr>
	
</table>

<br>

</body>

</html>
