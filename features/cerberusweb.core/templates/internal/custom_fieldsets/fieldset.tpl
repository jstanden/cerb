{$fieldset_domid = "peek_fieldset_{uniqid()}"}
<fieldset class="peek" id="{$fieldset_domid}">
	<legend>{$custom_fieldset->name}</legend>
	<span class="cerb-sprite2 sprite-cross-circle delete" style="cursor:pointer;float:right;margin-top:-20px;display:none;"></span>
	
	{if $custom_fieldset_is_new}
	<input type="hidden" name="custom_fieldset_adds[]" value="{$custom_fieldset->id}">
	{else}{* We can only delete fieldsets that existed first *}
	<input type="hidden" name="custom_fieldset_deletes[]" value="">
	{/if}
	
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=$bulk custom_fields=$custom_fieldset->getCustomFields()}
</fieldset>

<script type="text/javascript">
$('#{$fieldset_domid}')
	.hover(
		function() {
			$(this).find('span.delete').show();
		},
		function() {
			$(this).find('span.delete').hide();
		}
	)
	.find('span.delete')
	.click(function() {
		var $fieldset = $(this).closest('fieldset');
		var $hidden = $fieldset.find('input:hidden[name ^= custom_fieldset_deletes]');
		
		if($hidden.length == 0) {
			$fieldset.fadeTo('fast', 0.0, function() {
				var event = jQuery.Event("custom_fieldset_delete");
				event.fieldset_id = '{$custom_fieldset->id}';
				$(this).trigger(event);
				$(this).remove();
			});
			
		} else if($hidden.val() == '') {
			$(this).removeClass('sprite-cross-circle').addClass('sprite-tick-circle');
			$fieldset.fadeTo('fast', 0.3);
			$hidden.val('{$custom_fieldset->id}');
		} else {
			$(this).removeClass('sprite-tick-circle').addClass('sprite-cross-circle');
			$fieldset.fadeTo('fast', 1.0);
			$hidden.val('');
		}
	})
	;
</script>