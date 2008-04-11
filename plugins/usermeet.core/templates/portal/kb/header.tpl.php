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
	</td>
</tr>
</table>
<table id="kbNavMenu" style="" border="0" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td>
				 &nbsp;<img src="{devblocks_url}c=resource&p=usermeet.core&f=images/book_open2.gif{/devblocks_url}" alt="Home" align="top"> <a href="{devblocks_url}{/devblocks_url}">home</a> 
				 | <img src="{devblocks_url}c=resource&p=usermeet.core&f=images/book_blue_view.gif{/devblocks_url}" alt="Search" align="top"> <a href="{devblocks_url}c=search{/devblocks_url}">search</a> 
			</td>
		</tr>
	</tbody>
</table>
