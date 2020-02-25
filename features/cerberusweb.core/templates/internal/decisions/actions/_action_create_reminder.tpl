<b>{'common.name'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[name]" value="{$params.name}" class="placeholders" spellcheck="false" size="45" style="width:100%;" placeholder="e.g. Lunch meeting">
</div>

<b>{'common.remind_at'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[remind_at]" value="{$params.remind_at}" class="placeholders" spellcheck="false" size="45" style="width:100%;" placeholder="e.g. tomorrow noon">
</div>

<b>{'common.for'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="worker_id" values_to_contexts=$values_to_contexts single=true}
</div>

<b>{'common.behaviors'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<div class="behaviors">
	{if $params.behavior_ids}
	{$behaviors = DAO_TriggerEvent::getIds($params.behavior_ids)}
	{foreach from=$behaviors item=behavior}
	<fieldset class="peek black" style="position:relative;">
		<span class="glyphicons glyphicons-circle-remove" style="position:absolute;top:0;right:0;cursor:pointer;"></span>
		<input type="hidden" name="{$namePrefix}[behavior_ids][]" value="{$behavior->id}">
		<legend><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a></legend>
		<div class="parameters">
		{include file="devblocks:cerberusweb.core::events/_action_behavior_params.tpl" namePrefix="{$namePrefix}[behaviors][{$behavior->id}]" params=$params.behaviors[$behavior->id] macro_params=$behavior->variables}
		</div>
	</fieldset>
	{/foreach}
	{/if}
	</div>
	
	<div style="margin:5px 0px 10px 0px;">
		<button type="button" class="chooser-behavior" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-query="" data-query-required="disabled:n private:n event:event.macro.reminder"><span class="glyphicons glyphicons-circle-plus"></span> {'common.behaviors'|devblocks_translate|capitalize}</button>
	</div>
</div>

{if !empty($values_to_contexts)}
<b>Link to:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_var_picker.tpl" param_name="link_to" values_to_contexts=$values_to_contexts}
</div>
{/if}

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate|capitalize}:</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false field_wrapper="{$namePrefix}"}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/decisions/actions/_shared_add_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_REMINDER field_wrapper="{$namePrefix}"}

<b>Also create reminders in simulator mode:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

<b>Save object metadata to a placeholder named:</b><br>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_reminder_meta"}" required="required" spellcheck="false" size="32" placeholder="e.g. _reminder_meta">&#125;&#125;
</div>

{* Check for reminder list variables *}
{capture name="reminder_vars"}
{foreach from=$trigger->variables item=var key=var_key}
{if $var.type == "ctx_{CerberusContexts::CONTEXT_REMINDER}"}
<option value="{$var_key}" {if $params.object_var==$var_key}selected="selected"{/if}>{$var.label}</option>
{/if}
{/foreach}
{/capture}

{if $smarty.capture.attachment_vars}
<b>Add object to list variable:</b><br>
<div style="margin-left:10px;margin-bottom:10px;">
	<select name="{$namePrefix}[object_var]">
		<option value=""></option>
		{$smarty.capture.reminder_vars nofilter}
	</select>
</div>
{/if}

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	
	var $behaviors = $action.find('div.behaviors');

	// Peeks
	$behaviors.find('.cerb-peek-trigger').cerbPeekTrigger();
	
	// Abstract delete
	$behaviors.on('click', 'span.glyphicons-circle-remove', function(e) {
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
					var $remove = $('<span class="glyphicons glyphicons-circle-remove" style="position:absolute;top:0;right:0;cursor:pointer;"/>')
						.appendTo($fieldset)
					;
					
					var $legend = $('<legend/>')
						.appendTo($fieldset)
					;
					
					var $a = $('<a/>')
						.attr('href','javascript:;')
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
