{$peek_context = CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
	<input type="hidden" name="c" value="profiles">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="automation_event_listener">
	<input type="hidden" name="action" value="savePeekJson">
	<input type="hidden" name="view_id" value="{$view_id}">
	{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
	<input type="hidden" name="do_delete" value="0">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	{include file="devblocks:cerberusweb.core::records/types/workflow/managed_callout.tpl" workflow=$workflow workflow_url=$workflow_url noun="event listener"}

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
				<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
			</div>

			<div class="cerb-ui-form--row">
				{* Event — the interaction chooser (button + ul.bubbles), kept verbatim *}
				<div class="cerb-ui-form--field cerb-u-flex-2">
					<label class="cerb-ui-form--label">{'common.event'|devblocks_translate|capitalize}</label>
					<div>
						<button type="button" data-cerb-event-chooser data-interaction-uri="ai.cerb.chooser.automationEvent" data-interaction-params=""><span class="cerb-icons cerb-icon-search"></span></button>
						<ul class="chooser-container bubbles" style="display:inline-block;">
							{if $model->event_name}
								<li>
									{$model->event_name}
									<input type="hidden" name="event_name" value="{$model->event_name}">
									<span class="cerb-icons cerb-icon-circle-remove"></span>
								</li>
							{/if}
						</ul>
					</div>
				</div>

				<div class="cerb-ui-form--field cerb-u-flex-1">
					<label class="cerb-ui-form--label">{'common.priority'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-sort-asc" title="0-255, ascending"></span></label>
					<div><input type="number" name="priority" min="0" max="255" value="{$model->priority|default:100}" style="width:5em;"></div>
				</div>

				<div class="cerb-ui-form--field cerb-u-flex-1">
					<label class="cerb-ui-form--label">{'common.enabled'|devblocks_translate|capitalize}</label>
					<div>
						<input type="hidden" name="is_disabled" id="isDisabled_{$form_id}" value="{$model->is_disabled|default:0}">
						<label class="cerb-ui-toggle">
							<input type="checkbox" id="statusEnabled_{$form_id}" {if !$model->is_disabled}checked="checked"{/if}>
							<span class="cerb-ui-toggle--slider"></span>
						</label>
					</div>
				</div>
			</div>

			{if !empty($custom_fields)}
				{* bulk/form.tpl with tbody=true emits <tbody> rows → needs a <table> wrapper *}
				<table cellspacing="0" cellpadding="2" border="0" width="98%">
					{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
				</table>
			{/if}
		</div>
	</div>

	{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Automations: (KATA)</div>
		</div>
		<div data-cerb-event-listener-toolbar>
			{* Static editor actions; the per-event interaction items are spliced in at the front (before the divider) on init/refresh *}
			<ul class="cerb-ui-toolbar" id="event_toolbar_{$form_id}">
				<li data-static></li>
				{if $model->id}
					<li data-static data-icon="history" data-key="changesets" title="{'common.change_history'|devblocks_translate|capitalize}"></li>
				{/if}
				<li data-static data-icon="book-open" data-toggle data-key="placeholders" title="{'common.placeholders'|devblocks_translate|capitalize}"></li>
				<li data-static data-icon="lab" data-toggle data-key="tester" title="{'common.test'|devblocks_translate|capitalize}"></li>
				<li data-static data-icon="circle-question-mark" data-key="help" title="{'common.help'|devblocks_translate|capitalize}"></li>
			</ul>

			{* Initial per-event interaction items (their own ul); the JS moves these <li>s into the strip above *}
			<div data-cerb-toolbar-dynamic-source hidden>{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}</div>
		</div>

		<textarea id="event_kata_editor_{$form_id}" name="event_kata" data-editor-lines="30" spellcheck="false">{$model->event_kata}</textarea>

		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_inputs}
	</div>

	{if !empty($model->id)}
		{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="event listener"}
	{/if}

	<div class="buttons" style="margin-top:10px;">
		{if $model->id}
			<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			<button type="button" class="save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
			{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="cerb-icons cerb-icon-trash cerb-u-anim-shake-hover"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		{else}
			<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
		{/if}
	</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		let $frm = $('#{$form_id}');
		let $popup = genericAjaxPopupFind($frm);

		Devblocks.formDisableSubmit($frm);

		$popup.one('popup_open', function() {
			$popup.dialog('option','title',"{'Automation Event Listener'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
			$popup.find('[autofocus]:first').focus();
			$popup.css('overflow', 'inherit');

			// Buttons

			// Flush the editor's full document (incl. any folded rows) into the textarea before the form serializes
			let syncKataEditor = function() {
				$popup.find('[name=event_kata]').val(editor.getValue());
			};

			$popup.find('button.save').click({ before: syncKataEditor }, Devblocks.callbackPeekEditSave);
			$popup.find('button.save-continue').click({ mode: 'continue', before: syncKataEditor }, Devblocks.callbackPeekEditSave);
			$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

			// Inline delete confirm (reveals the cerb-ui-panel--alert, hides the button row); the actual
			// delete stays on button.delete above.
			if(window.CerbUI && CerbUI.Form)
				CerbUI.Form.ConfirmDelete($popup[0]);

			$popup.find('a.cerb-peek-trigger').cerbPeekTrigger();

			// Status toggle (checked = enabled = is_disabled:0); the hidden field carries the POST value
			let $statusToggle = $popup.find('#statusEnabled_{$form_id}').closest('.cerb-ui-toggle');
			if(window.CerbUI && CerbUI.Toggle) {
				new CerbUI.Toggle($statusToggle[0], {
					onChange: function(checked) { $popup.find('#isDisabled_{$form_id}').val(checked ? 0 : 1); }
				});
			}

			// Editor

			let autocomplete_suggestions = CerbUI.editorCore.autocompleteSchemas.kataAutomationEvent;

			{if $trigger_ext && $trigger_ext->id}
			autocomplete_suggestions['automation:uri:']['params']['automation'] = {
				'triggers': ['{$trigger_ext->id}']
			};
			{/if}

			let $editor = $popup.find('#event_kata_editor_{$form_id}');

			let editor = new CerbUI.KataEditor($editor[0], {
				onAutocomplete: CerbUI.KataEditor.kataFieldSource(autocomplete_suggestions)
			});

			{if $model->id}
			let openChangesets = function() {
				let formData = new FormData();
				formData.set('c', 'internal');
				formData.set('a', 'invoke');
				formData.set('module', 'records');
				formData.set('action', 'showChangesetsPopup');
				formData.set('record_type', 'automation_event_listener');
				formData.set('record_id', '{$model->id}');
				formData.set('record_key', 'automations_kata');

				let $editor_policy_differ_popup = genericAjaxPopup('editorDiff{$form_id}', formData, null, null, '80%');

				$editor_policy_differ_popup.one('cerb-diff-viewer-ready', function(e) {
					e.stopPropagation();

					if(!e.hasOwnProperty('viewer'))
						return;

					e.viewer.setCurrent(editor.getValue());

					e.viewer.onRestore(function(content) {
						editor.setValue(content);
						editor.clearSelection();
					});
				});
			};
			{/if}

			// Toolbar

			let $toolbar_wrapper = $popup.find('[data-cerb-event-listener-toolbar]');
			let toolbar_ul = $toolbar_wrapper.find('#event_toolbar_{$form_id}')[0];

			// Move the server-rendered per-event interaction <li>s into the front of the strip (before the
			// first static item). Passing null just clears them (e.g. when the event is removed).
			let spliceToolbarDynamic = function(source_ul) {
				toolbar_ul.querySelectorAll(':scope > li:not([data-static])').forEach(li => li.remove());

				if(source_ul) {
					let anchor = toolbar_ul.querySelector(':scope > li[data-static]');
					Array.from(source_ul.children)
						.filter(node => node.tagName === 'LI')
						.forEach(li => toolbar_ul.insertBefore(li, anchor))
					;
				}
			};

			spliceToolbarDynamic($toolbar_wrapper.find('[data-cerb-toolbar-dynamic-source] > ul')[0]);

			let toolbar = new CerbUI.Toolbar(toolbar_ul, {
				caller: {
					name: 'cerb.toolbar.editor',
					params: {
						toolbar: 'cerb.toolbar.recordEditor.automationEventListener',
						selected_text: ''
					}
				},
				start: function(formData) {
					let pos = editor.getCursorPosition();
					let token_path = editor.getTokenPath().join('');

					let event_id = 'cerb.trigger.' + $frm.find('input[name=event_name]').val();

					formData.set('caller[params][selected_text]', editor.getSelectedText());
					formData.set('caller[params][token_path]', token_path);
					formData.set('caller[params][cursor_row]', pos.row);
					formData.set('caller[params][cursor_column]', pos.column);
					formData.set('caller[params][trigger]', event_id);
					formData.set('caller[params][value]', editor.getValue());
				},
				done: function(e) {
					e.stopPropagation();

					let $target = e.trigger;

					if(!$target.is('[data-interaction-uri]'))
						return;

					if (e.eventData.exit === 'error') {

					} else if(e.eventData.exit === 'return') {
						Devblocks.interactionWorkerPostActions(e.eventData, editor);
					}
				},
				reset: function(e) {
					e.stopPropagation();
				},
				onSelect: function(item) {
					switch(item.key) {
						case 'placeholders':
							$popup.find('[data-cerb-event-placeholders]').stop(true, true)[item.pressed ? 'fadeIn' : 'fadeOut']();
							break;
						case 'tester':
							$popup.find('[data-cerb-event-tester]').stop(true, true)[item.pressed ? 'fadeIn' : 'fadeOut']();
							break;
						{if $model->id}
						case 'changesets':
							openChangesets();
							break;
						{/if}
						case 'help':
							window.open('https://cerb.ai/docs/automations/#events', '_blank');
							break;
					}
				}
			});

			// Tester panel ("Test": placeholders KataEditor + Run + results) — shared impl in editor-core.
			CerbUI.editorCore.attachEventHandlerTester($popup, editor);

			// Event chooser

			let $event_chooser = $popup.find('[data-cerb-event-chooser]');

			// Remove current automation event
			$event_chooser.siblings('.chooser-container').on('click', function(e) {
				e.stopPropagation();

				let $target = $(e.target);

				if(!$target.is('.cerb-icon-circle-remove'))
					return;

				$target.closest('li').remove();

				// Clear the per-event interaction items from the toolbar
				spliceToolbarDynamic(null);
				toolbar.refresh();

				// Reset autocompletions (the editor's source reads this map live)
				delete autocomplete_suggestions['automation:uri:']['params']['automation'];

				// Reset placeholders
				$popup.find('[data-cerb-event-placeholders]').empty();
			});

			$event_chooser.cerbBotTrigger({
				caller: {
					name: 'cerb.toolbar.editor.automationEventListener.event',
					params: {
					}
				},
				width: '75%',
				start: function(formData) {
				},
				done: function(e) {
					if('object' !== typeof e || !e.hasOwnProperty('eventData'))
						return;

					let $target = e.trigger;

					if(!$target.is('[data-cerb-event-chooser]'))
						return;

					if (e.eventData.exit === 'error') {

					} else if(e.eventData.exit === 'return') {
						Devblocks.interactionWorkerPostActions(e.eventData);
					}

					if(!e.eventData.return || !e.eventData.return.event)
						return;

					let $container = $event_chooser.siblings('ul.chooser-container');

					let $hidden = $('<input/>')
						.attr('type', 'hidden')
						.attr('name', 'event_name')
						.val(e.eventData.return.event.name)
					;

					let $remove = $('<span class="cerb-icons cerb-icon-circle-remove"></span>');

					let $li = $('<li/>')
						.text(e.eventData.return.event.name)
						.append($hidden)
						.append($remove)
					;

					$container.empty().append($li);

					// Events
					let event_id = e.eventData.return.event.name;

					if(!event_id)
						return;

					// Update config for trigger
					let formData = new FormData();
					formData.set('c', 'profiles');
					formData.set('a', 'invoke');
					formData.set('module', 'automation_event');
					formData.set('action', 'editorChangeEventJson');
					formData.set('event_id', event_id);

					genericAjaxPost(formData, null, null, function(json) {
						// Update the per-event interaction items by event
						if('object' == typeof json && json.hasOwnProperty('toolbar_html')) {
							let tmp = document.createElement('div');
							tmp.innerHTML = json.toolbar_html;
							spliceToolbarDynamic(tmp.querySelector('ul'));
							toolbar.refresh();
						}

						// Update autocompletion by event (the editor's source reads this map live)
						autocomplete_suggestions['automation:uri:']['params']['automation'] = {
							'triggers': ['cerb.trigger.' + event_id]
						};

						// Update placeholders by event
						if('object' == typeof json && json.hasOwnProperty('placeholders_html'))
							$popup.find('[data-cerb-event-placeholders]').html(json.placeholders_html);
					});
				}
			});
		});
	});
</script>