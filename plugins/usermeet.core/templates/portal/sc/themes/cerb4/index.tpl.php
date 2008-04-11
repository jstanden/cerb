<html>
<head>
	<title>{$page_title}</title>
	<style>
		{literal}
		BODY {
			margin:0px;
			color:rgb(60,60,60);
			background-color:rgb(255,255,255);
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
			width: 100%;
		}
		
		#headerTable {
			width:100%;
			background-color:rgb(255,255,255);
			color:rgb(50,50,50);
			padding:2px;
		}
		
		#headerTable A {
			color:rgb(50,50,50);
		}
	
		#headerBand {
			background-color:rgb(200,200,200);
			height:2px;
		}
		
		#headerLoginTable {
			color: rgb(100,100,100);
		}
		
		#headerLoginTable BUTTON {
			height:24px;
		}
		
		#headerLoginTable INPUT {
			border: 1px solid rgb(200,200,200);
			height:24px;
		}
		
		#menu {
			color: rgb(100,100,100);
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
			background-image: url('{/literal}{devblocks_url}c=resource&p=usermeet.core&f=images/cerb4/bg_whitegray.jpg{/devblocks_url}{literal}');
			background-repeat: repeat-x;
		}
		
		#content H1 {
			color:rgb(244,47,0);
			font-size:120%;
			margin-top:0px;
			margin-bottom:0px;
		}
		
		#content H2 {
			color:rgb(80,80,80);
			font-size:120%;
			margin-top:0px;
			margin-bottom:0px;
		}
		
		#sidebar {
			padding-left:5px;
		}
		
		#sidebar .header {
			background-image: url('{/literal}{devblocks_url}c=resource&p=usermeet.core&f=images/cerb4/bg_dkgray_ltgray.jpg{/devblocks_url}{literal}');
			background-repeat: repeat-x;
			border-bottom:2px solid rgb(200,200,200);
		}
	
		#sidebar .header H2 {
			color:rgb(255,255,255);
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
			/*background-image: url('{/literal}{devblocks_url}c=resource&p=usermeet.core&f=images/cerb4/bg_blackgray.jpg{/devblocks_url}{literal}');*/
			/*background-repeat: repeat-x;*/
			background-color:rgb(200,200,200);
			height:5px;
		}
		
		#footer {
			color:rgb(100,100,100);
			background-color:rgb(230,230,230);
			padding:5px;
		}
		
		#footer A {
			color:rgb(80,80,80);
		}
		
		#footer B {
			color:rgb(80,80,80);
		}
		
		#tagline {
			color:rgb(50,50,50);
			text-align: right;
			padding:5px;
		}
		
		#tagline A {
			color:rgb(40,120,40);
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
						<a href="{devblocks_url}c=home{/devblocks_url}"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/logo.jpg{/devblocks_url}" alt="Logo" border="0"></a><br>
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
						Logged in as <b>{$active_user->email}</b> [ <a href="javascript:;" onclick="document.loginForm.submit();">logout</a> ]
						</form> 
						{else}
						<form action="{devblocks_url}{/devblocks_url}" method="post">
						<input type="hidden" name="a" value="doLogin">
						<table cellpadding="1" cellspacing="0" border="0" id="headerLoginTable">
							<tr>
								<td>login:</td>
								<td>password:</td>
								<td></td>
							</tr>
							<tr>
								<td><input type="text" name="email" size="12"></td>
								<td><input type="password" name="pass" size="12"></td>
								<td valign="bottom"><button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/lock.gif{/devblocks_url}"></button></td>
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
						 	{if !empty($item.icon)}<img src="{devblocks_url}c=resource&p=usermeet.core&f={$item.icon}{/devblocks_url}" align="top">{/if} <a href="{devblocks_url}c={$item.uri}{/devblocks_url}">{$item.menu_title}</a>
						 	{if !$smarty.foreach.menu.last} | {/if} 
						 {/foreach}
						</div>
					
						{$module->writeResponse($module_response)}
					</td>
					
					<!-- Sidebar -->
					<td id="sidebar" width="0%" nowrap="nowrap" valign="top">
						{if $show_search}
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
						{/if}{*search*}
						
					</td>
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
		<td id="tagline">powered by <a href="http://www.cerberusweb.com/" target="_blank">cerberus helpdesk</a></td>
	</tr>
	
</table>

</body>

</html>