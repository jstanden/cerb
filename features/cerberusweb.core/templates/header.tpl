<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset={$smarty.const.LANG_CHARSET_CODE}">
	<META HTTP-EQUIV="Cache-Control" CONTENT="no-cache">
	<!--
	<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
	<META HTTP-EQUIV="Pragma-directive" CONTENT="no-cache">
	<META HTTP-EQUIV="Cache-Directive" CONTENT="no-cache">
	<META HTTP-EQUIV="Expires" CONTENT="0">
	-->

  <title>{$settings->get('cerberusweb.core','helpdesk_title')}</title>
  <link type="image/x-icon" rel="shortcut icon" href="{devblocks_url}favicon.ico{/devblocks_url}">
  <link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/container/assets/skins/sam/container.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
  <link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/calendar/assets/skins/sam/calendar.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
  <link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/autocomplete/assets/skins/sam/autocomplete.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
  <link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/tabview/assets/skins/sam/tabview.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">  
  <link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/cerberus.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
  
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/utilities/utilities.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/element/element-beta-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script> 
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/calendar/calendar-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script> 
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/container/container-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script> 
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/tabview/tabview-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script> 
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/datasource/datasource-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>  
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/autocomplete/autocomplete-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/charts/charts-experimental-min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>  

  <script language="javascript" type="text/javascript">{php}DevblocksPlatform::printJavascriptLibrary();{/php}</script>
  
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/cerberus/cerberus.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/cerberus/display.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/cerberus/config.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
  
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/livevalidation/livevalidation.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
</head>

<body class="yui-skin-sam">
