{$script_uid = uniqid('el_')}
<script id="{$script_uid}" type="text/javascript">
$$.ready(function() {
	var $script = document.querySelector('#{$script_uid}');
	var $panel = $script.closest('.cerb-interaction-panel');

	var title = {$popup_title|json_encode nofilter};
	
	var $widget_title = $panel.closest('.cerb-portal-widget')?.querySelector('.cerb-portal-widget--title');
	
	if($widget_title) {
		$widget_title.textContent = title;
	} else {
		$panel.querySelector('.cerb-interaction-panel--title').textContent = title;
	}
});
</script>