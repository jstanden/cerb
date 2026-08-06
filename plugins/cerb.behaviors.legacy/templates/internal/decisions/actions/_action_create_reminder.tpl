<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
	<input type="text" name="{$namePrefix}[name]" value="{$params.name}" class="placeholders" spellcheck="false" placeholder="e.g. Lunch meeting">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.remind_at'|devblocks_translate|capitalize}</label>
	<input type="text" name="{$namePrefix}[remind_at]" value="{$params.remind_at}" class="placeholders" spellcheck="false" placeholder="e.g. tomorrow noon">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.for'|devblocks_translate|capitalize}</label>
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="worker_id" values_to_contexts=$values_to_contexts single=true}
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.behaviors'|devblocks_translate|capitalize}<span class="cerb-ui-form--hint">these have no effect in 10.0+</span></label>
	<div>
		<div class="behaviors">
		{if $params.behavior_ids}
		{$behaviors = DAO_TriggerEvent::getIds($params.behavior_ids)}
		{foreach from=$behaviors item=behavior}
		<fieldset class="peek black" style="position:relative;">
			<span class="cerb-icons cerb-icon-circle-remove" style="position:absolute;top:0;right:0;cursor:pointer;"></span>
			<input type="hidden" name="{$namePrefix}[behavior_ids][]" value="{$behavior->id}">
			<legend><a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a></legend>
			<div class="parameters">
			{include file="devblocks:cerb.behaviors.legacy::events/_action_behavior_params.tpl" namePrefix="{$namePrefix}[behaviors][{$behavior->id}]" params=$params.behaviors[$behavior->id] macro_params=$behavior->variables}
			</div>
		</fieldset>
		{/foreach}
		{/if}
		</div>

		<div style="margin:5px 0px 10px 0px;">
			<button type="button" class="chooser-behavior" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-query="" data-query-required="disabled:n private:n event:event.macro.reminder"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.behaviors'|devblocks_translate|capitalize}</button>
		</div>
	</div>
</div>

{if !empty($values_to_contexts)}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Link to</label>
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_var_picker.tpl" param_name="link_to" values_to_contexts=$values_to_contexts}
</div>
{/if}

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.custom_fields'|devblocks_translate|capitalize}</div>
	</div>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields field_wrapper="{$namePrefix}" custom_field_values_raw=true}
</div>
{/if}

{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_add_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_REMINDER field_wrapper="{$namePrefix}"}

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Also create reminders in simulator mode</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Save object metadata to a placeholder named</label>
	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
		&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_reminder_meta"}" required="required" spellcheck="false" size="32" placeholder="e.g. _reminder_meta">&#125;&#125;
	</div>
</div>

{* Check for reminder list variables *}
{capture name="reminder_vars"}
{foreach from=$trigger->variables item=var key=var_key}
{if $var.type == "ctx_{CerberusContexts::CONTEXT_REMINDER}"}
<option value="{$var_key}" {if $params.object_var==$var_key}selected="selected"{/if}>{$var.label}</option>
{/if}
{/foreach}
{/capture}

{if $smarty.capture.reminder_vars}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Add object to list variable</label>
	<select name="{$namePrefix}[object_var]">
		<option value=""></option>
		{$smarty.capture.reminder_vars nofilter}
	</select>
</div>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');

	var $behaviors = $action.find('div.behaviors');

	// Peeks
	$behaviors.find('.cerb-peek-trigger').cerbPeekTrigger();

	// Abstract delete
	$behaviors.on('click', 'span.cerb-icon-circle-remove', function(e) {
		var $this = $(this);
		e.stopPropagation();

		// Two step confirm
		if(!$this.attr('data-delete')) {
			$this
				.css('color', 'red')
				.attr('data-delete', 'true')
			;
		} else {
			$this.closest('fieldset').remove();
		}
	});

	// Behavior chooser
	$action.find('.chooser-behavior')
		.click(function() {
			var $trigger = $(this);
			var context = $trigger.attr('data-context');
			var q = $trigger.attr('data-query');
			var qr = $trigger.attr('data-query-required');
			var single = $trigger.attr('data-single') != null ? '1' : '';
			var width = $(window).width()-100;
			var $chooser=genericAjaxPopup('chooser' + new Date().getTime(),'c=internal&a=invoke&module=records&action=chooserOpen&context=' + encodeURIComponent(context) + '&q=' + encodeURIComponent(q) + '&qr=' + encodeURIComponent(qr) + '&single=' + encodeURIComponent(single),null,true,width);

			$chooser.one('chooser_save', function(event) {
				for(value in event.values) {
					var behavior_label = event.labels[value];
					var behavior_id = event.values[value];

					// Don't add the same behavior twice
					if($behaviors.find('input:hidden[value=' + behavior_id + ']').length != 0)
						continue;

					var $fieldset = $('<fieldset class="peek black" style="position:relative;" />');
					var $hidden = $('<input type="hidden" name="{$namePrefix}[behavior_ids][]" />').val(behavior_id).appendTo($fieldset);
					var $remove = $('<span class="cerb-icons cerb-icon-circle-remove" style="position:absolute;top:0;right:0;cursor:pointer;"/>')
						.appendTo($fieldset)
					;

					var $legend = $('<legend/>')
						.appendTo($fieldset)
					;

					$('<a/>')
						.addClass('no-underline')
						.text(behavior_label)
						.attr('data-context', 'cerberusweb.contexts.behavior')
						.attr('data-context-id', behavior_id)
						.cerbPeekTrigger()
						.appendTo($legend)
					;

					var $div = $('<div class="parameters" />').appendTo($fieldset);
					var name_prefix = '{$namePrefix}[behaviors][' + behavior_id + ']';

					$fieldset.appendTo($behaviors);

					genericAjaxGet($div, 'c=profiles&a=invoke&module=behavior&action=getParams&name_prefix=' + encodeURIComponent(name_prefix) + '&trigger_id=' + encodeURIComponent(behavior_id));
				}
			});
		})
	;
});
</script>
