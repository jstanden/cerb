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

	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
			</td>
		</tr>

		<tr>
			<td width="1%" valign="top" nowrap="nowrap">
				<b>{'common.event'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%">
				<button type="button" data-cerb-event-chooser data-interaction-uri="ai.cerb.chooser.automationEvent" data-interaction-params=""><span class="glyphicons glyphicons-search"></span></button>
				<ul class="chooser-container bubbles">
					{if $model->event_name}
						<li>
							{$model->event_name}
							<input type="hidden" name="event_name" value="{$model->event_name}">
							<span class="glyphicons glyphicons-circle-remove"></span>
						</li>
					{/if}
				</ul>
			</td>
		</tr>

		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.priority'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<input type="text" name="priority" maxlength="3" size="3" value="{$model->priority|default:100}">
				<span>
					(0=first, 255=last)
				</span>
			</td>
		</tr>

		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.status'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<label>
					<input type="radio" name="is_disabled" value="0" {if !$model->is_disabled}checked="checked"{/if}>
					{'common.enabled'|devblocks_translate|capitalize}
				</label>
				<label>
					<input type="radio" name="is_disabled" value="1" {if $model->is_disabled}checked="checked"{/if}>
					{'common.disabled'|devblocks_translate|capitalize}
				</label>
			</td>
		</tr>

		{if !empty($custom_fields)}
			{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
		{/if}
	</table>

	{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

	<fieldset data-cerb-event-listener-toolbar class="peek">
		<legend>Automations: (KATA)</legend>
		<div class="cerb-code-editor-toolbar">
			<div data-cerb-toolbar-custom style="display:inline-block;">
				{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}
			</div>

			<div class="cerb-code-editor-toolbar-divider"></div>

			{if $model->id}
				<button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-changesets title="{'common.change_history'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-history"></span></button>
			{/if}

			{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_buttons.tpl"}

			<button type="button" style="float:right;" class="cerb-code-editor-toolbar-button cerb-editor-button-help"><a href="https://cerb.ai/docs/automations/#events" target="_blank"><span class="glyphicons glyphicons-circle-question-mark"></span></a></button>
		</div>

		<textarea name="event_kata" data-editor-mode="ace/mode/cerb_kata" data-editor-lines="30">{$model->event_kata}</textarea>

		{include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_inputs}
	</fieldset>

	{if !empty($model->id)}
		<fieldset style="display:none;" class="delete">
			<legend>{'common.delete'|devblocks_translate|capitalize}</legend>

			<div>
				Are you sure you want to permanently delete this event listener?
			</div>

			<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
			<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
		</fieldset>
	{/if}

	<div class="buttons" style="margin-top:10px;">
		{if $model->id}
			<button type="button" class="save"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			<button type="button" class="save-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
			{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		{else}
			<button type="button" class="save"><span class="glyphicons glyphicons-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
		{/if}
	</div>

</form>

<script type="text/javascript">
	$(function() {
		let $frm = $('#{$form_id}');
		let $popup = genericAjaxPopupFind($frm);

		Devblocks.formDisableSubmit($frm);

		$popup.one('popup_open', function() {
			$popup.dialog('option','title',"{'Automation Event Listener'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
			$popup.find('[autofocus]:first').focus();
			$popup.css('overflow', 'inherit');

			// Buttons

			$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
			$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
			$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

			// Editor

			let autocomplete_suggestions = cerbAutocompleteSuggestions.kataAutomationEvent;

			{if $trigger_ext && $trigger_ext->id}
			autocomplete_suggestions['automation:uri:']['params']['automation'] = {
				'triggers': ['{$trigger_ext->id}']
			};
			{/if}

			let $editor = $popup.find('[name=event_kata]')
				.cerbCodeEditor()
				.cerbCodeEditorAutocompleteKata({
					autocomplete_suggestions: autocomplete_suggestions
				})
				.next('pre.ace_editor')
			;

			let editor = ace.edit($editor.attr('id'));

			{if $model->id}
			$popup.find('[data-cerb-editor-button-changesets]').on('click', function(e) {
				e.stopPropagation();

				let formData = new FormData();
				formData.set('c', 'internal');
				formData.set('a', 'invoke');
				formData.set('module', 'records');
				formData.set('action', 'showChangesetsPopup');
				formData.set('record_type', 'automation_event_listener');
				formData.set('record_id', '{$model->id}');
				formData.set('record_key', 'automations_kata');

				let $editor_policy_differ_popup = genericAjaxPopup('editorDiff{$form_id}', formData, null, null, '80%');

				$editor_policy_differ_popup.one('cerb-diff-editor-ready', function(e) {
					e.stopPropagation();

					if(!e.hasOwnProperty('differ'))
						return;

					e.differ.editors.right.ace.setValue(editor.getValue());
					e.differ.editors.right.ace.clearSelection();

					e.differ.editors.right.ace.on('change', function() {
						editor.setValue(e.differ.editors.right.ace.getValue());
						editor.clearSelection();
					});
				});
			});
			{/if}

			// Toolbar

			let $toolbar = $popup.find('[data-cerb-event-listener-toolbar]').find('.cerb-code-editor-toolbar');

			$toolbar.cerbToolbar({
				caller: {
					name: 'cerb.toolbar.editor',
					params: {
						toolbar: 'cerb.toolbar.recordEditor.automationEventListener',
						selected_text: ''
					}
				},
				start: function(formData) {
					let pos = editor.getCursorPosition();
					let token_path = Devblocks.cerbCodeEditor.getKataTokenPath(pos, editor).join('');

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

					if(!$target.is('.cerb-bot-trigger'))
						return;

					if (e.eventData.exit === 'error') {

					} else if(e.eventData.exit === 'return') {
						Devblocks.interactionWorkerPostActions(e.eventData, editor);
					}
				},
				reset: function(e) {
					e.stopPropagation();
				}
			});

			$toolbar.cerbCodeEditorToolbarEventHandler({
				editor: editor
			});

			// Event chooser

			let $event_chooser = $popup.find('[data-cerb-event-chooser]');

			// Remove current automation event
			$event_chooser.siblings('.chooser-container').on('click', function(e) {
				e.stopPropagation();

				let $target = $(e.target);

				if(!$target.is('.glyphicons-circle-remove'))
					return;

				$target.closest('li').remove();

				// Clear the toolbar
				$toolbar.find('[data-cerb-toolbar-custom]').empty();

				// Reset autocompletions
				$editor
					.cerbCodeEditorAutocompleteKata({
						autocomplete_suggestions: cerbAutocompleteSuggestions.kataAutomationEvent
					})
				;

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

					let $remove = $('<span class="glyphicons glyphicons-circle-remove"></span>');

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
						// Update toolbar by event
						if('object' == typeof json && json.hasOwnProperty('toolbar_html')) {
							$toolbar
								.find('[data-cerb-toolbar-custom]')
								.html(json.toolbar_html)
								.closest('.cerb-code-editor-toolbar')
								.triggerHandler('cerb-toolbar--refreshed')
							;
						}

						// Update autocompletion by event
						let autocomplete_suggestions = cerbAutocompleteSuggestions.kataAutomationEvent;

						autocomplete_suggestions['automation:uri:']['params']['automation'] = {
							'triggers': ['cerb.trigger.' + event_id]
						};

						$editor
							.cerbCodeEditorAutocompleteKata({
								autocomplete_suggestions: autocomplete_suggestions
							})
						;

						// Update placeholders by event
						if('object' == typeof json && json.hasOwnProperty('placeholders_html'))
							$popup.find('[data-cerb-event-placeholders]').html(json.placeholders_html);
					});
				}
			});
		});
	});
</script>