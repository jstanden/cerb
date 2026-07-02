{$fieldset_domid = "peek_fieldset_{uniqid()}"}
{if !$custom_fields_expanded}
	{$custom_fields_expanded = []}
{/if}
{$owner = $custom_fieldset->getOwnerDictionary()}

{* Bulk-update variant — Cerb UI shell wrapping the bulk operator form (per-field enable checkbox +
   set/unset selects). Worklist bulk-update adds fieldsets fresh, so there is no delete-restore state;
   the remove control just drops the panel from the form. *}
<div class="cerb-ui-panel cerb-ui-panel--spaced" id="{$fieldset_domid}" data-cerb-custom-fieldset>
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm">
			{$custom_fieldset->name}
			{if $owner->_context != CerberusContexts::CONTEXT_APPLICATION && $owner->_label}
				<span class="cerb-u-text-muted cerb-u-fw-400">({$owner->_label})</span>
			{/if}
		</div>
		<div class="cerb-ui-header--right">
			<button type="button" class="cerb-ui-button cerb-ui-button--transparent cerb-custom-fieldset--remove" title="{'common.remove'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-circle-remove"></span></button>
		</div>
	</div>

	<div class="cerb-custom-fieldset--body">
		{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true field_wrapper=$field_wrapper custom_fields=$custom_fieldset->getCustomFields() custom_fields_expanded=$custom_fields_expanded}
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$('#{$fieldset_domid} > .cerb-ui-header button.cerb-custom-fieldset--remove').on('click', function() {
	var $panel = $(this).closest('[data-cerb-custom-fieldset]');

	$panel.fadeTo('fast', 0.0, function() {
		var event = jQuery.Event("custom_fieldset_delete");
		event.fieldset_id = '{$custom_fieldset->id}';
		$(this).trigger(event);
		$(this).remove();
	});
});
</script>
