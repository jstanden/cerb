<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmAddOppNote">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="saveOppNote">
<input type="hidden" name="opp_id" value="{$opp->id}">
<input type="hidden" name="id" value="">

<b>{'crm.opp.tab.notes.add_note'|devblocks_translate}:</b><br>
<textarea name="content" rows="10" cols="65" style="width:98%;height:150px;"></textarea><br>
{if !empty($workers)}
	<label><input type="checkbox" onclick="toggleDiv('addOppNoteNotifyWorkers');"> <b>{'common.notify_workers'|devblocks_translate}</b></label>
	<div id="addOppNoteNotifyWorkers" style="display:none;">
		<select name="notify_worker_ids[]" multiple="multiple" size="8">
			{foreach from=$active_workers item=worker name=notify_workers}
			{if $worker->id == $active_worker->id}{math assign=notify_me_id equation="x-1" x=$smarty.foreach.notify_workers.iteration}{/if}
			<option value="{$worker->id}">{$worker->getName()}</option>
			{/foreach}
		</select><br>
		{'common.tips.multi_select'|devblocks_translate}<br>
		{if !is_null($notify_me_id)}<button type="button" onclick="this.form.notify_worker_ids.options[{$notify_me_id}].selected=true;">{$translate->_('common.me')}</button>{/if} 
	</div>
{/if}
<br>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
<br>

{* Display Notes *}
{foreach from=$notes item=note}
	{include file="{$core_tpl}/internal/comments/comment.tpl"}
{/foreach}

</form>
