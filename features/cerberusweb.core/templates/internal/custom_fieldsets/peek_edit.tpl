{$peek_context = CerberusContexts::CONTEXT_CUSTOM_FIELDSET}
{$peek_context_id = $model->id}
{$is_writeable = !$model->id || Context_CustomFieldset::isWriteableByActor($model, $active_worker)}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="custom_fieldset">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">
			<b>{'common.name'|devblocks_translate|capitalize}:</b><br>
		</td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;" autofocus="autofocus"><br>
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">
			<b>{'common.type'|devblocks_translate|capitalize}:</b><br>
		</td>
		<td width="99%">
			{if !empty($model->id)}
				<input type="hidden" name="context" value="{$model->context}">
				{if $contexts.{$model->context}}
					{$contexts.{$model->context}->name}
				{/if}
			{else}
			<select name="context">
				{foreach from=$contexts item=ctx key=k}
				<option value="{$k}" {if $model->context==$k}selected="selected"{/if}>{$ctx->name}</option>
				{/foreach}
			</select>
			{/if}
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">
			<b>{'common.owner'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl" model=$model}
		</td>
	</tr>
</table>

{if !empty($model->id) && $is_writeable}
<fieldset class="delete" style="display:none;">
	<legend>Delete this custom fieldset?</legend>
	<p>Are you sure you want to permanently delete this custom fieldset?  All custom fields and their values will be removed.</p>
	
	<button type="button" class="red delete">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="delete-cancel">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons" style="margin-top:10px;">
{if $active_worker->hasPriv("contexts.{$peek_context}.update") && (empty($model->id) || $is_writeable)}
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>
{/if}
{if $model->id && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title', '{'common.custom_fieldset'|devblocks_translate|capitalize|escape:'javascript' nofilter}');
		$popup.css('overflow', 'inherit');
		
		{if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
		$popup.find('input:text:first').focus().select();
		{else}
		$popup.find('input,select,textarea').attr('disabled','disabled');
		{/if}
		
		// Buttons
		
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete-prompt').click(Devblocks.callbackPeekEditDeletePrompt);
		$popup.find('button.delete-cancel').click(Devblocks.callbackPeekEditDeleteCancel);

		// Owners
		
		var $owners_menu = $popup.find('ul.owners-menu');
		var $ul = $owners_menu.siblings('ul.chooser-container');
		
		$ul.on('bubble-remove', function(e, ui) {
			e.stopPropagation();
			$(e.target).closest('li').remove();
			$ul.hide();
			$owners_menu.show();
		});
		
		$owners_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');
				
				if(undefined == token || undefined == label)
					return;
				
				$owners_menu.hide();
				
				// Build bubble
				
				var context_data = token.split(':');
				var $li = $('<li/>');
				let $label = $('<a class="cerb-peek-trigger no-underline" />').attr('data-context',context_data[0]).attr('data-context-id',context_data[1]).text(label);
				$label.cerbPeekTrigger().appendTo($li);
				$('<input type="hidden">').attr('name', 'owner').attr('value',token).appendTo($li);
				ui.item.find('img.cerb-avatar').clone().prependTo($li);
				let $a = $('<a><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li);
				$a.on('click', function(e) {
					e.stopPropagation();
					$(this).trigger('bubble-remove');
				});
				
				$ul.find('> *').remove();
				$ul.append($li);
				$ul.show();
			}
		});
		
	});
});
</script>