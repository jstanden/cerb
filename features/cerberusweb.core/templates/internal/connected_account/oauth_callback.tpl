<html>
<head>
	<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/jquery/jquery.combined.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
</head>

<body>
<script type="text/javascript">
$(function() {
	var evt = new jQuery.Event('oauth-saved');
	evt.label = '{$label|escape:"javascript" nofilter}';
	evt.params = '{$params_json|escape:"javascript" nofilter}';
	
	window.opener.Devblocks.triggerEvent('#{$form_id}', evt);
	window.close()
});
</script>
</body>
</html>