{$peek_context = CerberusContexts::CONTEXT_AUTOMATION_TIMER}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="automation_timer">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
    <input type="hidden" name="do_delete" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
        <tr>
            <td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
            <td width="99%">
                <input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
            </td>
        </tr>
        
        <tr>
            <td width="1%" nowrap="nowrap"><b>{'common.when'|devblocks_translate|capitalize}:</b></td>
            <td width="99%">
                <input type="text" name="next_run_at" value="{$model->next_run_at|devblocks_date}" style="width:98%;">
            </td>
        </tr>

        <tr>
            <td width="1%" nowrap="nowrap"><b>{'common.status'|devblocks_translate|capitalize}:</b></td>
            <td width="99%">
                <label><input type="radio" name="is_disabled" value="0" {if empty($model->is_disabled)}checked="checked"{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
                <label><input type="radio" name="is_disabled" value="1" {if !empty($model->is_disabled)}checked="checked"{/if}> {'common.disabled'|devblocks_translate|capitalize}</label>
            </td>
        </tr>

        <tr>
            <td width="1%" valign="top" nowrap="nowrap">
                <label>
                    <b>{'common.repeat'|devblocks_translate|capitalize}:</b>
                    <input type="checkbox" name="is_recurring" value="1" {if $model->is_recurring}checked="checked"{/if}>
                </label>
            </td>
            <td width="99%">
                <div data-cerb-timer-schedule style="display:{if $model->is_recurring}block{else}none{/if};">
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

                    <textarea name="recurring_patterns" data-editor-mode="ace/mode/ini">{$model->recurring_patterns}</textarea>
                    
                    <div style="margin-top:0.5em;">
                        <b>{'common.timezone'|devblocks_translate|capitalize}</b>: 
                        
                        <select name="recurring_timezone">
                            <option value=""></option>
                            {foreach from=$timezones item=timezone}
                                <option value="{$timezone}" {if $timezone == $model->recurring_timezone}selected="selected"{/if}>{$timezone}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
            </td>
        </tr>

        {if !empty($custom_fields)}
            {include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
        {/if}
    </table>

    {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

    <fieldset data-cerb-timer-events class="peek">
        <legend>Event: Automation Timer (KATA)</legend>
        <div class="cerb-code-editor-toolbar">
            {$toolbar_dict = DevblocksDictionaryDelegate::instance([
                'caller_name' => 'cerb.toolbar.eventHandlers.editor',
    
                'worker__context' => CerberusContexts::CONTEXT_WORKER,
                'worker_id' => $active_worker->id
            ])}

            {$toolbar_kata =
"menu/add:
  icon: circle-plus
  items:
    interaction/automation:
      label: Automation
      uri: ai.cerb.eventHandler.automation
      inputs:
        trigger: cerb.trigger.automation.timer
"}

            {$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

            {DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

            <div class="cerb-code-editor-toolbar-divider"></div>

            {include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_buttons.tpl"}
        </div>

        <textarea name="automations_kata" data-editor-mode="ace/mode/cerb_kata">{$model->automations_kata}</textarea>

        {$trigger_ext = Extension_AutomationTrigger::get(AutomationTrigger_AutomationTimer::ID, true)}
        {if $trigger_ext}
            {include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
        {/if}
    </fieldset>    

    {if !empty($model->id)}
        <fieldset style="display:none;" class="delete">
            <legend>{'common.delete'|devblocks_translate|capitalize}</legend>

            <div>
                Are you sure you want to permanently delete this automation timer?
            </div>

            <button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
            <button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
        </fieldset>
    {/if}

    <div class="buttons" style="margin-top:10px;">
        {if $model->id}
            <button type="button" class="save"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
            <button type="button" class="save-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
            {if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
        {else}
            <button type="button" class="save"><span class="glyphicons glyphicons-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
            {*<button type="button" class="create-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.create_and_continue'|devblocks_translate|capitalize}</button>*}
        {/if}
    </div>
</form>

<script type="text/javascript">
    $(function() {
        var $frm = $('#{$form_id}');
        var $popup = genericAjaxPopupFind($frm);

        $popup.one('popup_open', function(event,ui) {
            $popup.dialog('option','title',"{'Automation Timer'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.css('overflow', 'inherit');

            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            //$popup.find('button.create-continue').click({ mode: 'create_continue' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

            // Close confirmation

            $popup.on('dialogbeforeclose', function(e, ui) {
                var keycode = e.keyCode || e.which;
                if(27 === keycode)
                    return confirm('{'warning.core.editor.close'|devblocks_translate}');
            });
            
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
            var $schedule_editor = $popup.find('textarea[name=recurring_patterns]')
                .cerbCodeEditor()
                .nextAll('pre.ace_editor')
            ;
            
            var schedule_editor = ace.edit($schedule_editor.attr('id'));
            
            var $automation_editor = $popup.find('textarea[name=automations_kata]')
                .cerbCodeEditor()
                .cerbCodeEditorAutocompleteKata({
                    autocomplete_suggestions: cerbAutocompleteSuggestions.kataAutomationEvent
                })
                .nextAll('pre.ace_editor')
            ;

            var automation_editor = ace.edit($automation_editor.attr('id'));

            // Toolbars

            var $toolbar = $popup.find('[data-cerb-timer-events] .cerb-code-editor-toolbar').cerbToolbar({
                caller: {
                    name: 'cerb.toolbar.eventHandlers.editor',
                    params: {
                        trigger: 'cerb.trigger.automation.timer',
                        selected_text: ''
                    }
                },
                start: function(formData) {
                    formData.set('caller[params][selected_text]', automation_editor.getSelectedText())
                },
                done: function(e) {
                    e.stopPropagation();

                    var $target = e.trigger;

                    if(!$target.is('.cerb-bot-trigger'))
                        return;

                    if(!e.eventData || !e.eventData.exit)
                        return;

                    if (e.eventData.exit === 'error') {
                        // [TODO] Show error

                    } else if(e.eventData.exit === 'return' && e.eventData.return.snippet) {
                        automation_editor.insertSnippet(e.eventData.return.snippet);
                    }
                }
            });

            $toolbar.cerbCodeEditorToolbarEventHandler({
                editor: automation_editor
            });

            $popup.find('[data-cerb-timer-schedule] .cerb-code-editor-toolbar').cerbToolbar({
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

                    if(!e.eventData || !e.eventData.exit)
                        return;

                    if (e.eventData.exit === 'error') {
                        // [TODO] Show error

                    } else if(e.eventData.exit === 'return' && e.eventData.return.snippet) {
                        schedule_editor.insertSnippet(e.eventData.return.snippet);
                    }
                }
            });

            // Helpers

            $popup.find('input[name=next_run_at]')
                .cerbDateInputHelper()
            ;
        });
    });
</script>
