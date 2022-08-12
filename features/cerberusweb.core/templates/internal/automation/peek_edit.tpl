{$peek_context = CerberusContexts::CONTEXT_AUTOMATION}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
{if $model}{$extension = $model->getTriggerExtension()}{else}{$extension = null}{/if}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="editor{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="automation">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="is_simulator" value="1">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap">
			<b>{'common.name'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" autofocus="autofocus" style="width:100%;" spellcheck="false">
		</td>
	</tr>

	<tr>
		<td width="1%" nowrap="nowrap">
			<b>{'common.description'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			<input type="text" name="description" value="{$model->description}" style="width:100%;">
		</td>
	</tr>

	<tr>
		<td width="1%" valign="top" nowrap="nowrap">
			<b>{'common.trigger'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			<button type="button" data-cerb-trigger-chooser data-interaction-uri="ai.cerb.cardEditor.automation.triggerChooser" data-interaction-params=""><span class="glyphicons glyphicons-search"></span></button>
			<ul class="chooser-container bubbles">
				{if $extension}
				<li>
					{$extension->manifest->id}
					<input type="hidden" name="extension_id" value="{$extension->id}">
					<span class="glyphicons glyphicons-circle-remove"></span>
				</li>
				{/if}
			</ul>

			<div data-cerb-extension-params>
				{if $extension}
					{$extension->renderConfig($model)}
				{/if}
			</div>
		</td>
	</tr>

	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

<div data-cerb-automation-editor-script>
	<div data-cerb-toolbar class="cerb-code-editor-toolbar">
		{if is_a($extension, 'Extension_AutomationTrigger')}
			{$toolbar_dict = DevblocksDictionaryDelegate::instance([
				'caller_name' => 'cerb.toolbar.editor.automation.script',
				
				'worker__context' => CerberusContexts::CONTEXT_WORKER,
				'worker_id' => $active_worker->id
			])}
			{$toolbar = $extension->getEditorToolbar()}
			{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar, $toolbar_dict)}
			{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}
		{/if}
	</div>
	<textarea name="automation_script" data-editor-mode="ace/mode/cerb_kata" data-editor-lines="25">{$model->script}</textarea>
</div>

{$tabs_uid = uniqid('automationTabs')}
<div id="{$tabs_uid}" style="margin-top:10px;" data-cerb-automation-editor-tabs>
	<ul>
		<li data-cerb-tab="run"><a href="#{$tabs_uid}Run">{'common.run'|devblocks_translate|capitalize}</a></li>
		<li data-cerb-tab="policy"><a href="#{$tabs_uid}Policy">{'common.policy'|devblocks_translate|capitalize}</a></li>
		<li data-cerb-tab="log"><a href="#{$tabs_uid}Log">{'common.log'|devblocks_translate|capitalize}</a></li>
		{*<li data-cerb-tab="versions"><a href="#{$tabs_uid}Versions">{'common.versions'|devblocks_translate|capitalize}</a></li>*}
		<li data-cerb-tab="visualization"><a href="#{$tabs_uid}Visualization">Visualization</a></li>
	</ul>

	<div id="{$tabs_uid}Run">
		<div style="display:flex;">
			<fieldset class="peek black no-legend" style="flex:1 1 50%;padding:5px;" data-cerb-automation-editor-state-start>
				<legend>
					{'common.input'|devblocks_translate|capitalize}: <small>(YAML)</small>
				</legend>

				<div class="cerb-code-editor-toolbar">
					<button type="button" title="Simulate" class="cerb-code-editor-toolbar-button cerb-editor-toolbar-button--mode" data-mode="simulator" style="text-decoration:underline;">Simulate</button>
					<div class="cerb-code-editor-toolbar-divider"></div>
					<button type="button" class="cerb-code-editor-toolbar-button cerb-code-editor-toolbar-button--run" title="{'common.run'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-play"></span></button>
					<button type="button" class="cerb-code-editor-toolbar-button cerb-code-editor-toolbar-button--interaction" data-interaction-uri="ai.cerb.automationBuilder.help" data-interaction-params="topic=input" title="{'common.help'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-circle-question-mark"></span></button>
				</div>

				<textarea name="start_state_yaml" data-editor-mode="ace/mode/yaml" rows="5" cols="45"></textarea>
			</fieldset>

			<fieldset class="peek black no-legend" style="flex:1 1 50%;padding:5px;" data-cerb-automation-editor-state-end>
				<legend>
					{'common.output'|devblocks_translate|capitalize}: <small>(YAML)</small>
				</legend>

				<div class="cerb-code-editor-toolbar">
					<button type="button" class="cerb-code-editor-toolbar-button cerb-code-editor-toolbar-button--step" title="Copy to input" style="display:none;"><span class="glyphicons glyphicons-left-arrow"></span></button>
					<button type="button" class="cerb-code-editor-toolbar-button cerb-code-editor-toolbar-button--interaction" data-interaction-uri="ai.cerb.automationBuilder.help" data-interaction-params="topic=output" title="{'common.help'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-circle-question-mark"></span></button>
				</div>

				<textarea name="end_state_yaml" data-editor-mode="ace/mode/yaml" rows="5" cols="45"></textarea>
			</fieldset>
		</div>
	</div>

	<div id="{$tabs_uid}Policy">
		<div class="cerb-code-editor-toolbar">
			<button type="button" class="cerb-code-editor-toolbar-button cerb-code-editor-toolbar-button--interaction" data-interaction-uri="ai.cerb.automationBuilder.help" data-interaction-params="topic=policy" title="{'common.help'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-circle-question-mark"></span></button>
		</div>

		<textarea name="automation_policy_kata" data-editor-mode="ace/mode/cerb_kata" data-editor-lines="25">{$model->policy_kata}</textarea>
	</div>

	{*
	<div id="{$tabs_uid}Versions">
	</div>
	*}

	<div id="{$tabs_uid}Log"></div>
	<div id="{$tabs_uid}Visualization"></div>
</div>

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>

	<div>
		Are you sure you want to permanently delete this automation?
	</div>

	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $model->id}<button type="button" class="save-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>{/if}
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#editor{$form_id}');
	var $popup = genericAjaxPopupFind($frm);

	$popup.one('popup_open', function() {
		$frm.find('[data-cerb-automation-editor-tabs]').tabs({
			beforeActivate: function(event, ui) {
				var formData;
				
				if(ui.newTab.attr('data-cerb-tab') === 'visualization') {
					Devblocks.getSpinner().appendTo(ui.newPanel.html(''));

					formData = new FormData();
					formData.set('c', 'profiles');
					formData.set('a', 'invoke');
					formData.set('module', 'automation');
					formData.set('action', 'editorVisualize');
					formData.set('script', editor_automation.getValue());

					genericAjaxPost(formData, null, null, function (html) {
						ui.newPanel.html(html);
					});
					
				} else if(ui.newTab.attr('data-cerb-tab') === 'log') {
					Devblocks.getSpinner().appendTo(ui.newPanel.html(''));

					formData = new FormData();
					formData.set('c', 'profiles');
					formData.set('a', 'invoke');
					formData.set('module', 'automation');
					formData.set('action', 'editorLog');
					formData.set('automation_name', $frm.find('input[name="name"]').val());

					genericAjaxPost(formData, null, null, function (html) {
						ui.newPanel.html(html);
					});
				}
			}
		});

		var $script = $frm.find('[data-cerb-automation-editor-script]');
		var $script_toolbar = $script.find('.cerb-code-editor-toolbar');
		var $automation_yaml = $script.find('textarea[name=automation_script]');

		var $state_start = $frm.find('[data-cerb-automation-editor-state-start]');
		var $state_yaml = $state_start.find('textarea[name=start_state_yaml]');
		var $state_start_toolbar = $state_start.find('.cerb-code-editor-toolbar');
		var $button_run = $state_start_toolbar.find('.cerb-code-editor-toolbar-button--run');
		var $toggle_mode = $state_start_toolbar.find('.cerb-editor-toolbar-button--mode');

		var $state_end = $frm.find('[data-cerb-automation-editor-state-end]');
		var $end_state_yaml = $state_end.find('textarea[name=end_state_yaml]');
		var $state_end_toolbar = $state_end.find('.cerb-code-editor-toolbar');
		var $button_step = $state_end_toolbar.find('.cerb-code-editor-toolbar-button--step');

		var $spinner = Devblocks.getSpinner().css('max-width', '16px');
		var highlight_marker = null;

		var $extension_params = $frm.find('[data-cerb-extension-params]');

		$popup.dialog('option', 'title', "{'common.automation'|devblocks_translate|capitalize}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Close confirmation

		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode === 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});

		// Help
		
		$popup.find('.cerb-code-editor-toolbar-button--interaction')
			.cerbBotTrigger()
		;
		
		// Trigger chooser

		var $trigger_chooser = $popup.find('[data-cerb-trigger-chooser]');

		$trigger_chooser.siblings('.chooser-container').on('click', function(e) {
			e.stopPropagation();

			var $target = $(e.target);

			if(!$target.is('.glyphicons-circle-remove'))
				return;

			$target.closest('li').remove();

			$extension_params.empty();
			$script_toolbar.empty();

			$editor_automation
				.cerbCodeEditorAutocompleteKata({
					autocomplete_suggestions: []
				})
			;
		});

		$trigger_chooser.cerbBotTrigger({
			caller: {
				name: 'cerb.toolbar.editor.automation.trigger',
				params: {
				}
			},
			width: '75%',
			start: function(formData) {
			},
			done: function(e) {
				if('object' !== typeof e || !e.hasOwnProperty('eventData') || !e.eventData.return.trigger)
					return;

				var $container = $trigger_chooser.siblings('ul.chooser-container');

				var $hidden = $('<input/>')
					.attr('type', 'hidden')
					.attr('name', 'extension_id')
					.val(e.eventData.return.trigger.id)
				;

				var $remove = $('<span class="glyphicons glyphicons-circle-remove"></span>');

				var $li = $('<li/>')
					.text(e.eventData.return.trigger.name)
					.append($hidden)
					.append($remove)
				;

				$container.empty().append($li);

				// Events

				var extension_id = e.eventData.return.trigger.id;

				$extension_params.empty().append(Devblocks.getSpinner());

				var formData;

				// Update config for trigger

				formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'automation');
				formData.set('action', 'getExtensionConfig');
				formData.set('extension_id', extension_id);

				genericAjaxPost(formData, $extension_params);

				// Update toolbar for editor

				formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'automation');
				formData.set('action', 'renderEditorToolbar');
				formData.set('trigger', extension_id);

				genericAjaxPost(formData, null, null, function(html) {
					$script_toolbar
						.html(html)
						.triggerHandler('cerb-toolbar--refreshed')
					;
				});

				// Update autocompletion for editor

				formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'automation');
				formData.set('action', 'getAutocompleteJson');
				formData.set('extension_id', extension_id);

				genericAjaxPost(formData, null, null, function(json) {
					$editor_automation
						.cerbCodeEditorAutocompleteKata({
							autocomplete_suggestions: json
						})
					;
				});
			}
		});

		// Extension select

		$popup.find('.chooser-abstract').cerbChooserTrigger();

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		var $editor_automation = $popup.find('textarea[name=automation_script]')
			.cerbCodeEditor()
			{if is_a($extension, 'Extension_AutomationTrigger')}
			{$autocomplete_json = $extension->getAutocompleteSuggestionsJson()}
			{if is_string($autocomplete_json)}
			.cerbCodeEditorAutocompleteKata({
				autocomplete_suggestions: {$autocomplete_json nofilter}
			})
			{/if}
			{/if}
			;

		var $editor_policy = $popup.find('textarea[name=automation_policy_kata]')
			.cerbCodeEditor()
			;

		$popup.find('textarea[name=start_state_yaml], textarea[name=end_state_yaml]')
			.cerbCodeEditor()
			;

		var editor_automation = ace.edit($automation_yaml.nextAll('pre.ace_editor').attr('id'));
		var editor_state_start = ace.edit($state_yaml.nextAll('pre.ace_editor').attr('id'));
		var editor_state_end = ace.edit($end_state_yaml.nextAll('pre.ace_editor').attr('id'));
		//var editor_policy = ace.edit($editor_policy.nextAll('pre.ace_editor').attr('id'));

		$editor_policy
			.cerbCodeEditorAutocompleteKata({
				autocomplete_suggestions: cerbAutocompleteSuggestions.kataAutomationPolicy
			})
		;
		
		{if $cursor}
		editor_automation.gotoLine({$cursor.row}, {$cursor.column});
		{/if}

		editor_automation.focus();

		editor_state_start.setOption('minLines', 15);
		editor_state_start.setOption('maxLines', 15);

		editor_state_end.setOption('minLines', 15);
		editor_state_end.setOption('maxLines', 15);

		$button_step
			.click(function() {
				Devblocks.clearAlerts();
				
				var formData = new FormData($frm[0]);
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'automation');
				formData.set('action', 'stepAutomationEditor');
				formData.set('output', editor_state_end.getValue());

				$spinner.insertAfter($button_step);
				$button_step.hide();

				genericAjaxPost(formData, null, null, function(yaml) {
					editor_state_start.setValue(yaml);
					editor_state_start.clearSelection();
					$spinner.detach();
				});

				editor_state_end.setValue('');
			})
		;

		$button_run
			.click(function() {
				Devblocks.clearAlerts();

				$spinner.insertAfter($button_run);
				$button_run.hide();
				$button_step.hide();

				if(null != highlight_marker) {
					editor_automation.session.removeMarker(highlight_marker.id);
					highlight_marker = null;
				}

				var formData = new FormData($frm[0]);
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'automation');
				formData.set('action', 'runAutomationEditor');

				genericAjaxPost(formData, null, null, function(json) {
					$spinner.detach();
					$button_run.fadeIn();

					if('object' != typeof json) {
						Devblocks.createAlertError("An unexpected error occurred.");
						return;
					}

					if(json.error) {
						Devblocks.createAlertError(json.error);
						return;
					}

					if(json.exit === 'await') {
						$button_step.fadeIn();
					}

					if(json.hasOwnProperty('exit_state')) {
						var state_path = json.exit_state;

						var row = Devblocks.cerbCodeEditor.getKataRowByPath(editor_automation, state_path);

						if(row) {
							highlight_marker = editor_automation.session.highlightLines(row,row);
							editor_automation.scrollToLine(row);
						}
					}

					editor_state_end.setValue(json.dict);
					editor_state_end.clearSelection();
				});
			})
		;

		$popup.on('cerb-automation-editor--goto', function(e) {
			if(null != highlight_marker) {
				editor_automation.session.removeMarker(highlight_marker.id);
				highlight_marker = null;
			}

			if(!e.hasOwnProperty('editor_line'))
				return;

			var row = e.editor_line;

			if(false !== row) {
				highlight_marker = editor_automation.session.highlightLines(row,row);
				editor_automation.scrollToLine(row);
			}
		});

		// Formatting

		$toggle_mode.on('click', function() {
			if('simulator' === $toggle_mode.attr('data-mode')) {
				$script_toolbar.triggerHandler($.Event('cerb-editor-toolbar-mode-set', { simulator: false }));
			} else {
				$script_toolbar.triggerHandler($.Event('cerb-editor-toolbar-mode-set', { simulator: true }));
			}
		});

		$script_toolbar.on('cerb-editor-toolbar-mode-set', function(e) {
			if(e.hasOwnProperty('simulator')) {
				if(e.simulator) {
					$frm.find('input:hidden[name=is_simulator]').val('1');
					$toggle_mode.attr('data-mode', 'simulator');
					$toggle_mode.text('Simulate');
				} else {
					$frm.find('input:hidden[name=is_simulator]').val('0');
					$toggle_mode.attr('data-mode', 'live');
					$toggle_mode.text('Execute');
				}
			}
		});

		var doneFunc = function(e) {
			e.stopPropagation();

			var $target = e.trigger;

			if(!$target.is('.cerb-bot-trigger'))
				return;

			//var done_params = new URLSearchParams($target.attr('data-interaction-done'));

			if(!e.eventData || !e.eventData.exit)
				return;

			if (e.eventData.exit === 'error') {
				// [TODO] Show error

			} else if(e.eventData.exit === 'return' && e.eventData.return.snippet) {
				editor_automation.insertSnippet(e.eventData.return.snippet);
			}
		};

		var resetFunc = function(e) {
		}

		var errorFunc = function(e) {
		};

		$script_toolbar.cerbToolbar({
			caller: {
				name: 'cerb.toolbar.editor.automation.script',
				params: {
					selected_text: ''
				}
			},
			width: '75%',
			start: function(formData) {
				var pos = editor_automation.getCursorPosition();
				var token_path = Devblocks.cerbCodeEditor.getKataTokenPath(pos, editor_automation).join('');
				var trigger = $frm.find('input:hidden[name=extension_id]').val();
				
				formData.set('caller[params][selected_text]', editor_automation.getSelectedText());
				formData.set('caller[params][token_path]', token_path);
				formData.set('caller[params][cursor_row]', pos.row);
				formData.set('caller[params][cursor_column]', pos.column);
				formData.set('caller[params][trigger]', trigger);
				formData.set('caller[params][value]', editor_automation.getValue());
			},
			done: doneFunc,
			reset: resetFunc,
			error: errorFunc,
		});
	});
});
</script>
