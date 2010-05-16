<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmAddOrgNote">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveOrgNote">
<input type="hidden" name="org_id" value="{$org->id}">
<input type="hidden" name="id" value="">

<b>{'addy_book.org.tabs.notes.add'|devblocks_translate:$org->name}</b><br>
<textarea name="content" rows="10" cols="65" style="width:98%;height:150px;"></textarea><br>
{if !empty($workers)}
	<label><input type="checkbox" onclick="toggleDiv('addOrgNoteNotifyWorkers');"> <b>{$translate->_('common.notify_workers')}</b></label>
	<div id="addOrgNoteNotifyWorkers" style="display:none;">
		<select name="notify_worker_ids[]" multiple="multiple" size="8">
			{foreach from=$active_workers item=worker name=notify_workers}
			{if $worker->id == $active_worker->id}{math assign=notify_me_id equation="x-1" x=$smarty.foreach.notify_workers.iteration}{/if}
			<option value="{$worker->id}">{$worker->getName()}</option>
			{/foreach}
		</select><br>
		{$translate->_('common.tips.multi_select')}<br>
		{if !is_null($notify_me_id)}<button type="button" onclick="this.form.notify_worker_ids.options[{$notify_me_id}].selected=true;">{$translate->_('common.me')}</button>{/if} 
	</div>
{/if}
<br>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
<br>

{* Display Notes *}
{foreach from=$notes item=note}
	{include file="{$core_tpl}/internal/notes/note.tpl"}
{/foreach}

</form>
