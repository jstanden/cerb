<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmCallEntry">
<input type="hidden" name="c" value="calls">
<input type="hidden" name="a" value="saveEntry">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
{if empty($id) && !empty($context)}
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">
{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset>
	<legend>{'common.properties'|devblocks_translate}</legend>

	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td colspan="2">
				<div style="float:left;">
					<b>Type:</b>
					<label><input type="radio" name="is_outgoing" value="0" {if empty($model) || !$model->is_outgoing}checked="checked"{/if}> Incoming</label>
					<label><input type="radio" name="is_outgoing" value="1" {if !empty($model) && $model->is_outgoing}checked="checked"{/if}> Outgoing</label>
				</div>
				<div style="float:right;">
					<b>{'common.status'|devblocks_translate|capitalize}:</b>
					<label><input type="radio" name="is_closed" value="0" {if empty($model) || !$model->is_closed}checked="checked"{/if}> {'status.open'|devblocks_translate|capitalize}</label>
					<label><input type="radio" name="is_closed" value="1" {if !empty($model) && $model->is_closed}checked="checked"{/if}> {'status.closed'|devblocks_translate|capitalize}</label>
				</div>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>Subject:</b></td>
			<td width="99%" valign="top">
				<input type="text" name="subject" value="{$model->subject}" style="width:98%;">
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>Phone #:</b></td>
			<td width="99%" valign="top">
				<input type="text" name="phone" value="{$model->phone}" style="width:98%;">
			</td>
		</tr>
		
		{* Watchers *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="middle" align="right">{$translate->_('common.watchers')|capitalize}: </td>
			<td width="100%">
				{if empty($model->id)}
					<label><input type="checkbox" name="is_watcher" value="1"> {'common.watchers.add_me'|devblocks_translate}</label>
				{else}
					{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_CALL, array($model->id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_CALL context_id=$model->id full=true}
				{/if}
			</td>
		</tr>

	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{* Comment *}
{if !empty($last_comment)}
	{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" readonly=true comment=$last_comment}
{/if}

<fieldset>
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="5" cols="45" style="width:98%;"></textarea>
	<div class="notify" style="display:none;">
		<b>{'common.notify_watchers_and'|devblocks_translate}:</b>
		<button type="button" class="chooser_notify_worker"><span class="cerb-sprite sprite-view"></span></button>
		<ul class="chooser-container bubbles" style="display:block;"></ul>
	</div>
</fieldset>

<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmCallEntry','{$view_id}', false, 'call_save');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>
{if $model->id && ($active_worker->is_superuser || $active_worker->id == $model->worker_id)}<button type="button" onclick="if(confirm('Permanently delete this call entry?')) { this.form.do_delete.value='1';genericAjaxPopupPostCloseReloadView(null,'frmCallEntry','{$view_id}'); } "><span class="cerb-sprite2 sprite-minus-circle-frame"></span> {$translate->_('common.delete')|capitalize}</button>{/if}

{if !empty($model->id)}
<div style="float:right;">
	<a href="{devblocks_url}c=calls&id={$model->id}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('calls.ui.log_call')}");
		ajax.orgAutoComplete('#orginput');
		ajax.emailAutoComplete('#emailinput');
		$(this).find('textarea[name=comment]').keyup(function() {
			if($(this).val().length > 0) {
				$(this).next('DIV.notify').show();
			} else {
				$(this).next('DIV.notify').hide();
			}
		});
	});
	$('#frmCallEntry button.chooser_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
	});
	$('#frmCallEntry button.chooser_notify_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
	});
</script>
