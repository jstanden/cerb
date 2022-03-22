{$script_uid = uniqid('el_')}
<script id="{$script_uid}" type="text/javascript" nonce="{$session->nonce}">
{
	let $script = document.querySelector('#{$script_uid}');
	let $popup = $script.closest('.cerb-interaction-popup');

	let title = {$popup_title|json_encode nofilter};
	$popup.querySelector('.cerb-interaction-popup--title').textContent = title;
}
</script>