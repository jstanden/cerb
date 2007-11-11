<html>
<head>
	<title>{$page_title}</title>
	<style>
		{literal}
		BODY {
			margin:0px;
			background-color:rgb(66,66,66);
		}
		
		BODY, TD {
			font-family:Arial, Helvetica, sans-serif;
			font-size: 14px;
		}
		
		BODY A, TD A {
			color: rgb(40,120,40);
		}
		
		FORM {
			margin:0px;
		}
		
		#mainTable {
			width: 900px;
			padding-bottom:50px;
		}
		
		#headerTable {
			width:100%;
			background-color:rgb(66,66,66);
			color:rgb(255,255,255);
		}
		
		#headerTable A {
			color:rgb(255,176,0);
		}
	
		#headerBand {
			background-color:rgb(166,166,166);
			height:5px;
		}
		
		#headerLoginTable {
			color: rgb(255,255,255);
		}
		
		#headerLoginTable BUTTON {
			height:24px;
		}
		
		#headerLoginTable INPUT {
			border: 1px solid rgb(200,200,200);
			height:24px;
		}
		
		#menu {
			color: rgb(127,127,127);
			padding-top:5px;
			padding-bottom:10px;
		}
	
		#menu A {
			color: rgb(120,120,120);
		}
		
		#logo {
			margin:0px;
			padding:0px;
			border:0px;
		}
		
		#content {
			padding:5px;
			padding-bottom:30px;
			background-color:rgb(255,255,255);
			background-image: url('{/literal}{devblocks_url}c=resource&p=usermeet.sc&f=images/cerb4/bg_whitegray.jpg{/devblocks_url}{literal}');
			background-repeat: repeat-x;
		}
		
		#content H1 {
			color:rgb(244,47,0);
			font-size:24px;
			margin-top:0px;
			margin-bottom:0px;
		}
		
		#sidebar {
			padding-left:5px;
		}
		
		#sidebar .header {
			background-image: url('{/literal}{devblocks_url}c=resource&p=usermeet.sc&f=images/cerb4/bg_dkgray_ltgray.jpg{/devblocks_url}{literal}');
			background-repeat: repeat-x;
			border-bottom:2px solid rgb(251,105,2);
		}
	
		#sidebar .header H2 {
			color:rgb(216,255,2);
			font-size:16px;
			margin:5px;
		}
		
		#sidebar .body {
			padding:8px;
			background-color:rgb(241,241,241);
		}
		
		#sidebar .footer {
			background-color:rgb(113,113,113);
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
			background-image: url('{/literal}{devblocks_url}c=resource&p=usermeet.sc&f=images/cerb4/bg_blackgray.jpg{/devblocks_url}{literal}');
			background-repeat: repeat-x;
			height:20px;
		}
		
		#footer {
			color:rgb(200,200,200);
			background-color:rgb(89,89,89);
			padding:5px;
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
						<a href="{devblocks_url}c=home{/devblocks_url}"><img src="{devblocks_url}c=resource&p=usermeet.sc&f=images/logo.jpg{/devblocks_url}" alt="Logo" border="0"></a><br>
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
						Logged in as <b>{$active_user->email}</b> [ <a href="javascript:;" onclick="document.loginForm.submit();" style="color:rgb(255,255,255);">logout</a> ]
						</form> 
						{else}
						<form action="{devblocks_url}{/devblocks_url}" method="post">
						<input type="hidden" name="a" value="doLogin">
						<table cellpadding="1" cellspacing="0" border="0" id="headerLoginTable">
							<tr>
								<td>login</td>
								<td>password</td>
								<td></td>
							</tr>
							<tr>
								<td><input type="text" name="email" size="12"></td>
								<td><input type="password" name="pass" size="12"></td>
								<td valign="bottom"><button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.sc&f=images/lock.gif{/devblocks_url}"></button></td>
							</tr>
						</table>
						</form>
						
						<a href="{devblocks_url}c=register{/devblocks_url}">register</a> | <a href="{devblocks_url}c=register&a=forgot{/devblocks_url}">forgot?</a>
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
					<td width="100%" valign="top">
						<div id="menu">
						 {foreach from=$menu item=item name=menu}
						 	{if !empty($item.icon)}<img src="{devblocks_url}c=resource&p=usermeet.sc&f={$item.icon}{/devblocks_url}" align="top">{/if} <a href="{devblocks_url}c={$item.uri}{/devblocks_url}">{$item.menu_title}</a>
						 	{if !$smarty.foreach.menu.last} | {/if} 
						 {/foreach}
						</div>
					
						{$module->writeResponse($module_response)}
					</td>
					
					<!-- Sidebar -->
					<td id="sidebar" width="0%" nowrap="nowrap" valign="top">
						<table cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td class="header">
									<h2>search resources</h2>
								</td>
							</tr>
							<tr>
								<td class="body">
									<form action="{devblocks_url}{/devblocks_url}" method="post">
									<input type="hidden" name="a" value="doSearch">
									<table cellpadding="0" cellspacing="0" border="0" id="searchBox">
										<tr>
											<td><input type="text" name="query" size="20"></td>
											<td><button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.sc&f=images/data_view.gif{/devblocks_url}"></button></td>
										</tr>
									</table>
									</form>
								</td>
							</tr>
							<tr>
								<td class="footer"></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	
	<!-- Footer Band -->
	<tr><td id="footerBand">&nbsp;</td></tr>
	
	<!-- Footer -->
	<tr>
		<td id="footer">{$footer_html}</td>
	</tr>
	
	<tr>
		<td id="tagline">powered by <a href="http://www.cerberusweb.com/" target="_blank">cerberus helpdesk 4.0</a></td>
	</tr>
	
</table>

</body>

</html>