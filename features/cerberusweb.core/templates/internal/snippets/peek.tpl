<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formSnippetsPeek" name="formSnippetsPeek" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveSnippetsPeek">
<input type="hidden" name="id" value="{$snippet->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<fieldset>
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<table cellpadding="2" cellspacing="0" border="0" width="100%">
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
			</td>
			<td width="99%">
				<input type="text" name="title" value="{$snippet->title}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;"><br>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.type'|devblocks_translate|capitalize}:</b><br>
			</td>
			<td width="99%">
				<select name="context">
					<option value="" {if empty($snippet->id)}selected="selected"{/if}>Plaintext</option>
					{foreach from=$contexts item=ctx key=k}
					{if is_array($ctx->params.options.0) && isset($ctx->params.options.0.snippets)}
					<option value="{$k}" {if $snippet->context==$k}selected="selected"{/if}>{$ctx->name}</option>
					{/if}
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.owner'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%">
				<select name="owner">
					{if !empty($snippet->id)}
						<option value=""> - transfer - </option>
					{/if}
					
					<option value="w_{$active_worker->id}" {if $snippet->owner_context==CerberusContexts::CONTEXT_WORKER && $active_worker->id==$snippet->owner_context_id}selected="selected"{/if}>me</option>
					
					{if !empty($owner_groups)}
					{foreach from=$owner_groups item=group key=group_id}
						<option value="g_{$group_id}" {if $snippet->owner_context==CerberusContexts::CONTEXT_GROUP && $group_id==$snippet->owner_context_id}selected="selected"{/if}>Group: {$group->name}</option>
					{/foreach}
					{/if}
					
					{if $active_worker->is_superuser}
					{foreach from=$workers item=worker key=worker_id}
						{if empty($worker->is_disabled)}
						<option value="w_{$worker_id}" {if $snippet->owner_context==CerberusContexts::CONTEXT_WORKER && $worker_id==$snippet->owner_context_id && $active_worker->id != $worker_id}selected="selected"{/if}>Worker: {$worker->getName()}</option>
						{/if}
					{/foreach}
					{/if}
				</select>
				
				{if !empty($snippet->id)}
				<ul class="bubbles">
					<li>
					{if $snippet->owner_context==CerberusContexts::CONTEXT_GROUP && isset($groups.{$snippet->owner_context_id})}
					<b>{$groups.{$snippet->owner_context_id}->name}</b> (Group)
					{/if}
					
					{if $snippet->owner_context==CerberusContexts::CONTEXT_WORKER && isset($workers.{$snippet->owner_context_id})}
					<b>{$workers.{$snippet->owner_context_id}->getName()}</b> (Worker)
					{/if}
					</li>
				</ul>
				{/if}
			</td>
		</tr>
	</table>
	
	<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
	<textarea name="content" style="width:98%;height:200px;border:1px solid rgb(180,180,180);padding:2px;">{$snippet->content}</textarea>
	<div class="toolbar"></div>
	
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{if $active_worker->hasPriv('core.snippets.actions.create')}
	<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formSnippetsPeek', 'view{$view_id}')"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>
{else}
	<fieldset class="delete" style="font-weight:bold;">
		{'error.core.no_acl.edit'|devblocks_translate}
	</fieldset>
{/if}
{if !empty($snippet->id)}
	{if $snippet->isWriteableByWorker($active_worker)}
	<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this snippet?')) { this.form.do_delete.value='1';genericAjaxPopupClose('peek');genericAjaxPost('formSnippetsPeek', 'view{$view_id}'); } "><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {$translate->_('common.delete')|capitalize}</button>
	{/if}
{/if}
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		{if empty($snippet->id)}
		$(this).dialog('option','title', 'Create Snippet');
		{else}
		$(this).dialog('option','title', 'Modify Snippet');
		{/if}

		// Change
		
		$change_dropdown = $popup.find("form select[name=context]");
		$change_dropdown.change(function(e) {
			ctx = $(this).val();
			genericAjaxGet($popup.find('DIV.toolbar'), 'c=internal&a=showSnippetsPeekToolbar&context=' + ctx);
		});
		
		// [TODO] If editing and a target context is known
		{if !empty($snippet->context)}
		genericAjaxGet($popup.find('DIV.toolbar'), 'c=internal&a=showSnippetsPeekToolbar&context={$snippet->context}');
		{/if}
		
		$(this).find('input:text:first').focus().select();
	} );
</script>
