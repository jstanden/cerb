{$peek_context = CerberusContexts::CONTEXT_COMMENT}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="comment">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-form">
	{if $model->id}
		{$author = $model->getAuthorDictionary()}
		{if $author}
		<div>
			<label>{'common.author'|devblocks_translate|capitalize}:</label>
			<ul class="bubbles">
				<li>
					{if $author->_image_url}
					<img src="{$author->_image_url}" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;">
					{/if}
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{$author->_context}" data-context-id="{$author->id}">{$author->_label}</a>
				</li>
			</ul>
		</div>
		{/if}
	{/if}
	
	{if $model->context}
		{$target = $model->getTargetDictionary()}
		{if $target}
		<div>
			<label>{'common.on'|devblocks_translate|capitalize}:</label>
			<input type="hidden" name="context" value="{$target->_context}">
			<input type="hidden" name="context_id" value="{$target->id}">
			<ul class="bubbles">
				<li>
					{if $target->_image_url}
					<img src="{$target->_image_url}" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;">
					{/if}
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{$target->_context}" data-context-id="{$target->id}">{$target->_label}</a>
				</li>
			</ul>
		</div>
		{/if}
	{/if}
	
	<div>
		<label>
			{'common.comment'|devblocks_translate|capitalize}:
		</label>
		<textarea name="comment" rows="10" cols="60" style="width:98%;height:150px;" placeholder="{'comment.notify.at_mention'|devblocks_translate}" autofocus="autofocus">{$model->comment}</textarea>
	</div>
	
	<fieldset class="peek">
		<legend>{'common.snippets'|devblocks_translate|capitalize}</legend>
		<div class="cerb-snippet-insert" style="display:inline-block;">
			<button type="button" class="cerb-chooser-trigger" data-field-name="snippet_id" data-context="{CerberusContexts::CONTEXT_SNIPPET}" data-query="" data-query-required="type:[plaintext,worker]" data-single="true" data-autocomplete="type:[plaintext,worker]"><span class="glyphicons glyphicons-search"></span></button>
			<ul class="bubbles chooser-container"></ul>
		</div>
	</fieldset>
	
	<fieldset class="peek">
		<legend>{'common.attachments'|devblocks_translate|capitalize}</legend>
		<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
		<ul class="chooser-container bubbles"></ul>
	</fieldset>
	
	{if $model->id}
	<div>
		<label>{'common.options'|devblocks_translate|capitalize}:</label>
		<div style="margin-left: 10px;">
			<label style="font-weight:normal;">
				<input type="checkbox" name="options[update_timestamp]" value="1"> Update the comment timestamp
			</label>
		</div>
	</div>
	{/if}
</div>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this comment?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.comment'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		// Buttons
		
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
			;
		
		// Close confirmation
		
		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode == 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});
		
		// Editor
		
		var $textarea = $popup.find('textarea[name=comment]')
			.focus()
			;
		
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
		
		// Snippets
		
		$popup.find('.cerb-snippet-insert button.cerb-chooser-trigger')
			.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
				e.stopPropagation();
				var $this = $(this);
				var $ul = $this.siblings('ul.chooser-container');
				var $search = $ul.prev('input[type=search]');
				var $textarea = $('#{$form_id} textarea[name=comment]');
				
				// Find the snippet_id
				var snippet_id = $ul.find('input[name=snippet_id]').val();
				
				if(null == snippet_id)
					return;
				
				// Remove the selection
				$ul.find('> li').find('span.glyphicons-circle-remove').click();
				
				// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
				var url = 'c=internal&a=snippetPaste&id=' + snippet_id;
				url += "&context_ids[cerberusweb.contexts.worker]={$active_worker->id}";
				
				genericAjaxGet('',url,function(json) {
					// If the content has placeholders, use that popup instead
					if(json.has_custom_placeholders) {
						$textarea.focus();
						
						var $popup_paste = genericAjaxPopup('snippet_paste', 'c=internal&a=snippetPlaceholders&id=' + encodeURIComponent(json.id) + '&context_id=' + encodeURIComponent(json.context_id),null,false,'50%');
					
						$popup_paste.bind('snippet_paste', function(event) {
							if(null == event.text)
								return;
						
							$textarea.insertAtCursor(event.text).focus();
						});
						
					} else {
						$textarea.insertAtCursor(json.text).focus();
					}
					
					$search.val('');
				});
			})
		;
		
		// Attachments
		
		$popup.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
