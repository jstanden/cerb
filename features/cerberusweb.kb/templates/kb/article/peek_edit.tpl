{$peek_context = CerberusContexts::CONTEXT_KB_ARTICLE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="kb">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div id="kbArticleTabs{$form_id}">
	<ul>
		<li><a href="#kbArticleEditor{$form_id}">{'common.editor'|devblocks_translate|capitalize}</a></li>
		<li><a href="#kbArticleProperties{$form_id}">{'common.properties'|devblocks_translate|capitalize}</a></li>
		<li><a href="#kbArticleAttachments{$form_id}">{'common.attachments'|devblocks_translate|capitalize}</a></li>
	</ul>
	
	<div id="kbArticleEditor{$form_id}">
		<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
		<input type="text" name="title" value="{$model->title}" style="width:99%;border:solid 1px rgb(180,180,180);" autofocus="autofocus"><br>
		
		<div>
			<textarea id="content" name="content" style="width:99%;height:400px;border:solid 1px rgb(180,180,180);">{$model->content}</textarea>
		</div>
		
		<div>
			<div class="cerb-snippet-insert" style="display:inline-block;">
				<button type="button" class="cerb-chooser-trigger" data-field-name="snippet_id" data-context="{CerberusContexts::CONTEXT_SNIPPET}" data-query="" data-query-required="type:[plaintext,article]" data-single="true">{'common.snippets'|devblocks_translate|capitalize}</button>
				<ul class="bubbles chooser-container"></ul>
			</div>
			&nbsp; 
			<label><input type="radio" name="format" value="2" {if 2==$model->format || empty($model->format)}checked{/if}> <b>Markdown</b> (recommended)</label> [<a href="http://en.wikipedia.org/wiki/Markdown" target="_blank" rel="noopener">?</a>] 
			<label><input type="radio" name="format" value="1" {if 1==$model->format}checked{/if}> <b>HTML</b></label> 
		</div>
	</div>
	
	<div id="kbArticleProperties{$form_id}">
		<b>Add to Categories:</b><br>
		<div style="overflow:auto;height:150px;border:solid 1px rgb(180,180,180);background-color:rgb(255,255,255);">
			{foreach from=$levels item=depth key=node_id}
				<label>
					<input type="checkbox" name="category_ids[]" value="{$node_id}" onchange="div=document.getElementById('kbTreeCat{$node_id}');div.style.color=(this.checked)?'green':'';div.style.background=(this.checked)?'rgb(230,230,230)':'';" {if (empty($model) && $root_id==$node_id) || isset($article_categories.$node_id)}checked{/if}>
					<span style="padding-left:{math equation="(x-1)*10" x=$depth}px;{if !$depth}font-weight:bold;{/if}">{if $depth}<span class="glyphicons glyphicons-chevron-right" style="color:rgb(80,80,80);"></span>{else}<span class="glyphicons glyphicons-folder-closed" style="color:rgb(80,80,80);"></span>{/if} <span id="kbTreeCat{$node_id}" {if (empty($model) && $root_id==$node_id) || isset($article_categories.$node_id)}style="color:green;background-color:rgb(230,230,230);"{/if}>{$categories.$node_id->name}</span></span>
				</label>
				<br>
			{/foreach}
		</div>
		<br>
		
		{if !empty($custom_fields)}
		<fieldset class="peek">
			<legend>{'common.custom_fields'|devblocks_translate|capitalize}</legend>
			{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
		</fieldset>
		{/if}
		
		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}
	</div>
	
	<div id="kbArticleAttachments{$form_id}">
		{$attachments = DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_KB_ARTICLE, $model->id)}
	
		<b>{'common.attachments'|devblocks_translate|capitalize}:</b><br>
		<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
		<ul class="chooser-container bubbles cerb-attachments-container" style="display:block;">
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
	</div>
</div> 

{if !empty($model->id)}
<fieldset style="display:none;margin-top:10px;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this knowledgebase article?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

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
		$popup.dialog('option','title',"{'kb.common.knowledgebase_article'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Tabs
		$("#kbArticleTabs{$form_id}").tabs();
		
				$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
			;
		
		var $attachments_container = $popup.find('UL.cerb-attachments-container');
		
		// Snippets
		
		var $content = $popup.find('textarea[name=content]');
		
		$frm.find('.cerb-snippet-insert button.cerb-chooser-trigger')
			.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
				e.stopPropagation();
				var $this = $(this);
				var $ul = $this.siblings('ul.chooser-container');
				var $search = $ul.prev('input[type=search]');
				var $textarea = $('#kbArticleEditor textarea[name=content]');
				
				// Find the snippet_id
				var snippet_id = $ul.find('input[name=snippet_id]').val();
				
				if(null == snippet_id)
					return;
				
				// Remove the selection
				$ul.find('> li').find('span.glyphicons-circle-remove').click();
				
				// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
				var url = 'c=internal&a=snippetPaste&id=' + snippet_id;
				url += "&context_ids[cerberusweb.contexts.kb_article]={$article->id}";
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
		
		// Editor
		
		var markitupHTMLSettings = $.extend(true, { }, markitupHTMLDefaults);
		var markitupMarkdownSettings = $.extend(true, { }, markitupMarkdownDefaults);

		markitupMarkdownSettings.markupSet.splice(
			10,
			0,
			{ name:'Upload an Image', openWith: 
				function(markItUp) {
					var $chooser = genericAjaxPopup('chooser','c=internal&a=chooserOpenFile&single=1',null,true,'750');
					
					$chooser.one('chooser_save', function(event) {
						if(!event.response || 0 == event.response)
							return;
						
						{literal}$content.insertAtCursor("![inline-image]({{cerb_file_url(" + event.response[0].id + ",'" + event.response[0].name + "')}})");{/literal}

						// Add an attachment link
						
						if(0 == $attachments_container.find('input:hidden[value=' + event.response[0].id + ']').length) {
							var $li = $('<li/>');

							var $a = $('<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" />')
								.attr('data-context-id', event.response[0].id)
								.text(event.response[0].name + ' (' + event.response[0].size + ' bytes - ' + event.response[0].type + ')')
								.appendTo($li)
								.cerbPeekTrigger()
								;
							
							var $hidden = $('<input type="hidden" name="file_ids[]">')
								.val(event.response[0].id)
								.appendTo($li)
								;
							
							var $remove = $('<a href="javascript:;"><span class="glyphicons glyphicons-circle-remove"></span></a>')
								.click(function() {
									$(this).parent().remove();
								})
								.appendTo($li)
								;
							
							$attachments_container.append($li);
						}
					});
				},
				key: 'U',
				className:'image-inline'
			}
		);
		
		markitupHTMLSettings.markupSet.splice(
			13,
			0,
			{ name:'Upload an Image', openWith: 
				function(markItUp) {
					var $chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenFile&single=1',null,true,'750');
					
					$chooser.one('chooser_save', function(event) {
						if(!event.response || 0 == event.response)
							return;
						
						{literal}$content.insertAtCursor("<img src=\"{{cerb_file_url(" + event.response[0].id + ",'" + event.response[0].name + "')}}\" alt=\"\">");{/literal}
						
						// Add an attachment link
						
						var $li = $('<li/>');

						var $a = $('<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" />')
							.attr('data-context-id', event.response[0].id)
							.text(event.response[0].name + ' (' + event.response[0].size + ' bytes - ' + event.response[0].type + ')')
							.appendTo($li)
							.cerbPeekTrigger()
							;
						
						var $hidden = $('<input type="hidden" name="file_ids[]">')
							.val(event.response[0].id)
							.appendTo($li)
							;
						
						var $remove = $('<a href="javascript:;"><span class="glyphicons glyphicons-circle-remove"></span></a>')
							.click(function() {
								$(this).parent().remove();
							})
							.appendTo($li)
							;
						
						$attachments_container.append($li);
					});
				},
				key: 'U',
				className:'image-inline'
			}
		);
		
		delete markitupHTMLSettings.previewParserPath;
		delete markitupHTMLSettings.previewTemplatePath;
		delete markitupHTMLSettings.previewInWindow;

		markitupHTMLSettings.previewParserPath = DevblocksAppPath + 'ajax.php?c=profiles&a=handleSectionAction&section=kb&action=getEditorHtmlPreview&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content');
		
		delete markitupMarkdownSettings.previewParserPath;
		delete markitupMarkdownSettings.previewTemplatePath;
		delete markitupMarkdownSettings.previewInWindow;
		
		markitupMarkdownSettings.previewParserPath = DevblocksAppPath + 'ajax.php?c=profiles&a=handleSectionAction&section=kb&action=getEditorParsedownPreview&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content');
		
		{if 1==$article->format}
		$content.markItUp(markitupHTMLSettings);
		{else}
		$content.markItUp(markitupMarkdownSettings);
		{/if}

		$frm.find('input[name=format]').bind('click', function(event) {
			$content.markItUpRemove();
			if(2==$(event.target).val()) {
				$content.markItUp(markitupMarkdownSettings);
			} else if(1==$(event.target).val()) {
				$content.markItUp(markitupHTMLSettings);
			} 
		} );
		
		$frm.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
