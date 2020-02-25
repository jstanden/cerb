{$peek_context = $custom_record->getContext()}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="abstract_custom_record">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="_record_id" value="{$custom_record->id}">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	
	{if $owners_menu}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">
			<b>{'common.owner'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			{if 1 == count($owners_menu) && array_key_exists('App', $owners_menu)}
			{$owner = array_shift($owners_menu)}
			{$owner_parts = explode(':', $owner->key)}
			<ul class="chooser-container bubbles">
				<li>
					<img class="cerb-avatar" src="{devblocks_url}c=avatars&ctx=cerberusweb.contexts.app&id=0{/devblocks_url}?v="><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{$owner_parts.0}" data-context-id="{$owner_parts.1}">{$owner->label}</a>
					<input type="hidden" name="owner" value="{$owner->key}">
				</li>
			</ul>
			{else}
			{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $custom_record->hasOption('avatars')}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">{'common.photo'|devblocks_translate|capitalize}:</td>
		<td width="99%" valign="top">
			<div style="float:left;margin-right:5px;">
				<img class="cerb-avatar" src="{devblocks_url}c=avatars&context={$custom_record->uri}&context_id={$model->id}{/devblocks_url}?v={$model->updated_at}" style="height:50px;width:50px;">
			</div>
			<div style="float:left;">
				<button type="button" class="cerb-avatar-chooser" data-context="{$custom_record->getContext()}" data-context-id="{$model->id}">{'common.edit'|devblocks_translate|capitalize}</button>
				<input type="hidden" name="avatar_image">
			</div>
		</td>
	</tr>
	{/if}

	{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" tbody=true bulk=false}
	{/if}
</table>

{if $custom_record->hasOption('attachments')}
<fieldset class="peek" style="margin-top:10px;">
	<legend>{'common.attachments'|devblocks_translate|capitalize}</legend>
	<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
	<ul class="chooser-container bubbles">
		{if !empty($attachments)}
			{foreach from=$attachments item=attachment name=attachments}
				<li>
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-context-id="{$attachment->id}">
						<b>{$attachment->name}</b>
						({$attachment->storage_size|devblocks_prettybytes}	-
						{if !empty($attachment->mime_type)}{$attachment->mime_type}{else}{'display.convo.unknown_format'|devblocks_translate|capitalize}{/if})
					</a>
					<input type="hidden" name="file_ids[]" value="{$attachment->id}">
					<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>
				</li>
			{/foreach}
		{/if}
	</ul>
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this {$custom_record->name|lower}?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:5px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{$custom_record->name|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Owners
		{if $owners_menu}
		var $owners_menu = $popup.find('ul.owners-menu');
		var $ul = $owners_menu.siblings('ul.chooser-container');
		
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		$ul.on('bubble-remove', function(e, ui) {
			e.stopPropagation();
			$(e.target).closest('li').remove();
			$ul.hide();
			$owners_menu.show();
			
			$events.each(function() {
				$(this).hide();
			});
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
				
				// Contextual events
				$events.each(function() {
					var contexts = $(this).attr('contexts').split(' ');
					
					if($.inArray(context_data[0], contexts) != -1)
						$(this).show();
					else
						$(this).hide();
				});
			}
		});
		{/if}
		
		// Avatar chooser
		
		var $avatar_chooser = $popup.find('button.cerb-avatar-chooser');
		var $avatar_image = $avatar_chooser.closest('td').find('img.cerb-avatar');
		ajax.chooserAvatar($avatar_chooser, $avatar_image);

		// Attachments

		$popup.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});

		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
