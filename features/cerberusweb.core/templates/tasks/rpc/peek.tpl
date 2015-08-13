<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formTaskPeek" name="formTaskPeek" onsubmit="return false;">
<input type="hidden" name="c" value="tasks">
<input type="hidden" name="a" value="saveTaskPeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$task->id}">
{if !empty($link_context)}
<input type="hidden" name="link_context" value="{$link_context}">
<input type="hidden" name="link_context_id" value="{$link_context_id}">
{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap" align="right">{'common.title'|devblocks_translate|capitalize}: </td>
			<td width="99%">
				<input type="text" name="title" style="width:98%;" value="{$task->title}">
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" align="right" valign="top">{'task.due_date'|devblocks_translate|capitalize}: </td>
			<td width="99%">
				<input type="text" name="due_date" size="45" class="input_date" value="{if !empty($task->due_date)}{$task->due_date|devblocks_date}{/if}">
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" align="right" valign="top">{'task.is_completed'|devblocks_translate|capitalize}: </td>
			<td width="99%">
				<label><input type="radio" name="completed" value="1" {if $task->is_completed}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="completed" value="0" {if empty($task->is_completed)}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
			</td>
		</tr>
		
		{* Watchers *}
		<tr>
			<td width="1%" nowrap="nowrap" valign="top" align="right">{'common.watchers'|devblocks_translate|capitalize}: </td>
			<td width="99%">
				{if empty($task->id)}
					<button type="button" class="chooser_watcher"><span class="glyphicons glyphicons-search"></span></button>
					<ul class="chooser-container bubbles" style="display:block;"></ul>
				{else}
					{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TASK, array($task->id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_TASK context_id=$task->id full=true}
				{/if}
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

{* Comment *}
{include file="devblocks:cerberusweb.core::internal/peek/peek_comments_pager.tpl" comments=$comments}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="2" cols="45" style="width:98%;" placeholder="{'comment.notify.at_mention'|devblocks_translate}"></textarea>
</fieldset>

{if $active_worker->hasPriv('core.tasks.actions.create')}
	<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'formTaskPeek','{$view_id}',false,'task_save');"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
	{if $active_worker->hasPriv('core.tasks.actions.delete') && !empty($task)}<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this task?')) { $('#formTaskPeek input[name=do_delete]').val('1'); genericAjaxPopupPostCloseReloadView(null,'formTaskPeek','{$view_id}',false,'task_delete'); } "><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
{else}
	<fieldset class="delete">
		{'error.core.no_acl.edit'|devblocks_translate}
	</fieldset>
{/if}
{if !empty($task)}
<div style="float:right;">
	<a href="{devblocks_url}c=profiles&type=task&id={$task->id}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open',function(event,ui) {
		var $this = $(this);
		var $textarea = $this.find('textarea[name=comment]');
		
		$this.dialog('option','title','Tasks');

		// Form hints
		
		$textarea
			.focusin(function() {
				$(this).siblings('div.cerb-form-hint').fadeIn();
			})
			.focusout(function() {
				$(this).siblings('div.cerb-form-hint').fadeOut();
			})
			;
		
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

		// Watchers
		
		$this.find('button.chooser_watcher').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','add_watcher_ids', { autocomplete:true });
		});
		
		$this.find('input.input_date').cerbDateInputHelper();
		
		$('#formTaskPeek :input:text:first').focus().select();
	});
	
</script>
