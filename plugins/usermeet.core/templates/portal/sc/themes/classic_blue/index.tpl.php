<html>
<head>
	<meta content="text/html; charset={$smarty.const.LANG_CHARSET_CODE}" http-equiv="content-type">
	<title>{$page_title}</title>
	
	<style>
	{literal}
		BODY, TD {
			font-family: Tahoma, Verdana, Arial;
			font-size:12px;
			color: rgb(60,60,60);
		}

		H1 {
			font-size:130%;
			font-weight:bold;
			color: rgb(5,37,114);
			margin-top:0px;
			margin-bottom:3px;
		}
		
		H2 {
			font-size:120%;
			color: rgb(60,60,60);
			margin-top:0px;
			margin-bottom:3px;
		}

		#menu TD A {
		}

		#content {
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
			width:220px;
			border: 1px solid #2222CC;
		}

		TABLE.box TH {
			background-image: url('{/literal}{devblocks_url}c=resource&p=usermeet.core&f=images/classic_blue/boxtitle_bg.gif{/devblocks_url}{literal}');
			background-repeat: repeat-x;
			background-color: rgb(5,37,114);
			font-size:100%;
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
			color: rgb(7,39,115);
		}
		
		TABLE.box BUTTON {
			width:98%;
			text-align:center;
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
			text-align:right;
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
	<a href="{devblocks_url}c=home{/devblocks_url}"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/logo.jpg{/devblocks_url}" alt="Logo" border="0"></a><br>
{else}
	<a href="{devblocks_url}c=home{/devblocks_url}"><img src="{$logo_url}" alt="Logo" border="0"></a><br>
{/if}
</div>

<table cellpadding="5" cellspacing="0" border="0" width="100%" align="center">
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">
		
			<!-- Menu -->
			<table cellpadding="0" cellspacing="0" border="0" class="box" id="menu">
				<tr>
					<th>Main Menu</th>
				</tr>
				<tr>
				<td>
				{foreach from=$menu item=item name=menu}
					{if !empty($item.icon)}<img src="{devblocks_url}c=resource&p=usermeet.core&f={$item.icon}{/devblocks_url}" align="top">{/if} <a href="{devblocks_url}c={$item.uri}{/devblocks_url}">{$item.menu_title|capitalize}</a><br>
				{/foreach}
				</td>
				</tr>
			</table>
			<br>
			
			<!-- Search Box -->
			{if $show_search}
			<form action="{devblocks_url}{/devblocks_url}" method="post">
			<input type="hidden" name="a" value="doSearch">
			<table cellpadding="0" cellspacing="0" border="0" class="box" id="searchBox">
				<tr>
					<th>Search Help</th>
				</tr>
				<tr>
					<td>
						Keywords:<br>
						<input type="text" name="query" style="width:98%;border:1px solid rgb(153,153,153);"><br>
						<button type="submit" style="width:98%;text-align:center;background-color:rgb(255,255,255);">Find</button><br>
						(Enter keywords separated by a space. For example: <i>product warranty</i>)<br>						
					</td>
				</tr>
			</table>
			</form>
			{/if}
			
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
					<td><button type="submit">Click to log out</button></td>
				</tr>
			</table>
			</form> 
			{else}
			<form action="{devblocks_url}{/devblocks_url}" method="post">
			<input type="hidden" name="a" value="doLogin">
			<table cellpadding="0" cellspacing="0" border="0" class="box">
				<tr>
					<th width="100%" colspan="2">Log in</th>
				</tr>
				<tr>
					<td width="0%">E-mail:</td>
					<td width="100%"><input type="text" name="email" style="width:98%;border:1px solid rgb(153,153,153);"></td>
				</tr>
				<tr>
					<td width="0%">Password:</td>
					<td width="100%"><input type="password" name="pass" style="width:98%;border:1px solid rgb(153,153,153);"></td>
				</tr>
				<tr>
					<td width="100%" colspan="2"><button type="submit">Click to log in</button></td>
				</tr>
				<tr>
					<td width="100%" colspan="2" align="center">
						<a href="{devblocks_url}c=register{/devblocks_url}">register</a> | <a href="{devblocks_url}c=register&a=forgot{/devblocks_url}">forgot?</a>
					</td>
				</tr>
			</table>
			</form>
			{/if}
			{/if}
			
		</td>
		
		<td width="99%" valign="top">
			<div id="content">
			{$module->writeResponse($module_response)}
			</div>
		</td>
	</tr>
	
	<tr>
		<td colspan="2" id="footer">{$footer_html}</td>
	</tr>

	<tr>
		<td colspan="2" id="tagline">powered by <a href="http://www.cerberusweb.com/" target="_blank">cerberus helpdesk 4.0</a></td>
	</tr>
	
</table>

<br>

</body>

</html>