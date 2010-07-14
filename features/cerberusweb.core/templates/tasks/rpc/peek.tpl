<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formTaskPeek" name="formTaskPeek" onsubmit="return false;">
<input type="hidden" name="c" value="tasks">
<input type="hidden" name="a" value="saveTaskPeek">
<input type="hidden" name="id" value="{$task->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">{'task.title'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<input type="text" name="title" style="width:98%;" value="{$task->title|escape}">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{'task.due_date'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<input type="text" name="due_date" size="45" value="{if !empty($task->due_date)}{$task->due_date|devblocks_date}{/if}"><button type="button" onclick="devblocksAjaxDateChooser(this.form.due_date,'#dateTaskDue');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
			<div id="dateTaskDue"></div>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><label for="checkTaskCompleted">{'task.is_completed'|devblocks_translate|capitalize}:</label> </td>
		<td width="100%">
			<input id="checkTaskCompleted" type="checkbox" name="completed" value="1" {if $task->is_completed}checked{/if}>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.owners'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			{if !empty($context_workers)}
			<div class="chooser-container">
				{foreach from=$context_workers item=context_worker}
				<div><button type="button" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash"></span></button> {$context_worker->getName()|escape}<input type="hidden" name="worker_id[]" value="{$context_worker->id}"></div>
				{/foreach}
			</div>
			{/if}
			<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-add"></span></button>
		</td>
	</tr>
	{if empty($task->id)}
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.content'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<textarea name="content" style="width:98%;height:100px;"></textarea>
		</td>
	</tr>
	{/if}
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=false}

{* Comment *}
{if !empty($last_comment)}
	<br>
	{include file="file:$core_tpl/internal/comments/comment.tpl" readonly=true comment=$last_comment}
{/if}
<br>

{if $active_worker->hasPriv('core.tasks.actions.create')}
	<button type="button" onclick="genericAjaxPopupClose('peek', 'task_save');genericAjaxPost('formTaskPeek', 'view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
	{if !empty($task)}<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this task?')) { $('#formTaskPeek input[name=do_delete]').val('1'); genericAjaxPost('formTaskPeek', 'view{$view_id}'); genericAjaxPopupClose('peek'); } "><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
{else}
	<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>
{/if}
{if !empty($task)}
<div style="float:right;">
	<a href="{devblocks_url}c=tasks&a=display&id={$task->id}{/devblocks_url}">view full record</a>
</div>
{/if}
</form>

<script type="text/javascript">
	var $popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title','Tasks');
		$('#formTaskPeek :input:text:first').focus().select();
	});
	$('#formTaskPeek button.chooser_worker').click(function() {
		$button = $(this);
		$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpen&context=cerberusweb.contexts.worker',null,true,'750');
		$chooser.one('chooser_save', function(event) {
			$label = $button.prev('div.chooser-container');
			if(0==$label.length)
				$label = $('<div class="chooser-container"></div>').insertBefore($button);
			for(var idx in event.labels)
				if(0==$label.find('input:hidden[value='+event.values[idx]+']').length)
					$label.append($('<div><button type="button" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash"></span></button> '+event.labels[idx]+'<input type="hidden" name="worker_id[]" value="'+event.values[idx]+'"></div>'));
		});
	});
</script>
