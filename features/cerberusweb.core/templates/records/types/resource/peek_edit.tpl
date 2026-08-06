{$peek_context = CerberusContexts::CONTEXT_RESOURCE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="resource">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
    <input type="hidden" name="do_delete" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-form">
            <div class="cerb-ui-form--field">
                <label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
                <input type="text" name="name" value="{$model->name}" spellcheck="false" autofocus="autofocus">
            </div>

            <div class="cerb-ui-form--field">
                <label class="cerb-ui-form--label">{'common.description'|devblocks_translate|capitalize}</label>
                <input type="text" name="description" value="{$model->description}">
            </div>

            <div class="cerb-ui-form--field">
                <label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
                <select name="extension_id">
                    <option value=""></option>
                    {foreach from=$resource_extensions item=resource_extension}
                        <option value="{$resource_extension->id}" {if $model->extension_id==$resource_extension->id}selected="selected"{/if}>{$resource_extension->name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>

    {if !empty($custom_fields)}
    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-form">
            {include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
        </div>
    </div>
    {/if}

    <div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-resource-config>
        <div>
            <input type="hidden" name="is_dynamic" id="isDynamic_{$form_id}" value="{if $model->is_dynamic}1{else}0{/if}">
            <div class="cerb-ui-switcher" data-cerb-input="isDynamic_{$form_id}">
                <button type="button" data-value="0"{if !$model->is_dynamic} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-file"></span> {'common.file'|devblocks_translate|capitalize}</button>
                <button type="button" data-value="1"{if $model->is_dynamic} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-zap"></span> {'common.automation'|devblocks_translate|capitalize}</button>
            </div>
        </div>

        <div style="margin-top:10px;">
            <div data-cerb-file-static style="display:{if !$model->is_dynamic}block{else}none{/if};">
                <div class="cerb-ui-form">
                    <div class="cerb-ui-form--field">
                        <label class="cerb-ui-form--label">{'common.upload'|devblocks_translate|capitalize}</label>
                        <div class="cerb-ui-file-upload" data-cerb-resource-file></div>
                        {if !empty($model->id) && !$model->is_dynamic && $model->storage_size}<div class="cerb-ui-form--help">{'common.current'|devblocks_translate|capitalize}: {$model->storage_size|devblocks_prettybytes} — {'common.upload'|devblocks_translate|lower} a new file to replace it</div>{/if}
                    </div>
                </div>
            </div>

            <div data-cerb-file-dynamic style="display:{if $model->is_dynamic}block{else}none{/if};">
                <div data-cerb-event-resource-get>
                    <div class="cerb-ui-header cerb-ui-header--tight">
                        <div class="cerb-ui-header--title-sm">Event: Get resource (KATA)</div>
                    </div>
                    {$toolbar_dict = DevblocksDictionaryDelegate::instance([
                        'caller_name' => 'cerb.toolbar.eventHandlers.editor'
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

                    <textarea name="automation_kata" data-editor-lines="15" spellcheck="false">{$model->automation_kata}</textarea>

                    {if $trigger_ext}
                        {include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
                    {/if}
                </div>
            </div>
        </div>
    </div>

    {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

    {if !empty($model->id)}
        {include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="resource"}
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
            $popup.dialog('option','title',"{'Resource'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.css('overflow', 'inherit');

            var $file_mode_static = $popup.find('[data-cerb-file-static]');
            var $file_mode_dynamic = $popup.find('[data-cerb-file-dynamic]');

            // Source mode: File (static upload) vs Automation (dynamic KATA) — Switcher bound to hidden is_dynamic
            var isDynamicEl = document.getElementById('isDynamic_{$form_id}');
            if(window.CerbUI && CerbUI.Switcher)
                new CerbUI.Switcher($popup.find('.cerb-ui-switcher[data-cerb-input=isDynamic_{$form_id}]')[0], {
                    value: isDynamicEl.value,
                    onSelect: function(value) {
                        isDynamicEl.value = value;
                        if('1' === value) {
                            $file_mode_static.hide();
                            $file_mode_dynamic.fadeIn();
                        } else {
                            $file_mode_dynamic.hide();
                            $file_mode_static.fadeIn();
                        }
                    }
                });

            // Type — native <select> keeps the POST value; enhance with type-to-filter
            if(window.CerbUI && CerbUI.SelectMenu)
                $popup.find('form select[name=extension_id]').each(function() { new CerbUI.SelectMenu(this); });

            // File upload (static resources) — uploads to an anonymous, ephemeral automation resource and
            // posts its token as `file_token`; the server streams that resource's content into resource
            // storage (see profiles/resource.php). No attachment is created, so there's nothing to clean up.
            if(window.CerbUI && CerbUI.FileUpload)
                $popup.find('[data-cerb-resource-file]').each(function() { new CerbUI.FileUpload(this, { name: 'file_token', emptyIcon: 'file', asResource: true }); });

            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
            if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

            // Editor — KataEditor with integrated event-handler toolbar (Automation + Placeholders/Test toggles).
            var $fieldset_resource_get = $popup.find('[data-cerb-event-resource-get]');

            var automation_editor = new CerbUI.KataEditor($fieldset_resource_get.find('textarea[name=automation_kata]')[0], {
                toolbar: {
                    sections: [
                        $fieldset_resource_get.find('[data-cerb-interaction-toolbar] ul.cerb-ui-toolbar')[0],
                        $fieldset_resource_get.find('[data-cerb-event-toolbar]')[0]
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
                            formData.set('caller[params][trigger]', 'cerb.trigger.resource.get');
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
                        if(value === 'placeholders') { $fieldset_resource_get.find('[data-cerb-event-placeholders]').toggle(!!(item && item.pressed)); return true; }
                        if(value === 'tester')       { $fieldset_resource_get.find('[data-cerb-event-tester]').toggle(!!(item && item.pressed)); return true; }
                        return false;
                    }
                }
            });

            CerbUI.editorCore.attachEventHandlerTester($fieldset_resource_get, automation_editor);
        });
    });
</script>
