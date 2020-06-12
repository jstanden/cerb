{$fieldset_domid = "peek_fieldset_{uniqid()}"}
<fieldset class="block" id="{$fieldset_domid}">
	{$owner = $custom_fieldset->getOwnerDictionary()}
	<legend>
		{$custom_fieldset->name}
		{if $owner->_context != CerberusContexts::CONTEXT_APPLICATION}
		<small>({$owner->_label})</small>
		{/if}
	</legend>
	<span class="glyphicons glyphicons-circle-remove delete" style="font-size:16px;cursor:pointer;float:right;margin-top:-20px;display:none;"></span>
	
	{if empty($field_wrapper)}
	{if !$custom_fieldset_is_new}{* We can only delete fieldsets that existed first *}
	<input type="hidden" name="custom_fieldset_deletes[]" value="">
	{/if}
	{/if}
	
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=$bulk field_wrapper=$field_wrapper custom_fields=$custom_fieldset->getCustomFields()}
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
			$(this).removeClass('glyphicons-circle-remove').addClass('glyphicons-circle-plus');
			$fieldset.fadeTo('fast', 0.3);
			$hidden.val('{$custom_fieldset->id}');
		} else {
			$(this).removeClass('glyphicons-circle-plus').addClass('glyphicons-circle-remove');
			$fieldset.fadeTo('fast', 1.0);
			$hidden.val('');
		}
	})
	;
</script>
