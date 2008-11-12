<html>
<head>
	<meta content="text/html; charset={$smarty.const.LANG_CHARSET_CODE}" http-equiv="content-type">
	<title>{$page_title}</title>
	<style>
		{literal}
		BODY {
			margin:0px;
			background-color:rgb(28,28,28);
		}
		
		BODY, TD {
			font-family:Arial, Helvetica, sans-serif;
			font-size: 14px;
			color:rgb(255,255,255);
		}
		
		BODY A, TD A {
			color: rgb(255,255,255); /* 140,255,86 */
			font-weight:normal;
		}
		
		FORM {
			margin:0px;
		}
		
		#mainTable {
			width: 900px;
		}
		
		#headerTable {
			width:100%;
			background-color:rgb(117,168,15);
			color:rgb(174,254,7);
		}
		
		#headerTable A {
			color:rgb(174,254,7);
		}
	
		#headerBand {
			background-color:rgb(79,118,3);
			height:5px;
		}
		
		#headerLoginTable {
			color: rgb(174,254,7);
		}
		
		#headerLoginTable BUTTON {
			height:24px;
		}
		
		#headerLoginTable INPUT {
			border: 1px solid rgb(200,200,200);
			height:24px;
		}
		
		#menu {
			color: rgb(160,160,160);
			padding-top:5px;
			padding-bottom:10px;
		}
	
		#menu A {
			color: rgb(235,235,235);
		}
		
		#logo {
			margin:0px;
			padding:0px;
			border:0px;
		}
		
		#content {
			background-color:rgb(148,148,148);
			background-image: url('{/literal}{devblocks_url}c=resource&p=usermeet.core&f=images/spook/bg_graygrad.jpg{/devblocks_url}{literal}');
			background-repeat: repeat-x;
		}
		
		#contentCell {
			padding:5px;
			padding-bottom:30px;
		}
		
		#content H1 {
			color:rgb(254,229,5);
			font-size:130%;
			margin-top:0px;
			margin-bottom:0px;
		}
		
		#content H2 {
			color:rgb(240,240,240);
			font-size:120%;
			margin-top:0px;
			margin-bottom:0px;
		}
		
		#sidebar {
			padding:10px;
			background-color:rgb(47,47,47);
		}
		
		#sidebar .header {
			background-image: url('{/literal}{devblocks_url}c=resource&p=usermeet.core&f=images/spook/bg_orangegrad.jpg{/devblocks_url}{literal}');
			background-repeat: repeat-x;
			border-bottom:2px solid rgb(155,155,155);
		}
	
		#sidebar .header H2 {
			color:rgb(255,255,255);
			font-size:16px;
			margin:5px;
		}
		
		#sidebar .body {
			padding:8px;
			background-color:rgb(231,231,231);
		}
		
		#sidebar .footer {
			background-color:rgb(160,160,160);
			height:2px;
		}
		
		#searchBox INPUT {
			border: 1px solid rgb(200,200,200);
			height:24px;
			width:150px;
		}
		
		#searchBox BUTTON {
			height:24px;
		}
		
		#footerBand {
			background-color:rgb(94,94,94);
			height:8px;
		}
		
		#footer {
			color:rgb(155,155,155);
			background-color:rgb(66,66,66);
			padding:5px;
		}
		
		#footer A {
			color:rgb(200,200,200);
		}
		
		#footer B {
			color:rgb(255,255,255);
		}
		
		#tagline {
			color:rgb(146,146,146);
			font-size: 75%;
			text-align: right;
		}
		
		#tagline A {
			color:rgb(143,181,80);
		}
		
		/* Cerb4 Styles */
		DIV.error {
			border:1px solid rgb(180,0,0);
			background-color:rgb(255,235,235);
			color:rgb(180,0,0);
			font-weight:bold;
			margin:10px;
			padding:5px;
		}
		
		DIV.success {
			border:1px solid rgb(0,180,0);
			background-color:rgb(235,255,235);
			color:rgb(0,180,0);
			font-weight:bold;
			margin:10px;
			padding:5px;
		}
		{/literal}
	</style>
</head>

<body>

<table cellpadding="0" cellspacing="0" border="0" id="mainTable">
	<tr>
		<td>
			<table cellpadding="2" cellspacing="1" border="0" id="headerTable">
				<tr>
					<td>
					{if empty($logo_url)}
						<a href="{devblocks_url}c=home{/devblocks_url}"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/spook/logo.jpg{/devblocks_url}" alt="Logo" border="0"></a><br>
					{else}
						<a href="{devblocks_url}c=home{/devblocks_url}"><img src="{$logo_url}" alt="Logo" border="0"></a><br>
					{/if}
					</td>
					<td align="right" valign="bottom">
						<!-- Login -->
						{if $allow_logins}
						{if !empty($active_user)}
						<form action="{devblocks_url}{/devblocks_url}" method="post" name="loginForm">
						<input type="hidden" name="a" value="doLogout">
		   				{assign var=tagged_active_user_email value="<b>"|cat:$active_user->email|cat:"</b>"}
						{'portal.sc.public.themes.logged_in_as'|devblocks_translate:$tagged_active_user_email}   							
						[ <a href="javascript:;" onclick="document.loginForm.submit();" style="color:rgb(255,255,255);">{$translate->_('portal.sc.public.themes.logout')}</a> ]
						</form> 
						{else}
						<form action="{devblocks_url}{/devblocks_url}" method="post">
						<input type="hidden" name="a" value="doLogin">
						<table cellpadding="1" cellspacing="0" border="0" id="headerLoginTable">
							<tr>
								<td>{$translate->_('portal.sc.public.themes.login')}</td>
								<td>{$translate->_('common.password')|lower}</td>
								<td></td>
							</tr>
							<tr>
								<td><input type="text" name="email" size="12"></td>
								<td><input type="password" name="pass" size="12"></td>
								<td valign="bottom"><button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/lock.gif{/devblocks_url}"></button></td>
							</tr>
						</table>
						</form>
						
						<a href="{devblocks_url}c=register{/devblocks_url}">{$translate->_('portal.sc.public.register')|lower}</a> | <a href="{devblocks_url}c=register&a=forgot{/devblocks_url}">{$translate->_('portal.sc.public.themes.forgot')}</a>
						{/if}
						{/if}
					</td>
				</tr>
			</table>
		</td>
	</tr>
	
	<!-- Header Band -->
	<tr><td id="headerBand"></td></tr>
	
	<!-- Content -->
	<tr>
		<td id="content">
			<table cellpadding="0" cellspacing="0" border="0" width="100%">
				<tr>
					<!-- Main Content -->
					<td width="99%" valign="top" id="contentCell">
						<div id="menu">
						 {foreach from=$menu item=item name=menu}
						 	{if !empty($item.icon)}<img src="{devblocks_url}c=resource&p=usermeet.core&f={$item.icon}{/devblocks_url}" align="top">{/if} <a href="{devblocks_url}c={$item.uri}{/devblocks_url}">{$item.menu_title}</a>
						 	{if !$smarty.foreach.menu.last} | {/if} 
						 {/foreach}
						</div>
					
						{$module->writeResponse($module_response)}
					</td>
					
					<!-- Sidebar -->
					{if $show_search}
					<td id="sidebar" width="1%" nowrap="nowrap" valign="top">
						{if $show_search}
						<table cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td class="header">
									<h2>{$translate->_('portal.sc.public.themes.search_resources')}</h2>
								</td>
							</tr>
							<tr>
								<td class="body">
									<form action="{devblocks_url}{/devblocks_url}" method="post">
									<input type="hidden" name="a" value="doSearch">
									<table cellpadding="0" cellspacing="0" border="0" id="searchBox">
										<tr>
											<td><input type="text" name="query" size="20"></td>
											<td><button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/data_view.gif{/devblocks_url}"></button></td>
										</tr>
									</table>
									</form>
								</td>
							</tr>
							<tr>
								<td class="footer"></td>
							</tr>
						</table>
						{/if}
					</td>
					{/if}
				</tr>
			</table>
		</td>
	</tr>
	
	<!-- Footer Band -->
	<tr><td id="footerBand"></td></tr>
	
	<!-- Footer -->
	<tr>
		<td id="footer">{$footer_html}</td>
	</tr>
	
	<tr>
		<td id="tagline">
			{assign var=linked_cerberus_helpdesk value="<a href=\"http://www.cerberusweb.com/\" target=\"_blank\">"|cat:"cerberus helpdesk 4.0"|cat:"</a>&trade;"}
			{'portal.public.powered_by'|devblocks_translate:$linked_cerberus_helpdesk}
		</td>
	</tr>
	
</table>

</body>

</html>
