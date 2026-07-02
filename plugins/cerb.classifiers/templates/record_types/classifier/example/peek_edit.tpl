{$peek_context = CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE}
{$peek_context_id = $model->id}
<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerb.classifiers&f=css/expression-editor.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/rangy/rangy-core.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/rangy/rangy-classapplier.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/rangy/rangy-highlighter.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>

{$frm_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$frm_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="classifier_example">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$model->id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.classifier'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser" id="classifierChooser{$frm_id}" data-context="{CerberusContexts::CONTEXT_CLASSIFIER}" data-name="classifier_id">
					{if $model}
						{$classifier = $model->getClassifier()}
						{if $classifier}
							<li data-context-id="{$classifier->id}" data-label="{$classifier->name}"></li>
						{/if}
					{/if}
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.classifier.classification'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser" id="classChooser{$frm_id}" data-context="{CerberusContexts::CONTEXT_CLASSIFIER_CLASS}" data-name="class_id" data-query="{if $model->classifier_id}classifier.id:{$model->classifier_id}{/if}">
					{if $model}
						{$class = $model->getClass()}
						{if $class}
							<li data-context-id="{$class->id}" data-label="{$class->name}"></li>
						{/if}
					{/if}
				</div>
			</div>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'dao.classifier_example.expression'|devblocks_translate|capitalize} <small class="cerb-u-text-muted cerb-u-fw-400">(highlight to tag)</small></div>
	</div>

	<input type="hidden" name="expression" value="{$model->expression}">

	<div class="cerb-expression-editor">
		<div class="expression cerb-u-w-100" contenteditable="true" autofocus="autofocus" spellcheck="false" style="min-height:1.2em;line-height:1.2em;">
			{$model->expression|escape|devblocks_rangy_deserialize nofilter}
		</div>

		<ul class="expression-toolbar" style="margin-top:5px;display:none;">
			<li data-tag="">
				<span style="color:var(--cerb-color-text);font-weight:bold;">- remove selected tags -</span>
			</li>
			{foreach from=$entities item=entity key=k}
			<li data-tag="{$k}" class="expression">
				<span class="{$k}">{$entity.label}</span>
				<div style="margin-left:20px;color:var(--cerb-color-text);">{$entity.description}</div>
			</li>
			{/foreach}
		</ul>
	</div>
</div>

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
	</div>
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="classifier example"}
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$frm_id}');
	let $popup = genericAjaxPopupFind($frm);
	let $layer = $popup.attr('data-layer');

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.example'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
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
		$popup.find('button.save').click({ before: serializeExpression }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		rangy.init();
		var highlighter = rangy.createHighlighter();

		// Content editable

		{foreach from=$entities item=entity key=k}
		highlighter.addClassApplier(rangy.createClassApplier("{$k}"));
		{/foreach}

		// The entity-tag toolbar: a flat CerbUI.Menu that floats below the editor when text is selected.
		// We snapshot the selection when it's made because opening the floating menu collapses it.
		let exprSavedRanges = null;

		let exprMenu = (window.CerbUI && CerbUI.Menu) ? new CerbUI.Menu($menu[0], {
			panelClass: 'cerb-expression-menu',
			onRenderItem: function(li, src) {
				let tag = src.getAttribute('data-tag');
				let $label = $(li).find('.cerb-ui-menu--label');

				// Render entity rows as the same colored chip the editor uses (shared %cerb-expression-tag);
				// the "remove tags" row (no entity) stays plain bold text.
				if(tag)
					$label.addClass('cerb-expression-tag').addClass(tag);
				else
					$label.css('font-weight', 'bold');

				let desc = $(src).children('div').text();

				if(desc.length) {
					// Two-line cell: the colored entity label over its description
					li.style.height = 'auto';
					li.style.minHeight = '40px';
					li.style.whiteSpace = 'normal';

					let $col = $('<div/>').css('display', 'flex').css('flex-direction', 'column').css('gap', '2px');
					$label.before($col);
					$col.append($label);
					$('<div/>').css('color', 'var(--cerb-color-text)').css('opacity', '0.7').css('font-size', '0.85em').text(desc).appendTo($col);
				}
			},
			onSelect: function(li, src) {
				let tag = src.getAttribute('data-tag');

				if(null == tag)
					return;

				// Restore the selection the floating menu took focus from
				let sel = rangy.getSelection();
				if(exprSavedRanges) sel.setRanges(exprSavedRanges);

				if(0 == tag.length)
					highlighter.unhighlightSelection();
				else
					highlighter.highlightSelection(tag);

				sel.removeAllRanges();
				exprSavedRanges = null;
			}
		}) : null;

		$expression
			.on('keypress keyup keydown', function(e) {
				e.stopPropagation();
			})
			.on('keyup mouseup', function(e) {
				e.stopPropagation();
				var sel = rangy.getSelection();

				if(sel.toString().length > 0) {
					exprSavedRanges = sel.getAllRanges();
					if(exprMenu && !exprMenu.isOpen())
						exprMenu.open($expression[0]);
				} else {
					exprSavedRanges = null;
					if(exprMenu)
						exprMenu.close();
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

		// Coupled choosers: the class chooser is scoped to the picked classifier
		let classChooserEl = document.getElementById('classChooser{$frm_id}');
		let classChooser = (classChooserEl && window.CerbUI && CerbUI.RecordChooser) ? new CerbUI.RecordChooser(classChooserEl, {
			context: classChooserEl.getAttribute('data-context'),
			name: 'class_id',
			emptyIcon: 'tag',
			query: classChooserEl.getAttribute('data-query') || ''
		}) : null;

		let classifierChooserEl = document.getElementById('classifierChooser{$frm_id}');
		if(classifierChooserEl && window.CerbUI && CerbUI.RecordChooser)
			new CerbUI.RecordChooser(classifierChooserEl, {
				context: classifierChooserEl.getAttribute('data-context'),
				name: 'classifier_id',
				emptyIcon: 'brain',
				onSelect: function(item) {
					// Picking a classifier rescopes the class chooser and clears the stale class
					if(classChooser && item && item.id) {
						classChooser.setQuery('classifier.id:' + item.id);
						classChooser.clear(false);
					}
				}
			});

	});
});
</script>
