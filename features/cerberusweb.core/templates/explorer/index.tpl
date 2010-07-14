<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">	
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={$smarty.const.LANG_CHARSET_CODE}">
		<meta http-equiv="Cache-Control" content="no-cache">
		
		{*
		<!-- [TODO] Title -->
		<title>{$settings->get('cerberusweb.core','helpdesk_title')}</title>
		*}
		<title>{$title|escape}</title>
		<link type="image/x-icon" rel="shortcut icon" href="{devblocks_url}favicon.ico{/devblocks_url}">
		
		<script type="text/javascript">
			var DevblocksAppPath = '{$smarty.const.DEVBLOCKS_WEBPATH}';
		</script>

		<!-- Platform -->
		<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=devblocks.core&f=css/jquery-ui.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
		<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/jquery/jquery.combined.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
		<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/devblocks.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>

		<!-- Application -->
		<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/cerberus.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">

		<style type="text/css">
			BODY { margin:0px; padding:0px; }
			IFRAME { width:100%; height:100%; border: 0; }
		</style>
	</head>
	
	<body>
		<table height="100%" width="100%" cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td style="height:50px;">
					<div class="block">
					<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tr>
							<td width="1%" nowrap="nowrap" align="left" style="padding-right:20px;padding-bottom:5px;">
								<a href="{if !empty($return_url)}{$return_url|escape}{else}{devblocks_url}{/devblocks_url}{/if}"><span class="cerb-sprite sprite-logo_small"></span></a>
							</td>
							<td align="left" width="98%;" valign="top">
								<h2 style="display:inline;">{$title|escape}</h2> &nbsp; 
								<a href="{$url|escape}" target="_blank">{$url|truncate:128:'...':false|escape}</a>
								<br>
								{if !empty($toolbar_extension) && !empty($item) && method_exists($toolbar_extension, 'render')}
									{$toolbar_extension->render($item)}
								{/if}
							</td>
							<td width="1%" nowrap="nowrap" align="right" valign="top" style="padding-right:10px;padding-top:10px;">
								{if !empty($count)}
								<form action="#" method="get">
								{if $prev}<button id="btnExplorerPrev" type="button" onclick="this.form.action='{devblocks_url}c=explore&hash={$hashset}&p={$prev}{/devblocks_url}';this.form.submit();">&laquo;</button>{/if}
								<b>{$p}</b> of <b>{$count}</b> 
								{if $next}<button id="btnExplorerNext" type="button" onclick="this.form.action='{devblocks_url}c=explore&hash={$hashset}&p={$next}{/devblocks_url}';this.form.submit();">&raquo;</button>{/if}
								</form>
								{/if}
							</td>
							<td style="padding-right:10px;padding-top:10px;" valign="top">
								<form action="{if !empty($url)}{$url|escape}{else}{$return_url}{/if}" method="get">
								<button type="button" onclick="this.form.submit();"> X </button>
								</form>
							</td>
						</tr>
					</table>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<iframe id="explorerFrame" src="{$url|escape}" frameborder="0"></iframe>
				</td>
			</tr>
		</table>
	</body>
</html>

<script type="text/javascript">
var keys = function(event) {
	// Don't fire if we're inside any form elements
	if($(event.target).filter(':input').length > 0)
		return;
	
	switch(event.which) {
		case 91:
			$('#btnExplorerPrev').click();
			event.stopPropagation();
			break;
		case 93:
			$('#btnExplorerNext').click();
			event.stopPropagation();
			break;
	}
}
	
$(document).ready(function(e) {
	// The outer frame document
	$(document).keypress(keys);
	
	$('#explorerFrame').load(function() {
		try {
			$('#explorerFrame').contents().find('body').parent().keypress(keys);
		} catch(e) { }
	});
	
	$('#explorerFrame').focus();
});
</script>
