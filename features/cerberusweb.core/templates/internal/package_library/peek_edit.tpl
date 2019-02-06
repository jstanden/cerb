{$peek_context = CerberusContexts::CONTEXT_PACKAGE}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="package">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.description'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="description" value="{$model->description}" style="width:98%;">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b><abbr title="A unique name for this package. Letters, numbers, and underscores.">{'common.uri'|devblocks_translate}</abbr>:</b></td>
		<td width="99%">
			<input type="text" name="uri" value="{$model->uri}" style="width:98%;" spellcheck="false">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.extension.point'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="point" value="{$model->point}" style="width:98%;" spellcheck="false">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.image'|devblocks_translate|capitalize}:</b></td>
		<td width="99%" valign="top">
			<div style="float:left;margin-right:5px;border:1px solid rgb(200,200,200);">
				<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=package&context_id={$model->id}{/devblocks_url}?v={$model->updated_at}" style="width:480px;height:270px;border-radius:0;margin:0;">
			</div>
			<div style="float:left;">
				<button type="button" class="cerb-avatar-chooser" data-context="{CerberusContexts::CONTEXT_PACKAGE}" data-context-id="{$model->id}" data-image-width="480" data-image-height="270" data-create-defaults="">{'common.edit'|devblocks_translate|capitalize}</button>
				<input type="hidden" name="avatar_image">
			</div>
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

<div style="margin-top:10px;">
	<b>{'common.package'|devblocks_translate|capitalize}</b> (JSON): {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/packages/"}<br>
	<textarea name="package_json" class="cerb-code-editor" data-editor-mode="ace/mode/json">{if $model}{$model->getPackageJson()}{/if}</textarea>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this package?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}


<div class="buttons" style="margin-top:10px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.package'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		var $avatar_chooser = $popup.find('button.cerb-avatar-chooser');
		var $avatar_image = $avatar_chooser.closest('td').find('img.cerb-avatar');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Form elements
		$popup.find('.cerb-code-editor')
			.cerbCodeEditor()
			;
		
		// Avatar
		
		// [TODO] Default size
		ajax.chooserAvatar($avatar_chooser, $avatar_image);
	});
});
</script>
