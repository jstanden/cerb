<b>Send a copy of this notification to:</b>
<div style="margin-left:10px;">
	{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="worker_id" values_to_contexts=$values_to_contexts}
</div>

<script type="text/javascript">
$(function() {
	var $action = $('fieldset#{$namePrefix}');
});
</script>