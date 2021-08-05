{$peek_context = CerberusContexts::CONTEXT_TASK}
{$peek_context_id = $task->id}
{$tabset_id = "peek-editor-{DevblocksPlatform::strAlphaNum($peek_context,'','_')}"}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formTaskPeek" name="formTaskPeek" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="task">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$task->id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div id="{$tabset_id}" class="cerb-tabs">
	{if !$task->id && $packages}
	<ul>
		<li><a href="#task-library">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li><a href="#task-builder">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$task->id && $packages}
	<div id="task-library" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	<div id="task-builder">
		<table cellpadding="0" cellspacing="2" border="0" width="98%" style="margin-bottom:10px;">
			<tr>
				<td width="1%" nowrap="nowrap">{'common.title'|devblocks_translate|capitalize}: </td>
				<td width="99%">
					<input type="text" name="title" style="width:98%;" value="{$task->title}" autofocus="autofocus">
				</td>
			</tr>
			
			<tr>
				<td width="1%" nowrap="nowrap" valign="top">{'common.status'|devblocks_translate|capitalize}: </td>
				<td width="99%">
					<label><input type="radio" name="status_id" value="0" onclick="toggleDiv('taskClosed','none');" {if $task->status_id == 0}checked{/if}> {'status.open'|devblocks_translate|capitalize}</label>
					<label><input type="radio" name="status_id" value="2" onclick="toggleDiv('taskClosed','block');" {if $task->status_id == 2}checked{/if}> {'status.waiting.abbr'|devblocks_translate|capitalize}</label>
					<label><input type="radio" name="status_id" value="1" onclick="toggleDiv('taskClosed','none');" {if $task->status_id == 1}checked{/if}> {'status.closed'|devblocks_translate|capitalize}</label>
					
					<div id="taskClosed" style="display:{if in_array($task->status_id,[2])}block{else}none{/if};margin:5px 0px 5px 15px;">
						<b>When would you like to resume this task?</b><br>
						<i>{'display.reply.next.resume_eg'|devblocks_translate}</i><br>
						<input type="text" name="reopen_at" size="32" class="input_date" value="{if !empty($task->reopen_at)}{$task->reopen_at|devblocks_date}{/if}" style="width:75%;"><br>
					</div>
				</td>
			</tr>
			
			<tr>
				<td width="1%" nowrap="nowrap" valign="top">{'task.due_date'|devblocks_translate|capitalize}: </td>
				<td width="99%">
					<input type="text" name="due_date" size="45" class="input_date" value="{if !empty($task->due_date)}{$task->due_date|devblocks_date}{/if}">
				</td>
			</tr>
			
			{* Importance *}
			<tr>
				<td width="0%" nowrap="nowrap" valign="middle">{'common.importance'|devblocks_translate|capitalize}: </td>
				<td width="100%">
					<div class="cerb-delta-slider-container" style="margin-left:10px;">
					<input type="hidden" name="importance" value="{$task->importance|default:0}">
						<div class="cerb-delta-slider {if $task->importance < 50}cerb-slider-green{elseif $task->importance > 50}cerb-slider-red{else}cerb-slider-gray{/if}" title="{$task->importance}">
							<span class="cerb-delta-slider-midpoint"></span>
						</div>
					</div>
				</td>
			</tr>
			
			{* Owner *}
			<tr>
				<td width="1%" nowrap="nowrap" valign="middle">{'common.owner'|devblocks_translate|capitalize}: </td>
				<td width="99%" valign="top">
					<button type="button" class="chooser-abstract" data-field-name="owner_id" data-context="{CerberusContexts::CONTEXT_WORKER}" data-single="true" data-query="isDisabled:n" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
					
					<ul class="bubbles chooser-container">
						{if $task}
							{$owner = $task->getOwner()}
							{if $owner}
								<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$owner->id}{/devblocks_url}?v={$owner->updated}"><input type="hidden" name="owner_id" value="{$owner->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$owner->id}">{$owner->getName()}</a></li>
							{/if}
						{/if}
					</ul>
				</td>
			</tr>
			
			{* Watchers *}
			{if empty($task->id)}
			<tr>
				<td width="1%" nowrap="nowrap" valign="middle">{'common.watchers'|devblocks_translate|capitalize}: </td>
				<td width="99%" valign="top">
					<button type="button" class="chooser-abstract" data-field-name="add_watcher_ids[]" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-autocomplete=""><span class="glyphicons glyphicons-search"></span></button>
					<ul class="bubbles chooser-container" style="display:block;"></ul>
				</td>
			</tr>
			{/if}
			
			{if !empty($custom_fields)}
			{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
			{/if}
		</table>
		
		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$task->id}
		
		{include file="devblocks:cerberusweb.core::internal/cards/editors/comment.tpl"}

		{if !empty($task->id)}
		<fieldset style="display:none;" class="delete">
			<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
			
			<div>
				Are you sure you want to permanently delete this task?
			</div>
			
			<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
			<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
		</fieldset>
		{/if}
		
		<div class="buttons">
			{if $task->id}
				<button type="button" class="save"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
				{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
			{else}
				<button type="button" class="create"><span class="glyphicons glyphicons-circle-plus" style="color:rgb(0,180,0);"></span> {'common.create'|devblocks_translate|capitalize}</button>
			{/if}
		</div>
	</div>
</div>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#formTaskPeek');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title','Tasks');
		
		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.create').click({ mode: 'create' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Close confirmation

		$popup.on('dialogbeforeclose', function(e) {
			var keycode = e.keyCode || e.which;
			if(27 === keycode)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});
		
		// Abstract choosers
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Package Library
		
		{if !$task->id && $packages}
			var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
			tabOptions.active = Devblocks.getjQueryUiTabSelected('{$tabset_id}');
			
			var $tabs = $popup.find('.cerb-tabs').tabs(tabOptions);
			
			var $library_container = $tabs;
			{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}
			
			$library_container.on('cerb-package-library-form-submit', function(e) {
				$popup.one('peek_saved peek_error', function(e) {
					$library_container.triggerHandler('cerb-package-library-form-submit--done');
				});
				
				$popup.find('button.submit').click();
			});
		{/if}
		
		// Slider
		
		$frm.find('div.cerb-delta-slider').each(function() {
			var $this = $(this);
			var $input = $this.siblings('input:hidden');
			
			$this.slider({
				disabled: false,
				value: $input.val(),
				min: 0,
				max: 100,
				step: 1,
				range: 'min',
				slide: function(event, ui) {
					$this.removeClass('cerb-slider-gray cerb-slider-red cerb-slider-green');
					
					if(ui.value < 50) {
						$this.addClass('cerb-slider-green');
						$this.slider('option', 'range', 'min');
					} else if(ui.value > 50) {
						$this.addClass('cerb-slider-red');
						$this.slider('option', 'range', 'max');
					} else {
						$this.addClass('cerb-slider-gray');
						$this.slider('option', 'range', false);
					}
					
					$this.attr('title', ui.value);
				},
				stop: function(event, ui) {
					$input.val(ui.value);
				}
			});
		});
		
		$popup.find('input.input_date').cerbDateInputHelper();
		
	});
});
</script>