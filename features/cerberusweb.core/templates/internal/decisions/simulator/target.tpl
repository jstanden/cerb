{$target_divid = uniqid()}
<fieldset id="{$target_divid}" class="peek">
	<legend>Target: {$context_ext->manifest->name}</legend>
	
	<button class="chooser"><span class="cerb-sprite sprite-view"></span></button>
	<b>{$values._label}</b>
</fieldset>

<script type="text/javascript">
$('#{$target_divid} button.chooser').click(function() {
	var $this = $(this);
	$chooser=genericAjaxPopup('chooser' + new Date().getTime(),'c=internal&a=chooserOpen&context={$context}&single=1',null,true,'750');
	$chooser.one('chooser_save', function(event) {
		if(typeof event.values == "object" && event.values.length > 0) {
			var context_id = event.values[0];
			genericAjaxPopup('simulate_behavior', 'c=internal&a=showBehaviorSimulatorPopup&trigger_id={$trigger->id}&context_id=' + context_id, 'reuse', false, '500');
		}
	});
});
</script>