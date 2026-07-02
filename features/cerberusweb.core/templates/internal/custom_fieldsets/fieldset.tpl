{$fieldset_domid = "peek_fieldset_{uniqid()}"}
{$collapsible = $collapsible|default:false}
{$collapse_default = $collapse_default|default:false}
{if !$custom_fields_expanded}
	{$custom_fields_expanded = []}
{/if}
{$owner = $custom_fieldset->getOwnerDictionary()}

{* Collapsed by default only where opted in (reply); freshly added ones stay expanded *}
{$start_collapsed = $collapsible && $collapse_default && !$custom_fieldset_is_new}

<div class="cerb-ui-panel cerb-ui-panel--spaced{if $collapsible} cerb-custom-fieldset{/if}{if $start_collapsed} is-collapsed{/if}" id="{$fieldset_domid}" data-cerb-custom-fieldset>
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm{if $collapsible} cerb-custom-fieldset--title{/if}">
			{if $collapsible}<span class="cerb-icons cerb-icon-chevron-right cerb-custom-fieldset--chevron"></span>{/if}
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
		{if empty($field_wrapper)}
			{if !$custom_fieldset_is_new}{* We can only delete fieldsets that existed first *}
				<input type="hidden" name="custom_fieldset_deletes[]" value="">
			{/if}
		{/if}

		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" field_wrapper=$field_wrapper custom_fields=$custom_fieldset->getCustomFields() custom_fields_expanded=$custom_fields_expanded custom_field_values_raw=$custom_field_values_raw|default:false}
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
{if $collapsible}
$('#{$fieldset_domid} > .cerb-ui-header > .cerb-custom-fieldset--title').on('click', function() {
	$(this).closest('[data-cerb-custom-fieldset]').toggleClass('is-collapsed');
});
{/if}

$('#{$fieldset_domid} > .cerb-ui-header button.cerb-custom-fieldset--remove').on('click', function() {
	var $panel = $(this).closest('[data-cerb-custom-fieldset]');
	var $hidden = $panel.find('input:hidden[name ^= custom_fieldset_deletes]');
	var $icon = $(this).find('.cerb-icons');

	if($hidden.length == 0) {
		$panel.fadeTo('fast', 0.0, function() {
			var event = jQuery.Event("custom_fieldset_delete");
			event.fieldset_id = '{$custom_fieldset->id}';
			$(this).trigger(event);
			$(this).remove();
		});
	} else if($hidden.val() == '') {
		$icon.removeClass('cerb-icon-circle-remove').addClass('cerb-icon-circle-plus');
		$panel.fadeTo('fast', 0.3);
		$hidden.val('{$custom_fieldset->id}');
	} else {
		$icon.removeClass('cerb-icon-circle-plus').addClass('cerb-icon-circle-remove');
		$panel.fadeTo('fast', 1.0);
		$hidden.val('');
	}
});
</script>
