<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html {if $pref_dark_mode}class="dark"{/if}>
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
			var CerbSchemaRecordsVersion = {intval(DevblocksPlatform::services()->cache()->getTagVersion("schema_records"))};
		</script>

		<!-- Platform -->
		<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=devblocks.core&f=css/jquery-ui.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
		<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/jquery/jquery.combined.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
		<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/devblocks.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>

		<!-- Application -->
		<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/cerb.css{/devblocks_url}?v={$smarty.const.APP_BUILD}&pl=0">
		<script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=js/cerberus.js{/devblocks_url}?v={$smarty.const.APP_BUILD}&pl=8"></script>

		<style type="text/css">
			BODY { margin:0; padding:0; }
			IFRAME { width:100%; height:100%; border: 0; }
		</style>
	</head>
	
	<body>
		<table cellpadding="0" cellspacing="0" border="0" style="height:100vh;width:100vw;">
			<tr>
				<td style="height:50px;">
					<div class="block" id="explorerToolbar">
						<div style="display:flex;flex-flow:row wrap;">
							<div style="flex:1 1 auto;">
								<div style="display:flex;flex-flow:row wrap;">
									<div style="flex:0 0 60px;">
										<a href="{if !empty($return_url)}{$return_url}{else}{devblocks_url}{/devblocks_url}{/if}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/cerby.png{/devblocks_url}?v={$smarty.const.APP_BUILD}" border="0" height="40"></span></a>
									</div>
									<div style="flex:1 1 auto;">
										<b style="font-size:1.5em;margin-right:5px;">{$title|default:"Results"|trim}</b>
										
										<div style="max-width:75vw;text-overflow:ellipsis;word-wrap:break-word;word-break:break-all;">
											{if !empty($content)}
											<a href="{$url}" target="_blank" rel="noopener">{$content}</a>
											{else} 
											<a href="{$url}" target="_blank" rel="noopener">{$url|truncate:100}</a>
											{/if}
										</div> 
									</div>
								</div>
							</div>
							<div style="flex:1 1 auto;text-align:right;">
								{if !empty($count)}
								<form action="#" method="get">
								{if $prev}<button id="btnExplorerPrev" type="button" onclick="this.form.action='{devblocks_url}c=explore&hash={$hashset}&p={$prev|round}{/devblocks_url}';this.form.submit();"><span class="glyphicons glyphicons-chevron-left"></span></button>{/if}
								<b>{$p}</b> of <b>{$count}</b> 
								{if $next}<button id="btnExplorerNext" type="button" onclick="this.form.action='{devblocks_url}c=explore&hash={$hashset}&p={$next|round}{/devblocks_url}';this.form.submit();"><span class="glyphicons glyphicons-chevron-right"></span></button>{/if}
								<button type="button" onclick="window.document.location.href='{if !empty($url)}{$url}{else}{$return_url}{/if}';"><span class="glyphicons glyphicons-circle-remove"></span></button>
								</form>
								{/if}
							</div>
						</div>
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

	<script type="text/javascript">
	$(function(e) {
		var $explorerBody = $('body');
		var $explorerFrame = $('#explorerFrame');
		
		var keyPrev = '[';
		var keyNext = ']';
		
		var funcPrev = function(event) {
			$('#btnExplorerPrev').click();
			event.stopPropagation();
		};
		
		var funcNext = function(event) {
			$('#btnExplorerNext').click();
			event.stopPropagation();
		};
		
		// Toolbar keyboard shortcuts
		
		$explorerBody.bind('keypress', keyPrev, funcPrev);
		$explorerBody.bind('keypress', keyNext, funcNext);
		
		// Frame keyboard shortcuts
		
		$explorerFrame.on('load', function() {
			try {
				var $explorerBody = $explorerFrame.contents().find('body').parent();
				$explorerBody.bind('keypress', keyPrev, funcPrev);
				$explorerBody.bind('keypress', keyNext, funcNext);
				$explorerFrame.focus();
			} catch(e) { }
		});
	});
	</script>
</html>
