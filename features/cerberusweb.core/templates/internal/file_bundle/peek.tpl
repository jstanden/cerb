<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmFileBundlePeek">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="file_bundle">
<input type="hidden" name="action" value="savePeek">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
			<td width="99%">
				<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.tag'|devblocks_translate}:</b></td>
			<td width="99%">
				<input type="text" name="tag" value="{$model->tag}" style="width:50%;" placeholder="example" title="This tag can be used in snippets and other text commands to quickly attach these files. The tag name can contain letters, numbers, dashes, and underscores, without spaces.">
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.owner'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%">
				{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
			</td>
		</tr>
	</table>
	
</fieldset>

<fieldset class="peek">
	<legend>{'common.attachments'|devblocks_translate|capitalize}</legend>
	
	<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
	<ul class="chooser-container bubbles" style="display:inline-block;">
		{foreach from=$attachments item=attachment}
		<li>
		{$attachment->display_name} ({$attachment->storage_size|devblocks_prettybytes:1})
		<input type="hidden" name="file_ids[]" value="{$attachment->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>
		</li>
		{/foreach}
	</ul>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_FILE_BUNDLE context_id=$model->id}

{* Comments *}
{include file="devblocks:cerberusweb.core::internal/peek/peek_comments_pager.tpl" comments=$comments}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="2" cols="45" style="width:98%;" placeholder="{'comment.notify.at_mention'|devblocks_translate}"></textarea>
</fieldset>

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this file bundle?
	</div>
	
	<button type="button" class="delete" onclick="var $frm=$(this).closest('form');$frm.find('input:hidden[name=do_delete]').val('1');$frm.find('button.submit').click();"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> Confirm</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons">
	<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView(null,'frmFileBundlePeek','{$view_id}', false, 'file_bundle_save');"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {$translate->_('common.save_changes')|capitalize}</button>
	{if !empty($model->id)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

{if !empty($model->id)}
<div style="float:right;">
	<a href="{devblocks_url}c=profiles&type=file_bundle&id={$model->id}-{$model->name|devblocks_permalink}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'File Bundle'|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		var $textarea = $popup.find('textarea[name=comment]');
		
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		$textarea.autosize();
		
		// Owner
		
		var $owners_menu = $popup.find('fieldset:first ul.owners-menu');
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
				var $label = $('<a href="javascript:;" class="cerb-peek-trigger no-underline" />').attr('data-context',context_data[0]).attr('data-context-id',context_data[1]).text(label);
				$label.cerbPeekTrigger().appendTo($li);
				var $hidden = $('<input type="hidden">').attr('name', 'owner').attr('value',token).appendTo($li);
				ui.item.find('img.cerb-avatar').clone().prependTo($li);
				var $a = $('<a href="javascript:;" onclick="$(this).trigger(\'bubble-remove\');"><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li);
				
				$ul.find('> *').remove();
				$ul.append($li);
				$ul.show();
			}
		});
		
		// Attachments
		
		$popup.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		// @mentions
		
		var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};

		$textarea.atwho({
			at: '@',
			{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${title}</small> <small style="margin-left:10px;">@${at_mention}</small></li>',{/literal}
			{literal}insertTpl: '@${at_mention}',{/literal}
			data: atwho_workers,
			searchKey: '_index',
			limit: 10
		});
	});
});	
</script>
