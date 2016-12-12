<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmKbEditPanel" onsubmit="return false;">
<input type="hidden" name="c" value="kb.ajax">
<input type="hidden" name="a" value="saveArticleEditPanel">
<input type="hidden" name="id" value="{$article->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div id="kbArticleTabs">
	<ul>
		<li><a href="#kbArticleEditor">Editor</a></li>
		<li><a href="#kbArticleProperties">{'common.properties'|devblocks_translate|capitalize}</a></li>
		<li><a href="#kbArticleAttachments">{'common.attachments'|devblocks_translate|capitalize}</a></li>
	</ul>
	
	<div id="kbArticleEditor">
		<b>Title:</b><br>
		<input type="text" name="title" value="{$article->title}" style="width:99%;border:solid 1px rgb(180,180,180);"><br>
		
		<div>
			<textarea id="content" name="content" style="width:99%;height:200px;border:solid 1px rgb(180,180,180);">{$article->content}</textarea>
		</div>
		
		<div>
			<button type="button" onclick="ajax.chooserSnippet('snippets',$('#kbArticleEditor textarea[name=content]'),{ '{CerberusContexts::CONTEXT_KB_ARTICLE}':'{$article->id}' } );">{'common.snippets'|devblocks_translate|capitalize}</button>
			 &nbsp; 
			<label><input type="radio" name="format" value="2" {if 2==$article->format || empty($article->format)}checked{/if}> <b>Markdown</b> (recommended)</label> [<a href="http://en.wikipedia.org/wiki/Markdown" target="_blank">?</a>] 
			<label><input type="radio" name="format" value="1" {if 1==$article->format}checked{/if}> <b>HTML</b></label> 
		</div>
	</div>
	
	<div id="kbArticleProperties">
		<b>Add to Categories:</b><br>
		<div style="overflow:auto;height:150px;border:solid 1px rgb(180,180,180);background-color:rgb(255,255,255);">
			{foreach from=$levels item=depth key=node_id}
				<label>
					<input type="checkbox" name="category_ids[]" value="{$node_id}" onchange="div=document.getElementById('kbTreeCat{$node_id}');div.style.color=(this.checked)?'green':'';div.style.background=(this.checked)?'rgb(230,230,230)':'';" {if (empty($article) && $root_id==$node_id) || isset($article_categories.$node_id)}checked{/if}>
					<span style="padding-left:{math equation="(x-1)*10" x=$depth}px;{if !$depth}font-weight:bold;{/if}">{if $depth}<span class="glyphicons glyphicons-chevron-right" style="color:rgb(80,80,80);"></span>{else}<span class="glyphicons glyphicons-folder-closed" style="color:rgb(80,80,80);"></span>{/if} <span id="kbTreeCat{$node_id}" {if (empty($article) && $root_id==$node_id) || isset($article_categories.$node_id)}style="color:green;background-color:rgb(230,230,230);"{/if}>{$categories.$node_id->name}</span></span>
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
		
		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_KB_ARTICLE context_id=$article->id}
	</div>
	
	<div id="kbArticleAttachments">
		{$attachments = DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_KB_ARTICLE, $article->id)}
	
		<b>{'common.attachments'|devblocks_translate|capitalize}:</b><br>
		<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
		<ul class="chooser-container bubbles cerb-attachments-container" style="display:block;">
		{if !empty($attachments)}
			{foreach from=$attachments item=attachment name=attachments}
			<li>
				<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-context-id="{$attachment->id}">
					<b>{$attachment->display_name}</b>
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

<div style="margin-top:10px;">
	{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" id="btnKbArticleEditSave"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>{/if} 
	{if !empty($article) && $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this article?')) { this.form.do_delete.value='1';$('#btnKbArticleEditSave').click(); } "><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	var $content = $popup.find("#content");
	
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','{'kb.common.knowledgebase_article'|devblocks_translate|escape:'javascript' nofilter}');
		$("#kbArticleTabs").tabs();
		$('#frmKbEditPanel :input:text:first').focus().select();
		
		var $attachments_container = $popup.find('UL.cerb-attachments-container');
		
		var markitupHTMLSettings = $.extend(true, { }, markitupHTMLDefaults);
		var markitupMarkdownSettings = $.extend(true, { }, markitupMarkdownDefaults);
		
		markitupMarkdownSettings.markupSet.splice(
			10,
			0,
			{ name:'Upload an Image', openWith: 
				function(markItUp) {
					var $chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenFile&single=1',null,true,'750');
					
					$chooser.one('chooser_save', function(event) {
						if(!event.response || 0 == event.response)
							return;
						
						$content.insertAtCursor("![inline-image](" + event.response[0].url + ")");

						// Add an attachment link
						
						if(0 == $attachments_container.find('input:hidden[value=' + event.response[0].id + ']').length) {
							var $li = $('<li/>');
							$li.text(event.response[0].name + ' ( ' + event.response[0].size + ' bytes - ' + event.response[0].type + ' )');
							
							var $hidden = $('<input type="hidden" name="file_ids[]">')
								.val(event.response[0].id)
								.appendTo($li)
								;
							
							var $a = $('<a href="javascript:;"><span class="glyphicons glyphicons-circle-remove"></span></a>');
							$a.click(function() {
								$(this).parent().remove();
							});
							$a.appendTo($li);
							
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
						
						$content.insertAtCursor("<img src=\"" + event.response[0].url + "\" alt=\"\">");
						
						// Add an attachment link
						
						if(0 == $attachments_container.find('input:hidden[value=' + event.response[0].id + ']').length) {
							var $li = $('<li/>');
							$li.text(event.response[0].name + ' ( ' + event.response[0].size + ' bytes - ' + event.response[0].type + ' )');
							
							var $hidden = $('<input type="hidden" name="file_ids[]" value="">')
								.val(event.response[0].id)
								.appendTo($li)
								;
							
							var $a = $('<a href="javascript:;"><span class="glyphicons glyphicons-circle-remove"></span></a>');
							$a.click(function() {
								$(this).parent().remove();
							});
							$a.appendTo($li);
							
							$attachments_container.append($li);
						}
					});
				},
				key: 'U',
				className:'image-inline'
			}
		);
		
		{if 1==$article->format}
		$content.markItUp(markitupHTMLSettings);
		{else}
		$content.markItUp(markitupMarkdownSettings);
		{/if}

		$frm = $('#frmKbEditPanel');

		$frm.find('input[name=format]').bind('click', function(event) {
			$content.markItUpRemove();
			if(2==$(event.target).val()) {
				$content.markItUp(markitupMarkdownSettings);
			} else if(1==$(event.target).val()) {
				$content.markItUp(markitupHTMLSettings);
			} 
		} );
		
		$('#btnKbArticleEditSave').bind('click', function() {
			genericAjaxPost('frmKbEditPanel', '', '', function(json) {
				genericAjaxPopupClose($popup, 'article_save');
				{if !empty($view_id)}
				genericAjaxGet('view{$view_id}','c=internal&a=viewRefresh&id={$view_id}');
				{/if}
			} );
		} );
		
		$frm.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
	});
	
});
</script>
