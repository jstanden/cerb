{$peek_context = CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="scheduled_behavior">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.at'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="run_date" value="{$model->run_date|devblocks_date}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.on'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<select name="context">
				{if !$event_point}
				<option value=""></option>
				{/if}
				{foreach from=$contexts item=context key=macro_event}
				<option value="{$context->id}" data-event-point="{$macro_event}" {if $context->id == $model->context}selected="selected"{/if}>{$context->name}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tbody class="cerb-hideable" {if !$event_point}style="display:none;"{/if}>
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.record'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<button type="button" class="chooser-abstract" data-field-name="context_id" data-context="{$model->context}" data-single="true" data-query="" data-query-required=""><span class="glyphicons glyphicons-search"></span></button>
			<ul class="bubbles chooser-container">
				{if $model->context_id}
				{$record = $model->getRecordDictionary()}
				{if $record}
					<li><input type="hidden" name="context_id" value="{$record->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{$record->_context}" data-context-id="{$record->id}">{$record->_label}</a></li>
				{/if}
				{/if}
			</ul>
		</td>
	</tr>
	</tbody>
	<tbody class="cerb-hideable" {if !$event_point}style="display:none;"{/if}>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{'common.run'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<button type="button" class="chooser-abstract" data-field-name="behavior_id" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="" data-query-required="event:{$event_point|default:'event.macro.*'} private:n disabled:n" data-autocomplete="event:{$event_point|default:'event.macro.*'} private:n disabled:n" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
				
				{$behavior = $model->getBehavior()}
				
				<ul class="bubbles chooser-container">
					{if $behavior}
					<li><input type="hidden" name="behavior_id" value="{$model->behavior_id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$model->behavior_id}">{$behavior->title}</a></li>
					{/if}
				</ul>
				
				<div class="cerb-behavior-params" style="margin:5px 0px 0px 0px;">
					{include file="devblocks:cerberusweb.core::internal/decisions/assistant/behavior_variables_entry.tpl" field_name="behavior_params" variables=$behavior->variables|default:[] variable_values=$model->variables}
				</div>
			</td>
		</tr>
	</tbody>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this scheduled behavior?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		var $chooser_context = $popup.find('select[name=context]');
		var $chooser_record = $popup.find('button[data-field-name=context_id]');
		var $chooser_behavior = $popup.find('button[data-field-name=behavior_id]');
		var $behavior_params = $popup.find('div.cerb-behavior-params');
		var $records = $chooser_record.siblings('ul.chooser-container');
		var $behaviors = $chooser_behavior.siblings('ul.chooser-container');
		
		$popup.dialog('option','title',"{'common.behavior.scheduled'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Close confirmation

		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode === 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});
		
		$popup.find('input[name=run_date]').cerbDateInputHelper();
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		$chooser_context.on('change', function() {
			var $this = $(this);
			var context = $this.val();
			
			if(0 == context.length) {
				$frm.find('table > tbody.cerb-hideable').hide();
			} else {
				$frm.find('table > tbody.cerb-hideable').fadeIn();
			}
			
			var event_point = $this.find('> option:selected').attr('data-event-point');
			
			// Set the context on the record chooser
			$chooser_record.attr('data-context', context);
			
			// Set the event point on the behavior chooser
			$chooser_behavior.attr('data-query-required', 'event:' + event_point + ' private:n disabled:n');
			$chooser_behavior.attr('data-autocomplete', 'event:' + event_point + ' private:n disabled:n');
			
			// Clear selections when the context changes
			$records.find('span.glyphicons-circle-remove').click();
			$behaviors.find('span.glyphicons-circle-remove').click();
			$behavior_params.text('').hide();
		});
		
		$popup.find('.chooser-abstract')
			.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
				var $target = $(e.target);
				
				// When the behavior changes, change the 'on record' to match 
				if($target.attr('data-field-name') == 'context_id') {
					// ...
					
				} else if($target.attr('data-field-name') == 'behavior_id') {
					$behavior_params.text('').hide();
					
					var $behavior = $behaviors.find('> li:first input:hidden');
					var behavior_id = $behavior.val();
					
					if($behavior.length > 0 && undefined != behavior_id) {
						//genericAjaxGet($behavior_params,'c=profiles&a=invoke&module=behavior&action=getParams&name_prefix=params&trigger_id=' + behavior_id);
						genericAjaxGet($behavior_params,'c=profiles&a=invoke&module=scheduled_behavior&action=getBulkParams&trigger_id=' + behavior_id);
					}
				}
			})
			;
		
		// Repeat freq
		
		$frm.find('input:radio[name=repeat_freq]').click(function(e) {
			var $td = $(this).closest('td');
			var $table = $td.closest('table');
			var $terms = $td.find('div.terms');
			var $val = $(this).val();
			
			$terms.find('> div').hide();

			if($val.length > 0) {
				$terms.find('div.'+$(this).val()).fadeIn();
				$table.find('tbody.end').show();
			} else {
				$table.find('tbody.end').hide();
			}
		});
		
		// Repeat end
		
		$frm.find('input:radio[name=repeat_end]').click(function(e) {
			var $ends=$(this).closest('td').find('div.ends');
			var $val = $(this).val();
			
			$ends.find('> div').hide();

			if($val.length > 0) {
				$ends.find('div.'+$(this).val()).fadeIn();
			}
		});

		// Modify recurring event
		
		$frm.find('DIV.buttons INPUT:radio[name=edit_scope]').change(function(e) {
			var $frm = $(this).closest('form');
			var $val = $(this).val();
			
			if($val == 'this') {
				$frm.find('tbody.repeat, tbody.end').hide();
			} else {
				$frm.find('tbody.repeat, tbody.end').show();
			}
		});	
	});
});
</script>
