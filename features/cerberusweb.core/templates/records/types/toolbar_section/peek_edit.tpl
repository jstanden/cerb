{$peek_context = CerberusContexts::CONTEXT_TOOLBAR_SECTION}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="toolbar_section">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
    <input type="hidden" name="do_delete" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    {include file="devblocks:cerberusweb.core::records/types/workflow/managed_callout.tpl" workflow=$workflow workflow_url=$workflow_url noun="section"}

    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-form">
            <div class="cerb-ui-form--field">
                <label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
                <input type="text" name="name" value="{$model->name}" autofocus="autofocus">
            </div>

            <div class="cerb-ui-form--row">
                {* Toolbar — the interaction chooser (button + ul.bubbles), kept verbatim *}
                <div class="cerb-ui-form--field cerb-u-flex-2">
                    <label class="cerb-ui-form--label">{'common.toolbar'|devblocks_translate|capitalize}</label>
                    <div>
                        <button type="button" data-cerb-toolbar-chooser data-interaction-uri="ai.cerb.chooser.toolbar" data-interaction-params=""><span class="cerb-icons cerb-icon-search"></span></button>
                        <ul class="chooser-container bubbles" style="display:inline-block;">
                            {if $model->toolbar_name}
                                <li>
                                    {$model->toolbar_name}
                                    <input type="hidden" name="toolbar_name" value="{$model->toolbar_name}">
                                    <span class="cerb-icons cerb-icon-circle-remove"></span>
                                </li>
                            {/if}
                        </ul>
                    </div>
                </div>

                <div class="cerb-ui-form--field cerb-u-flex-1">
                    <label class="cerb-ui-form--label">{'common.priority'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-sort-asc" title="0=first, 255=last"></span></label>
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

    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-header cerb-ui-header--tight">
            <div class="cerb-ui-header--title-sm">Toolbar <small class="cerb-u-text-muted cerb-u-fw-400">(KATA)</small></div>
        </div>
        {$toolbar_dict = DevblocksDictionaryDelegate::instance([
        'caller_name' => 'cerb.toolbar.editor',

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
"}

        {$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

        {* The editor toolbar is the KataEditor's integrated strip below; these hidden <ul>s are its host sections
           (the Insert builder menu + the Suggest / Change history / Help / Test items), merged in via toolbar.sections. *}
        <div data-cerb-interaction-toolbar hidden>{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}</div>
        <ul class="cerb-ui-toolbar" data-cerb-toolbar-builder-toolbar hidden>
            <li data-value="suggest" data-icon="autocomplete" title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl/⌘+Space)"></li>
            {if $model->id}
                <li data-value="changesets" data-icon="history" title="{'common.change_history'|devblocks_translate|capitalize}"></li>
            {/if}
            <li data-value="help" data-toggle data-key="help" data-icon="circle-question-mark" title="{'common.help'|devblocks_translate|capitalize}"></li>
            <li data-value="tester" data-toggle data-key="tester" data-icon="lab" title="{'common.test'|devblocks_translate|capitalize}"></li>
        </ul>

        <textarea name="toolbar_kata" data-editor-lines="20" spellcheck="false">{$model->toolbar_kata}</textarea>

        {$toolbar_ext = $model->getExtension()}
        {include file="devblocks:cerberusweb.core::toolbars/editor_toolbar.tpl" toolbar_ext=$toolbar_ext}
    </div>

    {if !empty($model->id)}
        {include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="toolbar section"}
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

        $popup.one('popup_open', function() {
            $popup.dialog('option','title',"{'Toolbar Section'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.find('[autofocus]:first').focus();
            $popup.css('overflow', 'inherit');

            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
            if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

            // Status toggle (checked = enabled = is_disabled:0); the hidden field carries the POST value
            let $statusToggle = $popup.find('#statusEnabled_{$form_id}').closest('.cerb-ui-toggle');
            if(window.CerbUI && CerbUI.Toggle) {
                new CerbUI.Toggle($statusToggle[0], {
                    onChange: function(checked) { $popup.find('#isDisabled_{$form_id}').val(checked ? 0 : 1); }
                });
            }

            // Workflow link (when this section is workflow-managed)
            $popup.find('a.cerb-peek-trigger').cerbPeekTrigger();

            // Editor — KataEditor with its integrated toolbar: the Insert builder menu (an interaction) + the
            // Suggest / Change history / Help / Test items merge in as host sections. The Insert menu fires via
            // toolbarOpts (caller); the rest route through onAction. The Help/Test panels + the tester editor live
            // in editor_toolbar.tpl and are wired by the toolbar-builder glue below (formerly the shared plugin).

            let autocomplete_suggestions = {if $autocomplete_json}{$autocomplete_json nofilter}{else}[]{/if};

            {if $model->id}
            // Open the read-only changeset diff popup; its "Restore this version" button writes a historical version back into this editor.
            var openChangesets = function() {
                let formData = new FormData();
                formData.set('c', 'internal');
                formData.set('a', 'invoke');
                formData.set('module', 'records');
                formData.set('action', 'showChangesetsPopup');
                formData.set('record_type', 'toolbar_section');
                formData.set('record_id', '{$model->id}');
                formData.set('record_key', 'toolbar_kata');

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

            var $toolbar = $popup.find('textarea[name=toolbar_kata]');

            var editor = new CerbUI.KataEditor($toolbar[0], {
                onAutocomplete: CerbUI.KataEditor.kataFieldSource(autocomplete_suggestions),
                toolbar: {
                    sections: [
                        $popup.find('[data-cerb-interaction-toolbar] ul.cerb-ui-toolbar')[0],
                        $popup.find('[data-cerb-toolbar-builder-toolbar]')[0]
                    ].filter(Boolean),
                    toolbarOpts: {
                        caller: { name: 'cerb.toolbar.editor', params: { toolbar: 'cerb.toolbar.recordEditor.toolbarSection', selected_text: '' } },
                        start: function(formData) {
                            let pos = editor.getCursorPosition();
                            formData.set('caller[params][selected_text]', editor.getSelectedText());
                            formData.set('caller[params][token_path]', editor.getTokenPath().join(''));
                            formData.set('caller[params][cursor_row]', pos.row);
                            formData.set('caller[params][cursor_column]', pos.column);
                            formData.set('caller[params][toolbar]', '{if $toolbar_ext}{$toolbar_ext->id}{/if}');
                            formData.set('caller[params][value]', editor.getValue());
                        },
                        done: function(e) {
                            e.stopPropagation();
                            if(!e.trigger.is('.cerb-bot-trigger'))
                                return;
                            if(e.eventData.exit === 'return')
                                Devblocks.interactionWorkerPostActions(e.eventData, editor);
                        }
                    },
                    onAction: function(value, ed, item) {
                        if(value === 'suggest') { ed.openAutocomplete(); return true; }
                        {if $model->id}
                        if(value === 'changesets') { openChangesets(); return true; }
                        {/if}
                        if(value === 'help')   { $popup.find('[data-cerb-toolbar-help]').toggle(!!(item && item.pressed)); return true; }
                        if(value === 'tester') { $popup.find('[data-cerb-toolbar-tester]').toggle(!!(item && item.pressed)); return true; }
                        return false;
                    }
                }
            });

            // Toolbar-builder glue (formerly cerbCodeEditorToolbarHandler): the panels live in
            // editor_toolbar.tpl. The Suggest/Help/Test toggles are driven by the editor toolbar's
            // onAction above; what remains here is the toolbar-type → help/autocomplete refresh and the
            // Test panel's placeholders editor + Run button.
            let $panel_help = $popup.find('[data-cerb-toolbar-help]');
            let $panel_tester = $popup.find('[data-cerb-toolbar-tester]');

            // A chosen toolbar changed → refresh the help panel + live-swap the KATA autocomplete schema.
            $toolbar.on('cerb-toolbar--change-type', function(e) {
                e.stopPropagation();

                $panel_help.html('');

                let toolbar_name = e.hasOwnProperty('toolbar_name') ? e.toolbar_name : '';

                if('string' !== typeof toolbar_name || 0 === toolbar_name.length)
                    return;

                let formData = new FormData();
                formData.set('c', 'profiles');
                formData.set('a', 'invoke');
                formData.set('module', 'toolbar');
                formData.set('action', 'editorChangeToolbar');
                formData.set('toolbar_name', toolbar_name);

                genericAjaxPost(formData, '', '', function(json) {
                    if(!json || 'object' !== typeof json)
                        return;

                    if(json.hasOwnProperty('help'))
                        $panel_help.html(json.help);

                    if(editor && editor.opts && window.CerbUI && CerbUI.KataEditor) {
                        let suggestions = json.hasOwnProperty('autocompletions') ? json.autocompletions : CerbUI.editorCore.autocompleteSchemas.kataToolbar;
                        editor.opts.onAutocomplete = CerbUI.KataEditor.kataFieldSource(suggestions);
                    }
                });
            });

            // Test panel: a placeholders KataEditor + Run button that renders the toolbar for real.
            let tester_kata_el = $panel_tester.find('textarea[name="tester[placeholders]"]')[0];
            let editor_placeholders = (tester_kata_el && window.CerbUI && CerbUI.KataEditor) ? new CerbUI.KataEditor(tester_kata_el) : null;

            $panel_tester.find('.cerb-code-editor-toolbar-button--run').on('click', function() {
                let formData = new FormData();
                formData.set('c', 'profiles');
                formData.set('a', 'invoke');
                formData.set('module', 'toolbar');
                formData.set('action', 'tester');
                formData.set('toolbar_kata', editor.getValue());
                formData.set('placeholders_kata', editor_placeholders ? editor_placeholders.getValue() : '');

                let $results = $panel_tester.find('[data-cerb-toolbar-tester-results]').empty();

                genericAjaxPost(formData, null, null, function(json) {
                    if('object' !== typeof json)
                        return;

                    if(json.hasOwnProperty('error') && json.error) {
                        $results.append($('<div/>').addClass('cerb-ui-panel cerb-ui-panel--alert').text(json.error));
                        return;
                    }

                    $results.html($(json.html));
                });
            });

            // Event chooser

            let $toolbar_chooser = $popup.find('[data-cerb-toolbar-chooser]');

            $toolbar_chooser.siblings('.chooser-container').on('click', function(e) {
                e.stopPropagation();

                let $target = $(e.target);

                if(!$target.is('.cerb-icon-circle-remove'))
                    return;

                $target.closest('li').remove();

                $toolbar.trigger($.Event('cerb-toolbar--help-disabled'));

                // Clear the editor's KATA autocomplete (no toolbar selected → no schema).
                editor.opts.onAutocomplete = CerbUI.KataEditor.kataFieldSource([]);
            });

            $toolbar_chooser.cerbBotTrigger({
                caller: {
                    name: 'cerb.toolbar.editor.toolbarSection.toolbar',
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

                    if(!$target.is('[data-cerb-toolbar-chooser]'))
                        return;

                    if (e.eventData.exit === 'error') {

                    } else if(e.eventData.exit === 'return') {
                        Devblocks.interactionWorkerPostActions(e.eventData);
                    }

                    if(!e.eventData.return || !e.eventData.return.toolbar)
                        return;

                    let $container = $toolbar_chooser.siblings('ul.chooser-container');

                    let $hidden = $('<input/>')
                        .attr('type', 'hidden')
                        .attr('name', 'toolbar_name')
                        .val(e.eventData.return.toolbar.name)
                    ;

                    let $remove = $('<span class="cerb-icons cerb-icon-circle-remove"></span>');

                    let $li = $('<li/>')
                        .text(e.eventData.return.toolbar.name)
                        .append($hidden)
                        .append($remove)
                    ;

                    $container.empty().append($li);

                    $toolbar.trigger($.Event('cerb-toolbar--change-type', { 'toolbar_name':  e.eventData.return.toolbar.name }));
                }
            });
        });
    });
</script>
