<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmServer">
<input type="hidden" name="c" value="datacenter">
<input type="hidden" name="a" value="saveServerPeek">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset>
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
			<td width="99%">
				<input type="text" name="name" value="{$model->name}" style="width:98%;">
			</td>
		</tr>
		
		{* Watchers *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="middle" align="right">{$translate->_('common.watchers')|capitalize}: </td>
			<td width="100%">
				{if empty($model->id)}
					<label><input type="checkbox" name="is_watcher" value="1"> {'common.watchers.add_me'|devblocks_translate}</label>
				{else}
					{$object_watchers = DAO_ContextLink::getContextLinks('cerberusweb.contexts.datacenter.server', array($model->id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context='cerberusweb.contexts.datacenter.server' context_id=$model->id full=true}
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

<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmServer','{$view_id}', false, 'datacenter_server_save');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>
{if $model->id && $active_worker->is_superuser}<button type="button" onclick="if(confirm('Permanently delete this server?')) { this.form.do_delete.value='1';genericAjaxPopupPostCloseReloadView(null,'frmServer','{$view_id}'); } "><span class="cerb-sprite2 sprite-minus-circle-frame"></span> {$translate->_('common.delete')|capitalize}</button>{/if}

{if !empty($model->id)}
<div style="float:right;">
	<a href="{devblocks_url}c=datacenter&a=server&id={$model->id}-{$model->name|devblocks_permalink}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'cerberusweb.datacenter.common.server'|devblocks_translate|capitalize}");
		$(this).find('textarea[name=comment]').keyup(function() {
			if($(this).val().length > 0) {
				$(this).next('DIV.notify').show();
			} else {
				$(this).next('DIV.notify').hide();
			}
		});
		$('#frmServer button.chooser_notify_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
		});
		
		$(this).find('input:text:first').focus();
	} );
</script>
