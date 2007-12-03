<html>
<head>
	<title>{$page_title}</title>
	
	<style>
	{literal}
		BODY, TD {
			font-family: Tahoma, Verdana, Arial;
			font-size: 14px;
			color: #666666;
		}

		H1 {
			font-size:16px;
			font-weight:bold;
			color: rgb(51,102,0);
			margin-top:0px;
			margin-bottom:3px;
		}
		
		H2 {
			font-size:14px;
			color: rgb(80,80,80);
			margin-top:0px;
			margin-bottom:3px;
		}

		#menu TD A {
			font-size:11px;
		}

		#content {
			font-size:14px;
			border:1px solid rgb(204,204,204);
			padding:5px;
		}

		#content A {
			color:rgb(50,50,50);
		}

		#logo {
			padding: 2px;
		}

		TABLE.box {
			width:200px;
			font-size: 12px;
			border: 1px solid rgb(78,103,4);
		}

		TABLE.box TH {
			background-image: url('{/literal}{devblocks_url}c=resource&p=usermeet.sc&f=images/classic_green/boxtitle_bg.gif{/devblocks_url}{literal}');
			background-repeat: repeat-x;
			background-color: rgb(83,109,6);
			
			font-family: Tahoma, Verdana, Arial;
			font-size: 11px;
			font-weight: bold;
			line-height: 22px;
			padding-left: 6px;
			text-align: left;
				
			color: rgb(255,255,255);
		}
		
		TABLE.box TD {
			background-color: rgb(238,238,238);
			padding: 3px;
		}
		
		TABLE.box TD A {
			color: rgb(51,102,0);
		}
		
		TABLE.box BUTTON {
			width:98%;
			text-align:center;
			font-size:11px;
			background-color:rgb(255,255,255);
			border-top:1px solid rgb(204,204,204);	
			border-left:1px solid rgb(204,204,204);
			border-right:1px solid rgb(102,102,102);
			border-bottom:1px solid rgb(102,102,102);
		}
		
		#footer {
			padding-bottom:15px;
			text-align:center;
		}
		
		#tagline {
			padding-top:10px;
			width:98%;
			background-color:rgb(244,244,244);
			color:rgb(102,102,102);
			padding:5px;
		}
		
		#tagline A {
			color:rgb(102,102,102);
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

<div align="center" id="logo">
{if empty($logo_url)}
	<a href="{devblocks_url}c=home{/devblocks_url}"><img src="{devblocks_url}c=resource&p=usermeet.sc&f=images/logo.jpg{/devblocks_url}" alt="Logo" border="0"></a><br>
{else}
	<a href="{devblocks_url}c=home{/devblocks_url}"><img src="{$logo_url}" alt="Logo" border="0"></a><br>
{/if}
</div>

<table cellpadding="5" cellspacing="0" border="0" width="800" align="center">
	<tr>
		<td width="200" valign="top">
		
			<!-- Menu -->
			<table cellpadding="0" cellspacing="0" border="0" class="box" id="menu">
				<tr>
					<th>Main Menu</th>
				</tr>
				<tr>
				<td>
				{foreach from=$menu item=item name=menu}
					{if !empty($item.icon)}<img src="{devblocks_url}c=resource&p=usermeet.sc&f={$item.icon}{/devblocks_url}" align="top">{/if} <a href="{devblocks_url}c={$item.uri}{/devblocks_url}">{$item.menu_title|capitalize}</a><br>
				{/foreach}
				</td>
				</tr>
			</table>
			<br>
			
			<!-- Login Form -->
			{if $allow_logins}
			{if !empty($active_user)}
			<form action="{devblocks_url}{/devblocks_url}" method="post" name="loginForm">
			<input type="hidden" name="a" value="doLogout">
			<table cellpadding="0" cellspacing="0" border="0" class="box">
				<tr>
					<th>Logged in</th>
				</tr>
				<tr>
					<td><button type="submit">Log out</button></td>
				</tr>
			</table>
			</form> 
			{else}
			<form action="{devblocks_url}{/devblocks_url}" method="post">
			<input type="hidden" name="a" value="doLogin">
			<table cellpadding="0" cellspacing="0" border="0" class="box">
				<tr>
					<th colspan="2">Login</th>
				</tr>
				<tr>
					<td>E-mail:</td>
					<td><input type="text" name="email" style="width:98%;border:1px solid rgb(153,153,153);"></td>
				</tr>
				<tr>
					<td>Password:</td>
					<td><input type="password" name="pass" style="width:98%;border:1px solid rgb(153,153,153);"></td>
				</tr>
				<tr>
					<td colspan="2"><button type="submit">Log in</button></td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<a href="{devblocks_url}c=register{/devblocks_url}">register</a> | <a href="{devblocks_url}c=register&a=forgot{/devblocks_url}">forgot?</a>
					</td>
				</tr>
			</table>
			</form>
			{/if}
			{/if}
			
			<!-- Search Box -->
			<form action="{devblocks_url}{/devblocks_url}" method="post">
			<input type="hidden" name="a" value="doSearch">
			<table cellpadding="0" cellspacing="0" border="0" class="box" id="searchBox">
				<tr>
					<th>Search</th>
				</tr>
				<tr>
					<td><input type="text" name="query" style="width:98%;border:1px solid rgb(153,153,153);"></td>
				</tr>
				<tr>
					<td><button type="submit" style="width:98%;text-align:center;background-color:rgb(255,255,255);">Find</button></td>
				</tr>
			</table>
			</form>
			
		</td>
		
		<td width="600" valign="top">
			<div id="content">
			{$module->writeResponse($module_response)}
			</div>
		</td>
	</tr>
	
	<tr>
		<td colspan="2" id="tagline">powered by <a href="http://www.cerberusweb.com/" target="_blank">cerberus helpdesk 4.0</a></td>
	</tr>

	<tr>
		<td colspan="2" id="footer">{$footer_html}</td>
	</tr>
	
</table>

<br>

</body>

</html>