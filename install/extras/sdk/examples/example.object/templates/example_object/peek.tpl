<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmExampleObject">
<input type="hidden" name="c" value="example.objects">
<input type="hidden" name="a" value="saveEntryPopup">
<input type="hidden" name="id" value="{$model->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<fieldset class="peek">
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
			<td width="0%" nowrap="nowrap" valign="middle" align="right">{'common.watchers'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				{if empty($model->id)}
					<button type="button" class="chooser_watcher"><span class="cerb-sprite sprite-view"></span></button>
					<ul class="chooser-container bubbles" style="display:block;"></ul>
				{else}
					{$object_watchers = DAO_ContextLink::getContextLinks(Context_ExampleObject::ID, array($model->id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=Context_ExampleObject::ID context_id=$model->id full=true}
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

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=Context_ExampleObject::ID context_id=$model->id}

{* Comments *}
{include file="devblocks:cerberusweb.core::internal/peek/peek_comments_pager.tpl" comments=$comments}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="5" cols="45" style="width:98%;"></textarea>
	<div style="float:right;color:rgb(120,120,120);">{'comment.notify.at_mention'|devblocks_translate}</div>
</fieldset>

<fieldset class="delete" style="display:none;">
	<legend>Are you sure you want to delete this project?</legend>
	
	<button type="button" class="red" onclick="$frm=$(this).closest('form');$frm.find('input:hidden[name=do_delete]').val('1');$frm.find('button.submit').click();">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$fieldset=$(this).closest('fieldset').fadeOut();$fieldset.siblings('div.toolbar').fadeIn();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>

<div class="toolbar">
	<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView(null,'frmExampleObject','{$view_id}',false,'example_object_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $model->id}<button type="button" onclick="$toolbar=$(this).closest('div.toolbar').fadeOut();$toolbar.siblings('fieldset.delete').fadeIn();"><span class="cerb-sprite2 sprite-minus-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		var $textarea = $(this).find('textarea[name=comment]');
		
		$(this).dialog('option','title',"{'example.object.common.object'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		$(this).find('button.chooser_watcher').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','add_watcher_ids', { autocomplete:true });
		});
		
		$(this).find('input:text:first').select().focus();
		
		// @mentions
		
		var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};

		$textarea.atwho({
			at: '@',
			{literal}tpl: '<li data-value="@${at_mention}">${name} <small style="margin-left:10px;">${title}</small></li>',{/literal}
			data: atwho_workers,
			limit: 10
		});
	});
</script>