{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
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
			{'common.comment'|devblocks_translate|capitalize}:</b>
		</label>
		<textarea name="comment" rows="2" cols="60" style="width:98%;" placeholder="{'comment.notify.at_mention'|devblocks_translate}" autofocus="autofocus">{$model->comment}</textarea>
		<button type="button" onclick="ajax.chooserSnippet('snippets',$('#{$form_id} textarea[name=comment]'), { '{$context}':'{$context_id}', '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });">{'common.snippets'|devblocks_translate|lower}</button>
	</div>
	
	<div>
		<label>{'common.attachments'|devblocks_translate|capitalize}:</label>
		<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
		<ul class="chooser-container bubbles"></ul>
	</div>
</div>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context='cerberusweb.contexts.comment' context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this comment?
	</div>
	
	<button type="button" class="delete red"></span> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"></span> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
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
		
		// Editor
		
		var $textarea = $popup.find('textarea[name=comment]')
			.autosize()
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
		
		// Attachments
		
		$popup.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
	});
});
</script>
