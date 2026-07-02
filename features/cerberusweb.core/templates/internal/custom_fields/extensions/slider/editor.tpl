{$field_uniqid = uniqid('cfield_')}
<div id="{$field_uniqid}">
    <div class="cerb-ui-slider" style="max-width:250px;margin-left:10px;">
        <input type="hidden" name="{$form_key}" value="{$form_value|default:0}">
    </div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $cfield = $('#{$field_uniqid}');

	if(window.CerbUI && CerbUI.Slider) {
		$cfield.find('div.cerb-ui-slider').each(function() {
			new CerbUI.Slider(this, {
				min: {$value_min},
				max: {$value_max},
				step: 1,
				midpoint: {$value_mid}
			});
		});
	}
});
</script>
