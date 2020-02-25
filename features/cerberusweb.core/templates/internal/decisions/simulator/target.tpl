{$target_divid = uniqid()}
<fieldset id="{$target_divid}" class="peek">
	<legend>Target: {$context_ext->manifest->name}</legend>
	
	<button class="chooser"><span class="glyphicons glyphicons-search"></span></button>
	<b>{$dict->_label}</b>
</fieldset>

<script type="text/javascript">
$(function() {
	$('#{$target_divid} button.chooser').click(function() {
		var $this = $(this);
		var $chooser = genericAjaxPopup('chooser' + new Date().getTime(),'c=internal&a=invoke&module=records&action=chooserOpen&context={$context}&single=1',null,true,'750');
		$chooser.one('chooser_save', function(event) {
			if(typeof event.values == "object" && event.values.length > 0) {
				var context_id = event.values[0];
				genericAjaxPopup('simulate_behavior', 'c=profiles&a=invoke&module=behavior&action=renderSimulatorPopup&trigger_id={$trigger->id}&context_id=' + context_id, 'reuse', false, '50%');
			}
		});
	});
});
</script>