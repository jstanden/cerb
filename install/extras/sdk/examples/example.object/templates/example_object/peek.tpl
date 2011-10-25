<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmExampleObject">
<input type="hidden" name="c" value="example.objects">
<input type="hidden" name="a" value="saveEntryPopup">
<input type="hidden" name="id" value="{$model->id}">
<input type="hidden" name="do_delete" value="0">

<fieldset>
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
			<td>
				<input type="text" name="name" value="{$model->name}" style="width:100%;">
			</td>
		</tr>
		
		{* Watchers *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="middle" align="right">{$translate->_('common.watchers')|capitalize}: </td>
			<td width="100%">
				{if empty($model->id)}
					<label><input type="checkbox" name="is_watcher" value="1"> {'common.watchers.add_me'|devblocks_translate}</label>
				{else}
					{$object_watchers = DAO_ContextLink::getContextLinks(Context_ExampleObject::ID, array($model->id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=Context_ExampleObject::ID context_id=$model->id}
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

<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmExampleObject','{$view_id}',false,'example_object_save');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>
{if $model->id}<button type="button" onclick="if(confirm('Permanently delete this example object?')) { this.form.do_delete.value='1';genericAjaxPopupPostCloseReloadView(null,'frmExampleObject','{$view_id}'); } "><span class="cerb-sprite2 sprite-minus-circle-frame"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('example.object.common.object')}");
		$(this).find('textarea[name=comment]').keyup(function() {
			if($(this).val().length > 0) {
				$(this).next('DIV.notify').show();
			} else {
				$(this).next('DIV.notify').hide();
			}
		});
	});
	$('#frmExampleObject button.chooser_notify_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
	});
</script>