<html>
<head>
	<meta content="text/html; charset={$smarty.const.LANG_CHARSET_CODE}" http-equiv="content-type">
	<title>{$page_title}</title>
	<script type="text/javascript" src="{devblocks_url}c=resource&p=usermeet.core&f=js/jquery.js{/devblocks_url}"></script>
	<script type="text/javascript" src="{devblocks_url}c=resource&p=usermeet.core&f=js/jquery.MultiFile.pack.js{/devblocks_url}"></script>
	<script type="text/javascript" src="{devblocks_url}c=resource&p=usermeet.core&f=js/jquery.validate.pack.js{/devblocks_url}"></script>
	<script type="text/javascript" src="{devblocks_url}c=resource&p=usermeet.core&f=js/cerb4.common.js{/devblocks_url}"></script>
	
	<style type='text/css'>
		{include file="devblocks:usermeet.core:portal_{$portal_code}:support_center/style.css.tpl"}
	</style>
</head>

<body>
{include file="devblocks:usermeet.core:portal_{$portal_code}:support_center/header.tpl"}

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
					<a href="{devblocks_url}c={$item->manifest->params.uri}{/devblocks_url}" {if !empty($module) && 0==strcasecmp($module->manifest->params.uri,$item->manifest->params.uri)}class="selected"{/if}>{$item->manifest->params.menu_title|devblocks_translate|capitalize}</a>
					<br>
				{/foreach}
				</td>
				</tr>
			</table>
			<br>
			{/if}
			
			<!-- Sidebar -->
			{if !empty($module) && method_exists($module,'renderSidebar')}
			{$module->renderSidebar($module_response)}
			{/if}						
			
			<!-- Login Form -->
			{if !empty($login_extension)}
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
				<br>
			{else}
				<form action="{devblocks_url}c=login{/devblocks_url}" method="post">
					{if !empty($login_extension) && method_exists($login_extension,'renderLoginForm')}
					{$login_extension->renderLoginForm()}
					{/if}
				</form>
				<br>
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
</table>

{include file="devblocks:usermeet.core:portal_{$portal_code}:support_center/footer.tpl"}

<div id="tagline" align="right">
	<a href="http://www.cerberusweb.com/" target="_blank"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/_wgm/logo_small.gif{/devblocks_url}" border="0"></a>
</div>

<br>

</body>

</html>
