<div>
	<textarea name="{$namePrefix}[value]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.value}</textarea>
</div>
<div style="display:none;" class="tips">
	<i>(e.g. "tomorrow 5pm", "+2 hours", "2011-04-27 5:00pm", "8am", "August 15", "next Thursday")</i>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action
	.find('textarea')
	.focus(
		function() {
			$(this).closest('div').next('div.tips').show();
		}
	)
	.blur(
		function() {
			$(this).closest('div').next('div.tips').hide();
		}
	)
	.elastic()
	;
</script>
