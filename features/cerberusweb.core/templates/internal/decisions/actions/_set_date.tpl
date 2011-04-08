<input type="text" name="{$namePrefix}[value]" value="{$params.value}" size="45" style="width:100%;">
<div style="display:none;" class="tips">
	<i>(e.g. "tomorrow 5pm", "+2 hours", "2011-04-27 5:00pm", "8am", "August 15", "next Thursday")</i>
</div>

<script type="text/javascript">
	$('#action{$seq}')
		.find('input:text')
		.focus(
			function() {
				$(this).next('div.tips').show();
			}
		)
		.blur(
			function() {
				$(this).next('div.tips').hide();
			}
		)
		;
</script>