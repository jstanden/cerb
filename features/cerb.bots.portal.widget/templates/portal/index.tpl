<html>
<head>
	<title>{$config.page_title}</title>
	<meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0">
	<link rel="icon" type="image/png" sizes="32x32" href="{devblocks_url}c=resource&p=cerb.bots.portal.widget&f=images/favicon-32x32.png{/devblocks_url}">
	<link rel="icon" type="image/png" sizes="96x96" href="{devblocks_url}c=resource&p=cerb.bots.portal.widget&f=images/favicon-96x96.png{/devblocks_url}">
	<link rel="icon" type="image/png" sizes="16x16" href="{devblocks_url}c=resource&p=cerb.bots.portal.widget&f=images/favicon-16x16.png{/devblocks_url}">
	<style type="text/css">
	html {
		background-image: url({devblocks_url}c=resource&p=cerb.bots.portal.widget&f=images/backgrounds/random-grey-variations.png{/devblocks_url});
	}
	{$config.page_css}
	</style>
</head>
<body>
	
	<script async type="text/javascript" src="{devblocks_url full=true}c=assets&f=embed.js{/devblocks_url}?v={$smarty.const.APP_BUILD}" id="cerb-portal" data-bubble="{if $config.page_hide_icon}false{else}true{/if}"></script>
	
	{if !empty($interaction)}
	<script type="text/javascript">
	document.getElementById('cerb-portal').addEventListener('cerb-bot-ready', function(e) {
		var $ = this.jQuery;
		var $embedder = $('#cerb-portal');
		
		var evt = new $.Event('cerb-bot-trigger');
		evt.interaction = '{$interaction}';
		evt.interaction_params = {$interaction_params nofilter};
		$embedder.trigger(evt);
	});
	</script>
	{/if}
	
</body>
</html>