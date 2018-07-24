<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">	
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={$smarty.const.LANG_CHARSET_CODE}">
		<meta http-equiv="Cache-Control" content="no-cache">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
		
		<title>{$title}</title>
		{$favicon_url = DevblocksPlatform::getPluginSetting('cerberusweb.core','helpdesk_favicon_url','')}
		{if empty($favicon_url)}
		<link type="image/x-icon" rel="shortcut icon" href="{devblocks_url}favicon.ico{/devblocks_url}">
		{else}
		<link type="image/x-icon" rel="shortcut icon" href="{$favicon_url}">
		{/if}

		<script type="text/javascript">
			var DevblocksAppPath = '{$smarty.const.DEVBLOCKS_WEBPATH}';
			var DevblocksWebPath = '{devblocks_url}{/devblocks_url}';
		</script>

		<!-- Platform -->
		<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=devblocks.core&f=css/jquery-ui.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
		<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/jquery/jquery.combined.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
		<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/devblocks.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>

		<!-- Application -->
		<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/cerb.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
		<script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=js/cerberus.js{/devblocks_url}?v={$smarty.const.APP_BUILD}&pl=2017021301"></script>

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
							<td width="1%" nowrap="nowrap" align="left" style="padding-right:10px;padding-bottom:5px;">
								<a href="{if !empty($return_url)}{$return_url}{else}{devblocks_url}{/devblocks_url}{/if}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/cerb_logo.png{/devblocks_url}?v={$smarty.const.APP_BUILD}" border="0" width="140" height="40"></span></a>
							</td>
							<td align="left" width="98%;" valign="top">
								<h2>{$title}</h2> &nbsp;
								{if !empty($content)}
									<a href="{$url}" target="_blank" rel="noopener">{$content}</a>
								{else} 
									<a href="{$url}" target="_blank" rel="noopener">{$url|truncate:100}</a>
								{/if} 
								<div style="margin-top:5px;">
								</div>
							</td>
							<td width="1%" nowrap="nowrap" align="right" valign="top" style="padding-right:10px;padding-top:10px;">
								{if !empty($count)}
								<form action="#" method="get">
								{if $prev}<button id="btnExplorerPrev" type="button" onclick="this.form.action='{devblocks_url}c=explore&hash={$hashset}&p={$prev}{/devblocks_url}';this.form.submit();"><span class="glyphicons glyphicons-chevron-left"></span></button>{/if}
								<b>{$p}</b> of <b>{$count}</b> 
								{if $next}<button id="btnExplorerNext" type="button" onclick="this.form.action='{devblocks_url}c=explore&hash={$hashset}&p={$next}{/devblocks_url}';this.form.submit();"><span class="glyphicons glyphicons-chevron-right"></span></button>{/if}
								</form>
								{/if}
							</td>
							<td style="padding-right:10px;padding-top:10px;" valign="top">
								<form action="{if !empty($url)}{$url}{else}{$return_url}{/if}" method="get">
								<button type="button" onclick="this.form.submit();"><span class="glyphicons glyphicons-circle-remove"></span></button>
								</form>
							</td>
						</tr>
					</table>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<iframe id="explorerFrame" src="{$url}" frameborder="0"></iframe>
				</td>
			</tr>
		</table>
	</body>
</html>

<script type="text/javascript">
$(function(e) {
	var $explorerFrame = $('#explorerFrame');
	var keyPrev = '[';
	var keyNext = ']';

	$explorerFrame.load(function() {
		try {
			var $explorerBody = $explorerFrame.contents().find('body').parent();
			
			$explorerBody.bind('keypress', keyPrev, function(event) {
				$('#btnExplorerPrev').click();
				event.stopPropagation();
			});
			
			$explorerBody.bind('keypress', keyNext, function(event) {
				$('#btnExplorerNext').click();
				event.stopPropagation();
			});
			
			$explorerFrame.focus();
		} catch(e) {}
	});
});
</script>
