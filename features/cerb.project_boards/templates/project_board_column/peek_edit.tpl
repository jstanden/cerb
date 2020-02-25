{$peek_context = Context_ProjectBoardColumn::ID}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="project_board_column">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'projects.common.board'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<button type="button" class="chooser-abstract" data-field-name="board_id" data-context="{Context_ProjectBoard::ID}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $model}
					{$board = $model->getProjectBoard()}
					{if $board}
						<li><input type="hidden" name="board_id" value="{$board->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{Context_ProjectBoard::ID}" data-context-id="{$board->id}">{$board->name}</a></li>
					{/if}
				{/if}
			</ul>
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

<h3 style="font-size:1.3em;">When cards are dropped into this column:</h3>

{* If tasks are on the board *}
{if in_array(CerberusContexts::CONTEXT_TASK, $board->params.contexts)}
{$action_params = $model->params.actions}
{$has_action_task_status = is_array($action_params) && array_key_exists('task_status', $action_params)}
<fieldset class="peek black" style="position:relative;">
	<legend>
		<label>
		<input type="checkbox" name="actions[]" value="task_status" {if $has_action_task_status}checked="checked"{/if}> 
		Set task status
		</label>
	</legend>
	
	<div class="parameters" style="{if $has_action_task_status}display:block;{else}display:none;{/if}">
		<div class="block" style="margin-left:10px;margin-bottom:0.5em;">
			<select name="action_params[task_status][status_id]">
				<option value="0" {if $action_params.task_status.status_id == 0}selected="selected"{/if}>{'status.open'|devblocks_translate|lower}</option>
				<option value="2" {if $action_params.task_status.status_id == 2}selected="selected"{/if}>{'status.waiting.abbr'|devblocks_translate|lower}</option>
				<option value="1" {if $action_params.task_status.status_id == 1}selected="selected"{/if}>{'status.completed'|devblocks_translate|lower}</option>
			</select>
		</div>
	</div>
</fieldset>
{/if}

<div class="behaviors">
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
</div>

<div style="margin:5px 0px 10px 0px;">
	<button type="button" class="chooser-behavior" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-query="" data-query-required="disabled:n private:n event:event.macro.*"><span class="glyphicons glyphicons-circle-plus"></span> {'common.behaviors'|devblocks_translate|capitalize}</button>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this project board column?
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
		var $behaviors = $popup.find('div.behaviors');
		
		$popup.dialog('option','title',"{'projects.common.board.column'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Triggers
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		// Choosers
		$popup.find('.chooser-abstract').cerbChooserTrigger()
		
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
		
		// Handle built-in actions
		$popup.find('input:checkbox[value=task_status]')
			.on('change', function(e) {
				var $this = $(this);
				var $params = $this.closest('fieldset').find('div.parameters');
				
				if($this.is(':checked')) {
					$params.fadeIn();
				} else {
					$params.hide();
				}
			})
			;
		
		// Behavior chooser
		$popup.find('.chooser-behavior')
			.click(function() {
				var $trigger = $(this);
				var context = $trigger.attr('data-context');
				var q = $trigger.attr('data-query');
				var qr = $trigger.attr('data-query-required');
				var single = $trigger.attr('data-single') != null ? '1' : '';
				var width = $(window).width()-100;
				var $chooser=genericAjaxPopup('chooser' + new Date().getTime(),'c=internal&a=chooserOpen&context=' + encodeURIComponent(context) + '&q=' + encodeURIComponent(q) + '&qr=' + encodeURIComponent(qr) + '&single=' + encodeURIComponent(single),null,true,width);
				
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
						
						genericAjaxGet($div, 'c=internal&a=showBehaviorParams&name_prefix=' + encodeURIComponent(name_prefix) + '&trigger_id=' + encodeURIComponent(behavior_id));
					}
				});
			})
		;
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
