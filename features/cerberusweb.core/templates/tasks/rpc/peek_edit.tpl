<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formTaskPeek" name="formTaskPeek" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="task">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$task->id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap" align="right">{'common.title'|devblocks_translate|capitalize}: </td>
			<td width="99%">
				<input type="text" name="title" style="width:98%;" value="{$task->title}" autofocus="autofocus">
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" align="right" valign="top">{'common.status'|devblocks_translate|capitalize}: </td>
			<td width="99%">
				<label><input type="radio" name="completed" value="0" {if empty($task->is_completed)}checked="checked"{/if}> {'status.open'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="completed" value="1" {if $task->is_completed}checked="checked"{/if}> {'status.completed'|devblocks_translate|capitalize}</label>
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" align="right" valign="top">{'task.due_date'|devblocks_translate|capitalize}: </td>
			<td width="99%">
				<input type="text" name="due_date" size="45" class="input_date" value="{if !empty($task->due_date)}{$task->due_date|devblocks_date}{/if}">
			</td>
		</tr>
		
		{* Importance *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="middle" align="right">{'common.importance'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<div class="cerb-delta-slider-container">
				<input type="hidden" name="importance" value="{$task->importance|default:0}">
					<div class="cerb-delta-slider {if $task->importance < 50}cerb-slider-green{elseif $task->importance > 50}cerb-slider-red{else}cerb-slider-gray{/if}">
						<span class="cerb-delta-slider-midpoint"></span>
					</div>
				</div>
			</td>
		</tr>
		
		{* Owner *}
		<tr>
			<td width="1%" nowrap="nowrap" align="right" valign="middle">{'common.owner'|devblocks_translate|capitalize}:</td>
			<td width="99%" valign="top">
					<button type="button" class="chooser-abstract" data-field-name="owner_id" data-context="{CerberusContexts::CONTEXT_WORKER}" data-single="true" data-query="" data-autocomplete="if-null"><span class="glyphicons glyphicons-search"></span></button>
					
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
		
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TASK context_id=$task->id}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="2" cols="45" style="width:98%;" placeholder="{'comment.notify.at_mention'|devblocks_translate}"></textarea>
</fieldset>

{if !empty($task->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this task?
	</div>
	
	<button type="button" class="delete red"></span> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"></span> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $active_worker->hasPriv('core.tasks.actions.delete') && !empty($task)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#formTaskPeek');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open',function(event,ui) {
		var $textarea = $popup.find('textarea[name=comment]');
		
		$popup.dialog('option','title','Tasks');
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Abstract choosers
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

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
				},
				stop: function(event, ui) {
					$input.val(ui.value);
				}
			});
		});
		
		// @mentions
		
		var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};

		$textarea.atwho({
			at: '@',
			{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${title}</small> <small style="margin-left:10px;">@${at_mention}</small></li>',{/literal}
			{literal}insertTpl: '@${at_mention}',{/literal}
			data: atwho_workers,
			searchKey: '_index',
			limit: 10
		});

		$popup.find('input.input_date').cerbDateInputHelper();
	});
});
</script>