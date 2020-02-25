{$peek_context = CerberusContexts::CONTEXT_REMINDER}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="reminder">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.reminder'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.when'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="remind_at" value="{$model->remind_at|devblocks_date}" style="width:98%;">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.for'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<button type="button" class="chooser-abstract" data-field-name="worker_id" data-context="{CerberusContexts::CONTEXT_WORKER}" data-single="true" data-query="isDisabled:n" data-autocomplete="isDisabled:n" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $model}
					{$worker = $model->getWorker()}
					{if $worker}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$worker->id}{/devblocks_url}?v={$worker->updated}"><input type="hidden" name="worker_id" value="{$worker->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}">{$worker->getName()}</a></li>
					{/if}
				{/if}
			</ul>
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

<div class="behaviors">
{if $model->params.behaviors}
{$behaviors = DAO_TriggerEvent::getIds(array_keys($model->params.behaviors))}
{foreach from=$behaviors item=behavior}
<fieldset class="peek black" style="position:relative;">
	<input type="hidden" name="behavior_ids[]" value="{$behavior->id}">
	<span class="glyphicons glyphicons-circle-remove" style="position:absolute;top:0;right:0;cursor:pointer;"></span>
	<legend><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a></legend>
	<div class="parameters">
	{include file="devblocks:cerberusweb.core::events/_action_behavior_params.tpl" namePrefix="behavior_params[{$behavior->id}]" params=$model->params.behaviors[$behavior->id] macro_params=$behavior->variables}
	</div>
</fieldset>
{/foreach}
{/if}
</div>

<div style="margin:5px 0px 10px 0px;">
	<button type="button" class="chooser-behavior" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-query="" data-query-required="disabled:n private:n event:event.macro.reminder"><span class="glyphicons glyphicons-circle-plus"></span> {'common.behaviors'|devblocks_translate|capitalize}</button>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this reminder?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'Reminder'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		var $behaviors = $popup.find('div.behaviors');
		
		// Helpers
		
		$popup.find('input[name=remind_at]')
			.cerbDateInputHelper()
		;
		
		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
		;
		
		$popup.find('.chooser-abstract')
			.cerbChooserTrigger()
		;
		
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
		$popup.find('.chooser-behavior')
			.click(function() {
				var $trigger = $(this);
				var context = $trigger.attr('data-context');
				var q = $trigger.attr('data-query');
				var qr = $trigger.attr('data-query-required');
				var single = $trigger.attr('data-single') != null ? '1' : '';
				var width = $(window).width()-100;
				var $chooser=genericAjaxPopup('chooser' + new Date().getTime(),'c=internal&a=invoke&module=records&action=chooserOpen&context=' + encodeURIComponent(context) + '&q=' + encodeURIComponent(q) + '&qr=' + encodeURIComponent(qr) + '&single=' + encodeURIComponent(single),null,true,width);
				
				$behaviors.find('.cerb-peek-trigger').cerbPeekTrigger();
				
				$chooser.one('chooser_save', function(event) {
					for(value in event.values) {
						var behavior_label = event.labels[value];
						var behavior_id = event.values[value];
						
						// Don't add the same behavior twice
						if($behaviors.find('input:hidden[value=' + behavior_id + ']').length != 0)
							continue;
						
						var $fieldset = $('<fieldset class="peek black" style="position:relative;" />');
						var $hidden = $('<input type="hidden" name="behavior_ids[]" />').val(behavior_id).appendTo($fieldset);
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
						var name_prefix = 'behavior_params[' + behavior_id + ']';
						
						$fieldset.appendTo($behaviors);
						
						genericAjaxGet($div, 'c=profiles&a=invoke&module=behavior&action=getParams&name_prefix=' + encodeURIComponent(name_prefix) + '&trigger_id=' + encodeURIComponent(behavior_id));
					}
				});
			})
		;
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
