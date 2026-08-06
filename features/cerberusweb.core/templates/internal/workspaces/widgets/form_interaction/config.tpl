{$fi_uid = uniqid()}
<div class="cerb-u-mt-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Enable these form interactions: <span class="cerb-ui-form--hint">KATA</span></div>
		</div>

		{* Toolbar-builder insert menu — a hidden section folded into the KataEditor's integrated strip *}
		<div data-cerb-toolbar-builder hidden>
			{$toolbar_dict = DevblocksDictionaryDelegate::instance([
				'caller_name' => 'cerb.toolbar.editor',

				'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
				'widget_id' => $widget->id,

				'worker__context' => CerberusContexts::CONTEXT_WORKER,
				'worker_id' => $active_worker->id
			])}

			{$toolbar_kata =
"menu/insert:
  icon: circle-plus
  items:
    interaction/interaction:
      label: Interaction
      uri: ai.cerb.toolbarBuilder.interaction
    interaction/menu:
      label: Menu
      uri: ai.cerb.toolbarBuilder.menu
"
			}

			{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

			{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}
		</div>

		{* Editor actions — preview + help, routed through the KataEditor toolbar's onAction *}
		<ul class="cerb-ui-toolbar" data-cerb-editor-actions hidden>
			<li data-value="preview" data-icon="play" title="{'common.preview'|devblocks_translate|capitalize}"></li>
			<li data-value="help" data-icon="circle-question-mark" title="{'common.help'|devblocks_translate|capitalize}"></li>
		</ul>

		<textarea name="params[interactions_kata]" data-editor-lines="15" spellcheck="false">{$widget->params.interactions_kata}</textarea>
		<div class="cerb-code-editor-preview-output"></div>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Start interactions:</div>
		</div>
		<div>
			<input type="hidden" name="params[is_popup]" id="isPopup{$fi_uid}" value="{if $widget->params.is_popup}1{else}0{/if}">
			<div class="cerb-ui-switcher" data-cerb-input="isPopup{$fi_uid}">
				<button type="button" data-value="0" {if !$widget->params.is_popup}class="cerb-ui-switcher--active"{/if}>In the widget</button>
				<button type="button" data-value="1" {if $widget->params.is_popup}class="cerb-ui-switcher--active"{/if}>As a popup</button>
			</div>
		</div>
	</div>
</div>

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" id="{$script_uid}">
$(function() {
	var $script = $('#{$script_uid}');
	var $config = $script.prev('div');
	var $form = $config.closest('form');
	var $placeholder_output = $config.find('.cerb-code-editor-preview-output');

	// Preview the configured interactions into the output area below the editor
	var runPreview = function(ed) {
		$placeholder_output.html('').append(Devblocks.getSpinner());

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'workspace_widget');
		formData.set('action', 'invokeConfig');
		formData.set('config_action', 'previewInteractions');
		formData.set('interactions_kata', ed.getValue());

		var $hidden = $form.find('input[name=id]');

		if(0 === $hidden.length) {
			var $select = $form.find('select[name=extension_id]');

			if(0 === $select.length)
				return;

			formData.set('id', $select.val());
			formData.set('record_type', $form.find('input[name=record_type]').val());

		} else {
			formData.set('id', $hidden.val());
		}

		genericAjaxPost(formData, null, null, function (html) {
			$placeholder_output.html(html);
		});
	};

	var doneFunc = function(e) {
		e.stopPropagation();

		var $target = e.trigger;

		if(!$target.is('.cerb-bot-trigger'))
			return;

		if (e.eventData.exit === 'error') {

		} else if(e.eventData.exit === 'return') {
			Devblocks.interactionWorkerPostActions(e.eventData, editor);
		}
	};

	var resetFunc = function(e) {
		e.stopPropagation();
	};

	var errorFunc = function(e) {
		e.stopPropagation();
	};

	// KataEditor with its integrated toolbar: the toolbar-builder insert menu + editor actions (preview/help).
	var editor = new CerbUI.KataEditor($config.find('textarea[name="params[interactions_kata]"]')[0], {
		onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataToolbar),
		toolbar: {
			sections: [
				$config.find('[data-cerb-toolbar-builder] ul.cerb-ui-toolbar')[0],
				$config.find('[data-cerb-editor-actions]')[0]
			],
			toolbarOpts: {
				caller: {
					name: 'cerb.toolbar.editor',
					params: {
						toolbar: 'cerb.toolbar.workspaceWidget.interactions',
						selected_text: ''
					}
				},
				start: function(formData) {
					formData.set('caller[params][selected_text]', editor.getSelectedText())
				},
				done: doneFunc,
				reset: resetFunc,
				error: errorFunc
			},
			onAction: function(value, ed) {
				if('preview' === value) { runPreview(ed); return true; }
				if('help' === value) { window.open('https://cerb.ai/docs/automations/triggers/interaction.worker/callers/cerb.toolbar.workspaceWidget.interactions/', '_blank', 'noopener'); return true; }
				return false;
			}
		}
	});

	// Start-interactions switcher (hidden input carries the POST value)
	if(window.CerbUI && CerbUI.Switcher) {
		var isPopupEl = $config.find('.cerb-ui-switcher[data-cerb-input="isPopup{$fi_uid}"]')[0];
		var $isPopup = $config.find('#isPopup{$fi_uid}');
		if(isPopupEl)
			new CerbUI.Switcher(isPopupEl, { value: $isPopup.val(), onSelect: function(value) { $isPopup.val(value); } });
	}
});
</script>
