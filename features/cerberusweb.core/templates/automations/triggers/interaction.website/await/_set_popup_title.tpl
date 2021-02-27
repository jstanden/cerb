{$script_uid = uniqid('el_')}
<script id="{$script_uid}" type="text/javascript">
{
	var $script = document.querySelector('#{$script_uid}');
	var $popup = $script.closest('.cerb-interaction-popup');
	
	$popup.querySelector('.cerb-interaction-popup--title').textContent = '{$popup_title}';
}
</script>