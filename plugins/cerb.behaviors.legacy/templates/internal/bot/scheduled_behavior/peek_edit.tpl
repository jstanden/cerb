{$peek_context = CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="scheduled_behavior">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.at'|devblocks_translate|capitalize}</label>
			<input type="text" name="run_date" value="{$model->run_date|devblocks_date}" autofocus="autofocus">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.on'|devblocks_translate|capitalize}</label>
			<select name="context">
				{if !$event_point}
				<option value=""></option>
				{/if}
				{foreach from=$contexts item=context key=macro_event}
				<option value="{$context->id}" data-event-point="{$macro_event}" data-cerb-ui-icon="{$context->params.icon|default:'collection'}" {if $context->id == $model->context}selected="selected"{/if}>{$context->name}</option>
				{/foreach}
			</select>
		</div>

		<div class="cerb-ui-form--field cerb-hideable" {if !$event_point}style="display:none;"{/if}>
			<label class="cerb-ui-form--label">{'common.record'|devblocks_translate|capitalize}</label>
			<div class="cerb-ui-record-chooser cerb-record-chooser-context">
				{if $model->context_id}
				{$record = $model->getRecordDictionary()}
				{if $record}
				<li data-context="{$record->_context}" data-context-id="{$record->id}" data-label="{$record->_label}"></li>
				{/if}
				{/if}
			</div>
		</div>

		<div class="cerb-ui-form--field cerb-hideable" {if !$event_point}style="display:none;"{/if}>
			<label class="cerb-ui-form--label">{'common.run'|devblocks_translate|capitalize}</label>
			{$behavior = $model->getBehavior()}
			<div class="cerb-ui-record-chooser cerb-record-chooser-behavior">
				{if $behavior}
				<li data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$model->behavior_id}" data-label="{$behavior->title}"></li>
				{/if}
			</div>

			<div class="cerb-behavior-params" style="margin:5px 0px 0px 0px;">
				{include file="devblocks:cerb.behaviors.legacy::internal/decisions/assistant/behavior_variables_entry.tpl" field_name="behavior_params" variables=$behavior->variables|default:[] variable_values=$model->variables}
			</div>
		</div>
	</div>
</div>

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
	</div>
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="scheduled behavior"}
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function(event,ui) {
		var $chooser_context = $popup.find('select[name=context]');
		var $behavior_params = $popup.find('div.cerb-behavior-params');

		$popup.dialog('option','title',"{'common.behavior.scheduled'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// On — record-type <select> keeps the POST value + its data-event-point; enhance with type-to-filter + icons
		if(window.CerbUI && CerbUI.SelectMenu)
			$chooser_context.each(function() { new CerbUI.SelectMenu(this); });

		$popup.find('input[name=run_date]').each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this); });
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		let loadBehaviorParams = function(behavior_id) {
			$behavior_params.text('').hide();
			if(behavior_id)
				genericAjaxGet($behavior_params,'c=profiles&a=invoke&module=scheduled_behavior&action=getBulkParams&trigger_id=' + behavior_id);
		};

		// Record-of-context chooser (dynamic context, driven by the "On:" select) + scoped behavior chooser
		let rcRecord = null, rcBehavior = null;

		if(window.CerbUI && CerbUI.RecordChooser) {
			$popup.find('.cerb-record-chooser-context').each(function() {
				rcRecord = new CerbUI.RecordChooser(this, {
					context: '{$model->context}',
					name: 'context_id'
				});
			});

			$popup.find('.cerb-record-chooser-behavior').each(function() {
				rcBehavior = new CerbUI.RecordChooser(this, {
					context: '{CerberusContexts::CONTEXT_BEHAVIOR}',
					name: 'behavior_id',
					emptyIcon: 'branch',
					query: 'event:{$event_point|default:'event.macro.*'} private:n disabled:n',
					onSelect: function(item) { loadBehaviorParams(item.id); }
				});
			});
		}

		$chooser_context.on('change', function() {
			var $this = $(this);
			var context = $this.val();

			// The hideable rows are cerb-ui-form--field (display:flex) — set display explicitly so jQuery
			// doesn't force `block` and flatten the label/control layout.
			$frm.find('.cerb-hideable').css('display', context.length ? 'flex' : 'none');

			var event_point = $this.find('> option:selected').attr('data-event-point');

			// Re-target the record chooser to the selected context, and re-scope the behavior chooser to the
			// matching event point; clear any prior selections (they no longer apply).
			if(rcRecord) { rcRecord.setContext(context); rcRecord.clear(false); }
			if(rcBehavior) { rcBehavior.setQuery('event:' + event_point + ' private:n disabled:n'); rcBehavior.clear(false); }
			$behavior_params.text('').hide();
		});
		
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
