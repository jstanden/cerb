<b>From:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[range_from]" size="3" maxlength="2" value="{$params.range_from}" placeholder="0">
	<input type="hidden" name="{$namePrefix}[color_from]" value="{$params.color_from|default:'#FF0000'}" size="7" class="color-picker">
	<input type="text" name="{$namePrefix}[label_from]" size="24" value="{$params.label_from}" placeholder="Not likely">
</div>

<b>To:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[range_to]" size="3" maxlength="2" value="{$params.range_to}" placeholder="10">
	<input type="hidden" name="{$namePrefix}[color_to]" value="{$params.color_to|default:'#19B700'}" size="7" class="color-picker">
	<input type="text" name="{$namePrefix}[label_to]" size="24" value="{$params.label_to}" placeholder="Extremely likely">
</div>

<b>Midpoint:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="hidden" name="{$namePrefix}[color_mid]" value="{$params.color_mid|default:'#FFFFFF'}" size="7" class="color-picker">
</div>

<script type="text/javascript">
$(function() {
	var $action = $('fieldset#{$namePrefix}');
	
	$action.find('input:hidden.color-picker').miniColors({
		color_favorites: ['#CF2C1D','#FEAF03','#57970A','#007CBD','#7047BA','#D5D5D5','#ADADAD','#34434E','#FFFFFF']
	});
});
</script>
