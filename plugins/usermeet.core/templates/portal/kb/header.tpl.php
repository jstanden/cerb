<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
	<title>{$page_title}</title>
	<style type="text/css">
	{literal}
		body { margin:0;padding:0; }
		form { margin: 0px; padding:0px; }
		a { color: rgb(51, 102, 255); }
		
		#kbNavMenu {
			border-bottom: 1px solid rgb(180, 180, 180);
			border-top: 1px solid rgb(180, 180, 180);
			text-align: left;
			width: 100%;
			padding:3px;
			background-color: rgb(235, 235, 255);
			color: color: rgb(50,50,50);
		}
		
		#kbArticle {
		}
		
		#kbNavMenu A {
			color: rgb(50,50,50);
		}
		
		#kbTagCloudNav A {
			color: rgb(50,50,50);
		}
		
		#kbTagCloud {
			padding: 10px;
		}
		
		#kbTagCloud A {
			color: rgb(80,180,0);
		}
		
		#kbTagCloudArticles {
			padding: 10px;	
		}
		
		#kbTagCloudArticles A {
		}
		
	{/literal}
	</style>

	<link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/fonts/fonts-min.css{/devblocks_url}">
	{if !empty($editor)}	
		{*<link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/autocomplete/assets/skins/sam/autocomplete.css{/devblocks_url}">*}
		<link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/container/assets/skins/sam/container.css{/devblocks_url}">
		<link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/menu/assets/skins/sam/menu.css{/devblocks_url}">
		<link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/button/assets/skins/sam/button.css{/devblocks_url}">
		<link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/editor/assets/skins/sam/editor.css{/devblocks_url}">
	{/if}
	
	{if !empty($editor)}
	  	<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/utilities/utilities.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script> 
		{*<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/autocomplete/autocomplete-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>*}
	  	<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/container/container-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script> 
	  	<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/menu/menu-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script> 
	  	<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/button/button-beta-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script> 
	  	<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/editor/editor-beta-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
	{/if}
</head>

<body class="yui-skin-sam">
<table cellpadding="5" cellspacing="0" border="0" width="100%">
<tr>
	<td>
		{if empty($logo_url)}
			<img src="{devblocks_url}c=resource&p=usermeet.core&f=images/cerb_logo.jpg{/devblocks_url}" alt="Logo"><br>
		{else}
			<img src="{$logo_url}" alt="Logo"><br>
		{/if}
	</td>
	<td align="right" valign="bottom">
		{if !empty($editor)}
		<form action="{devblocks_url}{/devblocks_url}" method="post" name="loginForm">
		<input type="hidden" name="a" value="doLogout">
		Logged in as <b>{$editor}</b> [ <a href="javascript:;" onclick="document.loginForm.submit();" style="color:rgb(0,0,0);">logout</a> ]
		</form> 
		{else}
		<a href="{devblocks_url}c=login{/devblocks_url}" style="color:rgb(0,0,0);">not logged in</a>
		{/if}
	</td>
</tr>
</table>
<table id="kbNavMenu" style="" border="0" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td>
				 &nbsp;<img src="{devblocks_url}c=resource&p=usermeet.core&f=images/book_open2.gif{/devblocks_url}" alt="Home" align="top"> <a href="{devblocks_url}{/devblocks_url}">home</a> 
				 | <img src="{devblocks_url}c=resource&p=usermeet.core&f=images/book_blue_view.gif{/devblocks_url}" alt="Search" align="top"> <a href="{devblocks_url}c=search{/devblocks_url}">search</a> 
				 {if !empty($editor)} | <img src="{devblocks_url}c=resource&p=usermeet.core&f=images/document_new.gif{/devblocks_url}" alt="New Article" align="top"> <a href="{devblocks_url}c=edit{/devblocks_url}">add new article</a>{/if} 
				 {if !empty($editor)} | <img src="{devblocks_url}c=resource&p=usermeet.core&f=images/gear.gif{/devblocks_url}" alt="Configuration" align="top"> <a href="{devblocks_url}c=config{/devblocks_url}">configuration</a>{/if} 
			</td>
		</tr>
	</tbody>
</table>
