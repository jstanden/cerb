{$peek_context = CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE}
{$peek_context_id = $model->id}
<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/rangy/rangy-core.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/rangy/rangy-classapplier.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/rangy/rangy-highlighter.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>

{$frm_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$frm_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="classifier_example">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$model->id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="100%" style="margin-bottom:5px;">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.classifier'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<button type="button" class="chooser-abstract" data-field-name="classifier_id" data-context="{CerberusContexts::CONTEXT_CLASSIFIER}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $model}
					{$classifier = $model->getClassifier()}
					{if $classifier}
						<li><input type="hidden" name="classifier_id" value="{$classifier->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CLASSIFIER}" data-context-id="{$classifier->id}">{$classifier->name}</a></li>
					{/if}
				{/if}
			</ul>
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.classifier.classification'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<button type="button" class="chooser-abstract" data-field-name="class_id" data-context="{CerberusContexts::CONTEXT_CLASSIFIER_CLASS}" data-single="true" data-query="{if $model->classifier_id}classifier.id:{$model->classifier_id}{/if}" data-autocomplete="{if $model->classifier_id}classifier.id:{$model->classifier_id}{/if}"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $model}
					{$class = $model->getClass()}
					{if $class}
						<li><input type="hidden" name="class_id" value="{$class->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CLASSIFIER_CLASS}" data-context-id="{$class->id}">{$class->name}</a></li>
					{/if}
				{/if}
			</ul>
		</td>
	</tr>
</table>

<fieldset class="peek">
	<legend>{'dao.classifier_example.expression'|devblocks_translate|capitalize} <small>(highlight to tag)</small></legend>

	<input type="hidden" name="expression" value="{$model->expression}">
	
	<div class="cerb-expression-editor">
		<div class="expression" contenteditable="true" autofocus="autofocus" spellcheck="false" style="border:1px solid rgb(150,150,150);padding:2px;min-height:1.2em;line-height:1.2em;width:100%;">
			{$expression = preg_replace('#\\{\{(.*?)\:(.*?)\}\}#', '<span class="\1">\2</span>', $model->expression)}
			{$expression nofilter}
		</div>
		
		<ul class="expression-toolbar" style="margin-top:5px;display:none;">
			<li data-tag="">
				<span style="color:black;font-weight:bold;">- remove selected tags -</span>
			</li>
			{foreach from=$entities item=entity key=k}
			<li data-tag="{$k}" class="expression">
				<span class="{$k}">{$entity.label}</span>
				<div style="margin-left:20px;color:black;">{$entity.description}</div>
			</li>
			{/foreach}
		</ul>
	</div>
</fieldset>

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
		Are you sure you want to permanently delete this classifier example?
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
	var $frm = $('#{$frm_id}');
	var $popup = genericAjaxPopupFind($frm);
	var $layer = $popup.attr('data-layer');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.example'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		var $chooser_classifier = $frm.find('button.chooser-abstract[data-field-name="classifier_id"]');
		var $chooser_class = $frm.find('button.chooser-abstract[data-field-name="class_id"]');
		var $expression_field = $frm.find('input:hidden[name=expression]');
		var $expression_editor = $frm.find('div.cerb-expression-editor');
		var $expression = $expression_editor.find('div.expression');
		var $menu = $expression_editor.find('ul.expression-toolbar');
		
		var serializeExpression = function(e) {
			var $clone = $expression.clone();
			
			$clone.find('span').each(function(i, node) {
				var $node = $(node);
				var text = $node.text();
				var tag = $node.attr('class');
				{literal}
				$(document.createTextNode('{{' + tag + ':' + text + '}}')).insertAfter($node);
				{/literal}
				$node.remove();
			});
			
			$expression_field.val($.trim($clone.html()));
			$clone.remove();
		}
		
		// Buttons
		$popup.find('button.submit').click({ before: serializeExpression }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		rangy.init();
		var highlighter = rangy.createHighlighter();
		
		// Content editable
		
		{foreach from=$entities item=entity key=k}
		highlighter.addClassApplier(rangy.createClassApplier("{$k}"));
		{/foreach}
		
		$menu
			.menu()
			.on('click', function(e) {
				var $target = $(e.target);
				
				if($target.is('span,div'))
					$target = $target.closest('li');
				
				if(!$target.is('li'))
					return;
				
				e.stopPropagation();
				
				var tag = $target.attr('data-tag');
				
				if(0 == tag.length) {
					//var sel = rangy.getSelection();
					//var range = sel.getRange();
					highlighter.unhighlightSelection();
				} else {
					highlighter.highlightSelection(tag);
				}
				
				var sel = rangy.getSelection();
				sel.removeAllRanges();
				$menu.hide();
			})
			;
		
		$expression
			.on('keypress keyup keydown', function(e) {
				e.stopPropagation();
			})
			.on('keyup mouseup', function(e) {
				e.stopPropagation();
				var range = rangy.createRangyRange();
				var sel = rangy.getSelection();
				
				if(sel.toString().length > 0) {
					$menu.show();
				} else {
					$menu.hide();
				}
			})
			.on('paste', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var pasted = (e.originalEvent || e || window).clipboardData;
				var text = pasted.getData('Text');
				window.document.execCommand('insertText', false, text);
			})
			.find('span').each(function() {
				var $span = $(this);
				var tag = $span.attr('class');
				var range = rangy.createRangyRange();
				var sel = rangy.getSelection();
				range.selectNode($span.get(0));
				sel.setSingleRange(range);
				highlighter.highlightSelection(tag);
				sel.removeAllRanges();
			})
		;
		
		$expression.focus();
		
		// Triggers
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		$popup.find('.chooser-abstract').cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
				// When the classifier changes, default the class chooser filter
				if($(e.target).attr('data-field-name') == 'classifier_id') {
					var $bubble = $chooser_classifier.siblings('ul.chooser-container').find('> li:first input:hidden');
					
					if($bubble.length > 0) {
						var classifier_id = $bubble.val();
						$chooser_class.attr('data-query', 'classifier.id:' + classifier_id);
						$chooser_class.attr('data-autocomplete', 'classifier.id:' + classifier_id);
					}
				}
			})
			;
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
