{$peek_context = CerberusContexts::CONTEXT_AUTOMATION_TIMER}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="automation_timer">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
    <input type="hidden" name="do_delete" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-form">
            <div class="cerb-ui-form--row">
                <div class="cerb-ui-form--field">
                    <label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
                    <input type="text" name="name" value="{$model->name}" autofocus="autofocus">
                </div>

                <div class="cerb-ui-form--field">
                    <label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
                    <div>
                        <input type="hidden" name="is_disabled" id="isDisabled_{$form_id}" value="{if !empty($model->is_disabled)}1{else}0{/if}">
                        <div class="cerb-ui-switcher" data-cerb-input="isDisabled_{$form_id}">
                            <button type="button" data-value="0"{if empty($model->is_disabled)} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.enabled'|devblocks_translate|capitalize}</button>
                            <button type="button" data-value="1"{if !empty($model->is_disabled)} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-ban"></span> {'common.disabled'|devblocks_translate|capitalize}</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cerb-ui-form--field">
                <label class="cerb-ui-form--label">{'common.when'|devblocks_translate|capitalize}</label>
                <div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
                    <input type="text" name="next_run_at" value="{$model->next_run_at|devblocks_date}" style="flex:1 1 auto;min-width:0;">
                </div>
            </div>

            <div class="cerb-ui-form--field">
                <div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
                    <label class="cerb-ui-toggle">
                        <input type="checkbox" name="is_recurring" id="isRecurring_{$form_id}" value="1" {if $model->is_recurring}checked="checked"{/if}>
                        <span class="cerb-ui-toggle--slider"></span>
                    </label>
                    <label for="isRecurring_{$form_id}" class="cerb-ui-form--label" style="margin:0;">{'common.repeat'|devblocks_translate|capitalize}</label>
                </div>

                <div data-cerb-timer-schedule class="cerb-u-mt-2" style="display:{if $model->is_recurring}block{else}none{/if};">
                    <div class="cerb-code-editor-toolbar">
                        {$toolbar_dict = DevblocksDictionaryDelegate::instance([
                            'caller_name' => 'cerb.toolbar.editor.timer.schedule',
                            'worker__context' => CerberusContexts::CONTEXT_WORKER,
                            'worker_id' => $active_worker->id
                        ])}

                        {$toolbar_kata =
"interaction/schedule:
  icon: circle-plus
  tooltip: Add schedule
  uri: ai.cerb.timerEditor.schedule.add
"}

                        {$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

                        {DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

                        <div class="cerb-code-editor-toolbar-divider"></div>
                    </div>

                    <textarea name="recurring_patterns" data-editor-lines="6" spellcheck="false">{$model->recurring_patterns}</textarea>

                    <div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1" style="margin-top:0.5em;">
                        <b>{'common.timezone'|devblocks_translate|capitalize}</b>
                        <select name="recurring_timezone" data-cerb-timezone-selectmenu>
                            <option value="">({'common.default'|devblocks_translate|lower})</option>
                            {foreach from=$timezones item=timezone}
                                <option value="{$timezone}" {if $timezone == $model->recurring_timezone}selected="selected"{/if}>{$timezone}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
            </div>

            {if !empty($custom_fields)}
            {include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
            {/if}
        </div>
    </div>

    {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

    <div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-timer-events>
        <div class="cerb-ui-header cerb-ui-header--tight">
            <div class="cerb-ui-header--title-sm">Event: Automation Timer <span class="cerb-ui-form--hint">KATA</span></div>
        </div>
        {$toolbar_dict = DevblocksDictionaryDelegate::instance([
            'caller_name' => 'cerb.toolbar.eventHandlers.editor',

            'worker__context' => CerberusContexts::CONTEXT_WORKER,
            'worker_id' => $active_worker->id
        ])}

        {$toolbar_kata =
"interaction/automation:
  uri: ai.cerb.eventHandler.automation
  icon: circle-plus
  tooltip: Automation
"}

        {$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

        {* The editor toolbar is the KataEditor's integrated strip below; these hidden <ul>s are its host sections. *}
        <div data-cerb-interaction-toolbar hidden>{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}</div>
        {include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_toolbar.tpl"}

        <textarea name="automations_kata" data-editor-lines="15" spellcheck="false">{$model->automations_kata}</textarea>

        {if $trigger_ext}
            {include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
        {/if}
    </div>

    {if !empty($model->id)}
        {include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="automation timer"}
    {/if}

    <div class="buttons" style="margin-top:10px;">
        {if $model->id}
            <button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
            <button type="button" class="cerb-ui-button cerb-ui-button--subtle save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
            {if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
        {else}
            <button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
        {/if}
    </div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
    $(function() {
        let $frm = $('#{$form_id}');
        let $popup = genericAjaxPopupFind($frm);

        Devblocks.formDisableSubmit($frm);

        $popup.one('popup_open', function(event,ui) {
            $popup.dialog('option','title',"{'Automation Timer'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.css('overflow', 'inherit');

            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
            if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

            // Status switcher
            if(window.CerbUI && CerbUI.Switcher) {
                $popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
                    let input = document.getElementById(this.getAttribute('data-cerb-input'));
                    new CerbUI.Switcher(this, {
                        value: input ? input.value : null,
                        onSelect: function(value) { if(input) input.value = value; }
                    });
                });
            }

            // Timezone
            if(window.CerbUI && CerbUI.SelectMenu)
                $popup.find('select[data-cerb-timezone-selectmenu]').each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

            // Repeat toggle reveals the schedule editor
            $popup.find('input[name=is_recurring]').on('click', function(e) {
               e.stopPropagation();
               var $checkbox = $(this);
               var $textarea = $popup.find('textarea[name=recurring_patterns]');

               if($checkbox.is(':checked')) {
                   $textarea.closest('[data-cerb-timer-schedule]').show();
               } else {
                   $textarea.closest('[data-cerb-timer-schedule]').hide();
               }
            });

            // Editors
            // Schedule (cron patterns) — a plain ScriptingEditor (no autocomplete; no Twig in crontab content).
            var schedule_editor = new CerbUI.ScriptingEditor($popup.find('textarea[name=recurring_patterns]')[0]);

            // Automation Timer event — KataEditor with integrated event-handler toolbar (Automation + Placeholders/Test).
            var $events = $popup.find('[data-cerb-timer-events]');
            var automation_editor = new CerbUI.KataEditor($events.find('textarea[name=automations_kata]')[0], {
                onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataAutomationEvent),
                toolbar: {
                    sections: [
                        $events.find('[data-cerb-interaction-toolbar] ul.cerb-ui-toolbar')[0],
                        $events.find('[data-cerb-event-toolbar]')[0]
                    ],
                    toolbarOpts: {
                        caller: { name: 'cerb.toolbar.eventHandlers.editor', params: { selected_text: '' } },
                        width: '75%',
                        start: function(formData) {
                            var pos = automation_editor.getCursorPosition();
                            formData.set('caller[params][selected_text]', automation_editor.getSelectedText());
                            formData.set('caller[params][token_path]', automation_editor.getTokenPath().join(''));
                            formData.set('caller[params][cursor_row]', pos.row);
                            formData.set('caller[params][cursor_column]', pos.column);
                            formData.set('caller[params][trigger]', 'cerb.trigger.automation.timer');
                            formData.set('caller[params][value]', automation_editor.getValue());
                        },
                        done: function(e) {
                            e.stopPropagation();
                            if(!e.trigger.is('.cerb-bot-trigger'))
                                return;
                            if(e.eventData.exit === 'return')
                                Devblocks.interactionWorkerPostActions(e.eventData, automation_editor);
                        }
                    },
                    onAction: function(value, ed, item) {
                        if(value === 'placeholders') { $events.find('[data-cerb-event-placeholders]').toggle(!!(item && item.pressed)); return true; }
                        if(value === 'tester')       { $events.find('[data-cerb-event-tester]').toggle(!!(item && item.pressed)); return true; }
                        return false;
                    }
                }
            });

            // Tester panel ("Test": placeholders KataEditor + Run + results) — shared impl in editor-core.
            CerbUI.editorCore.attachEventHandlerTester($events, automation_editor);

            let timerschedule_toolbar_ul = $popup.find('[data-cerb-timer-schedule] .cerb-code-editor-toolbar ul.cerb-ui-toolbar')[0];
            if(timerschedule_toolbar_ul && window.CerbUI && CerbUI.Toolbar)
            new CerbUI.Toolbar(timerschedule_toolbar_ul, {
                caller: {
                    name: 'cerb.toolbar.editor.timer.schedule',
                    params: {
                        selected_text: ''
                    }
                },
                start: function(formData) {
                    formData.set('caller[params][selected_text]', schedule_editor.getSelectedText())
                },
                done: function(e) {
                    e.stopPropagation();

                    var $target = e.trigger;

                    if(!$target.is('.cerb-bot-trigger'))
                        return;

                    if (e.eventData.exit === 'error') {

                    } else if(e.eventData.exit === 'return') {
                        Devblocks.interactionWorkerPostActions(e.eventData, schedule_editor);
                    }
                }
            });

            // Helpers

            $popup.find('input[name=next_run_at]')
                .each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this); })
            ;
        });
    });
</script>
