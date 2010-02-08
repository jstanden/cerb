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
  
  <link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/jquery/redmond/jquery-ui-1.7.2.custom.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
  <link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/jquery/jquery.rte.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
  <link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/cerberus.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
  
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=js/jquery/jquery.combined.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
  
  <!--[if IE]><script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=js/flot/excanvas.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script><![endif]-->
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=js/flot/jquery.flot.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
  
  <script language="javascript" type="text/javascript">
    {include file="libs/devblocks/api/devblocks.tpl.js"}
  </script>
  
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/cerberus/cerberus.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
</head>

<body>
