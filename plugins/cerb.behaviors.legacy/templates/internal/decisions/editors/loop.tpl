<div class="cerb-tabs">
	{if !$id && $packages}
	<ul>
		<li><a href="#loop{$id}-library">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li><a href="#loop{$id}-build">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}

	{if !$id && $packages}
	<div id="loop{$id}-library" class="package-library">
		<form id="frmDecisionLoop{$id}Library" method="post">
		<input type="hidden" name="c" value="profiles">
		<input type="hidden" name="a" value="invoke">
		<input type="hidden" name="module" value="behavior">
		<input type="hidden" name="action" value="saveDecisionPopup">
		{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
		{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
		{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
		{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
		</form>
	</div>
	{/if}

	<div id="loop{$id}-build">
		<form id="frmDecisionLoop{$id}" class="cerb-ui-form">
			<input type="hidden" name="c" value="profiles">
			<input type="hidden" name="a" value="invoke">
			<input type="hidden" name="module" value="behavior">
			<input type="hidden" name="action" value="saveDecisionPopup">
			{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
			{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
			{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
			{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

			<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--note">
				<div class="cerb-ui-header">
					<div class="cerb-ui-callout">
						<span class="cerb-icons cerb-icon-circle-info cerb-ui-callout--icon"></span>
						<div>
							<div class="cerb-ui-header--title-sm">Repeat this branch for each object in a list</div>
							<div class="cerb-ui-header--subtitle">A <b>loop</b> branch will repeat its decisions and actions for each object in a list.</div>
						</div>
					</div>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.title'|devblocks_translate|capitalize}</label>
				<input type="text" name="title" value="{$model->title}" autofocus="autofocus" autocomplete="off" spellcheck="false">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
				<input type="hidden" name="status_id" value="{$model->status_id|default:0}">
				<div>
					<div class="cerb-ui-switcher cerb-behavior-status-switcher">
						<button type="button" data-value="0"{if !$model->status_id} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-play-button"></span> Live</button>
						<button type="button" data-value="2"{if 2 == $model->status_id} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-lab"></span> Simulator only</button>
						<button type="button" data-value="1"{if 1 == $model->status_id} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-ban"></span> Disabled</button>
					</div>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">For each object in this JSON array:</label>
				<textarea id="decisionLoopForeach{$id}" name="params[foreach_json]" data-editor-lines="12" spellcheck="false">{$model->params.foreach_json}</textarea>
			</div>

			<div id="divDecisionLoopToolbar{$id}" style="display:none;">
				<div class="tester"></div>

				<button type="button" class="cerb-ui-button cerb-ui-button--subtle cerb-popupmenu-trigger">Insert placeholder <span class="cerb-icons cerb-icon-chevron-down"></span></button>
				<button type="button" class="cerb-ui-button cerb-ui-button--subtle tester">{'common.test'|devblocks_translate|capitalize}</button>
				<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-button="help">Help</button>

				{$types = $values._types}
				{function tree level=0}
					{foreach from=$keys item=data key=idx}
						{$type = $types.{$data->key}}
						{if is_array($data->children) && !empty($data->children)}
							<li {if $data->key}data-token="{$data->key}{if $type == Model_CustomField::TYPE_DATE}|date{/if}" data-label="{$data->label}"{/if}>
								{if $data->key}
									<div style="font-weight:bold;">{$data->l|capitalize}</div>
								{else}
									<div>{$idx|capitalize}</div>
								{/if}
								<ul>
									{tree keys=$data->children level=$level+1}
								</ul>
							</li>
						{elseif $data->key}
							<li data-token="{$data->key}{if $type == Model_CustomField::TYPE_DATE}|date{/if}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
						{/if}
					{/foreach}
				{/function}

				<ul class="menu" style="width:150px;">
				{tree keys=$placeholders}
				</ul>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Set this object placeholder:</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
					{literal}{{{/literal}<input type="text" name="params[as_placeholder]" value="{$model->params.as_placeholder}" size="32" class="cerb-u-flex-1">{literal}}}{/literal}
				</div>
			</div>
		</form>

		{if isset($id)}
			{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="loop"}
		{/if}

		<div class="buttons">
			<button type="button" class="cerb-ui-button cerb-u-anim-group" data-cerb-button="save"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if isset($id)}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFetch('node_loop{$id}');

	Devblocks.formDisableSubmit($popup.find('form'));

	// Refresh every on-page instance of this behavior's tree (it can appear in multiple widgets/cards).
	const refreshTrees = function() {
		$('[data-behavior-tree-id="{$trigger_id}"]').each(function() {
			genericAjaxGet(this.id, 'c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}&tree_dom_id=' + encodeURIComponent(this.id));
		});
	};

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{if empty($id)}New {/if}Loop");
		$popup.css('overflow', 'inherit');

		let $frm_build = $popup.find('#frmDecisionLoop{$id}');
		let $frm_library = $popup.find('#frmDecisionLoop{$id}Library');

		if(window.CerbUI && CerbUI.Switcher) {
			$frm_build.find('.cerb-behavior-status-switcher').each(function() {
				const input = this.closest('.cerb-ui-form--field').querySelector('input[name=status_id]');
				new CerbUI.Switcher(this, { value: input ? input.value : null, onSelect: function(v) { if(input) input.value = v; } });
			});
		}

		$popup.find('[data-cerb-button=save]').on('click', function(e) {
			e.stopPropagation();

			genericAjaxPost($frm_build,null,null,function() {
				genericAjaxPopupDestroy('node_loop{$id}');
				refreshTrees();
			});
		});

		if(window.CerbUI && CerbUI.Form) {
			CerbUI.Form.ConfirmDelete($popup[0], { onConfirm: function() {
				var formData = new FormData($frm_build[0]);
				formData.set('action','saveDecisionDeletePopup');
				genericAjaxPost(formData,null,null,function() {
					genericAjaxPopupDestroy('node_loop{$id}');
					refreshTrees();
				});
			}});
		}

		// Package Library

		{if !$id && $packages}
			var $library_container = $popup.find('.cerb-tabs');
			$library_container.find('> ul').each(function() {
				if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this);
			});
			{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}

			$library_container.on('cerb-package-library-form-submit', function(e) {
				Devblocks.clearAlerts();

				genericAjaxPost($frm_library,null,null, function(json) {
					$library_container.triggerHandler('cerb-package-library-form-submit--done');

					if(json.error) {
						Devblocks.createAlertError(json.error);

					} else if (json.id && json.type) {
						genericAjaxPopupDestroy('node_loop{$id}');

						refreshTrees();
						genericAjaxPopup('node_' + json.type + json.id,'c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&id=' + encodeURIComponent(json.id),null,false,'50%');
					}
				});
			});
		{/if}

		// Placeholder toolbar

		var $toolbar = $('#divDecisionLoopToolbar{$id}');

		$toolbar.find('[data-cerb-button=help]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPopup('help', 'c=profiles&a=invoke&module=snippet&action=helpPopup', { my:'left top' , at:'left+20 top+20'}, false, '600');
		});

		// Each .placeholders field becomes a CerbUI.ScriptingEditor (input=single-line/wrap, textarea=multi-line)
		// via enhance(); the shared placeholder toolbar docks on the editor's focus.
		var enhancePlaceholders = function($scope) {
			if(!(window.CerbUI && CerbUI.ScriptingEditor)) return;
			$scope.find('textarea.placeholders, :text.placeholders').each(function() {
				var isInput = this.tagName === 'INPUT';
				this.classList.remove('placeholders');
				var ed = CerbUI.ScriptingEditor.enhance(this, {
					singleLine: isInput,
					minLines: isInput ? 1 : 3,
					maxLines: isInput ? 6 : 12
				});
				if(!ed) return;
				ed.textarea.addEventListener('focus', function() {
					$toolbar.find('div.tester').html('');
					$toolbar.find('ul.menu').hide();
					$toolbar.show().insertAfter(ed.el);
					$toolbar.data('src', ed);
					$toolbar.find('button.tester').show();
				});
			});
		};

		enhancePlaceholders($popup);

		// The foreach_json editor is its own pre-built CerbUI.ScriptingEditor; dock the toolbar to it on focus too.
		var loopEditor = new CerbUI.ScriptingEditor(document.getElementById('decisionLoopForeach{$id}'), { minLines: 6, maxLines: 20 });
		$('#decisionLoopForeach{$id}').on('focus', function(e) {
			$toolbar.find('div.tester').html('');
			$toolbar.find('ul.menu').hide();
			$toolbar.show().insertAfter('#decisionLoopForeach{$id}');
			$toolbar.data('src', loopEditor);
			$toolbar.find('button.tester').show();
		});

		// Placeholder menu

		var $placeholder_menu_trigger = $toolbar.find('button.cerb-popupmenu-trigger');
		var $placeholder_menu = $toolbar.find('ul.menu').hide();

		// Quick insert token menu

		var menu = new CerbUI.Menu($placeholder_menu[0], {
			clickTrigger: $placeholder_menu_trigger[0],
			selectableParents: true,
			filter: true,
			onSelect: function(li, src) {
				var token = src.getAttribute('data-token');
				var label = src.getAttribute('data-label');

				if(undefined == token || undefined == label)
					return;

				var insert = '{literal}{{{/literal}' + token + '{literal}}}{/literal}';
				var fieldSrc = $toolbar.data('src');

				if(fieldSrc instanceof CerbUI.ScriptingEditor) {
					fieldSrc.focus();
					fieldSrc.insertAtCursor(insert);
					return;
				}

				var $field = fieldSrc ? $(fieldSrc) : $toolbar.prev(':text, textarea');

				if(!$field || 0 == $field.length)
					return;

				if($field.is(':text, textarea'))
					$field.focus().insertAtCursor(insert);
			}
		});

		$toolbar.find('button.tester').click(function(e) {
			var divTester = $toolbar.find('div.tester').first();

			var formData = new FormData($frm_build[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'behavior');
			formData.set('action', 'testDecisionEventSnippets');
			formData.set('prefix', 'params');
			formData.set('field', 'foreach_json');

			genericAjaxPost(formData, divTester, null);
		});

		$placeholder_menu_trigger
			.bind('remove',
				function(e) {
					if(menu)
						menu.destroy();
					$placeholder_menu.remove();
				}
			)
		;
	});
});
</script>
