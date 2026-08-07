{$popup_id = uniqid('popup')}

<div class="cerb-ui-header">
    <div>
        <div data-cerb-workflow-name class="cerb-ui-header--title">{$model->name}</div>
        <div class="cerb-ui-header--subtitle">{$model->description}</div>
    </div>
</div>

<form id="{$popup_id}">
    <input type="hidden" name="id" value="{$model->id}">

    <div id="{$popup_id}Tabs" class="cerb-ui-tabs-slide">
        <ul style="display:none;">
            <li><a href="#{$popup_id}TabsTemplate">{'common.template'|devblocks_translate|capitalize}</a></li>
            <li><a href="#{$popup_id}TabsConfig">{'common.configuration'|devblocks_translate|capitalize}</a></li>
            <li><a href="#{$popup_id}TabsChanges">{'common.changes'|devblocks_translate|capitalize}</a></li>
        </ul>

        <div id="{$popup_id}TabsTemplate">
            <div>
                {* The editor toolbar is the KataEditor's integrated strip below; this hidden <ul> is its host section. *}
                <ul class="cerb-ui-toolbar" data-cerb-template-toolbar hidden>
                    <li data-value="suggest" data-icon="autocomplete" title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl/⌘+Space)"></li>
                    {if $model->id}
                        <li data-value="changesets" data-icon="history" title="{'common.change_history'|devblocks_translate|capitalize}"></li>
                        <li data-value="builder" data-icon="hammer" title="Workflow Builder"></li>
                    {/if}
                </ul>
                <textarea name="template[kata]" data-editor-lines="25" spellcheck="false">{$model->workflow_kata}</textarea>
            </div>

            <div style="margin-top:1em;">
                <fieldset data-cerb-fieldset-resources-import class="peek">
                    <legend>
                        <label><input type="checkbox"> Import Resources</label>
                    </legend>
                    <div style="display:none;padding:0.3em;">
                        <div>
                            You can optionally link new record keys in the template to existing record IDs. This imports a record to the workflow as-is rather than creating a new one.
                        </div>
                        <textarea name="template[import_resources]" data-editor-lines="10" spellcheck="false"></textarea>
                    </div>
                </fieldset>
            </div>

            <div style="margin-top:0.5em;">
                <button type="button" data-cerb-button-continue><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {{'common.continue'|devblocks_translate|capitalize}}</button>
            </div>
        </div>

        <div id="{$popup_id}TabsConfig">
            <div data-cerb-content></div>

            <div style="margin-top:0.5em;">
                <button type="button" data-cerb-button-back><span class="cerb-icons cerb-icon-circle-arrow-left"></span> {{'common.back'|devblocks_translate|capitalize}}</button>
                <button type="button" data-cerb-button-continue><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {{'common.continue'|devblocks_translate|capitalize}}</button>
            </div>
        </div>

        <div id="{$popup_id}TabsChanges">
            <div data-cerb-content></div>

            <div style="margin-top:0.5em;">
                <button type="button" data-cerb-button-back><span class="cerb-icons cerb-icon-circle-arrow-left"></span> {{'common.back'|devblocks_translate|capitalize}}</button>
                <button type="button" data-cerb-button-continue><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {{'common.continue'|devblocks_translate|capitalize}}</button>
            </div>
        </div>
    </div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    let $frm = $('#{$popup_id}');
    let $tabs = $('#{$popup_id}Tabs');
    let $popup = genericAjaxPopupFind($frm);

    Devblocks.formDisableSubmit($frm);

    $popup.one('popup_open', function() {
        $popup.dialog('option','title',"{'common.workflow'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

        // Tabs

        let cerbWizardTabs = null;
        $tabs.find('> ul').each(function() {
            if(window.CerbUI && CerbUI.Tabs) cerbWizardTabs = new CerbUI.Tabs(this);
        });

        let $tab_template = $('#{$popup_id}TabsTemplate');

        // Editors

        new CerbUI.KataEditor($popup.find('textarea[name="template[import_resources]"]')[0], {
            onAutocomplete: CerbUI.KataEditor.kataFieldSource({
                '': [
                    'records:'
                ],
                'records:': [
                    'record_type/record_key@int: 1234'
                ]
            })
        });

        {if $model->id}
        // Open the Workflow Builder popup w/ the current template persisted in the workflow.
        var openBuilder = function() {
            var formData = new FormData();
            formData.set('c', 'profiles');
            formData.set('a', 'invoke');
            formData.set('module', 'workflow');
            formData.set('action', 'showBuilderPopup');
            formData.set('id', '{$model->id}');
            formData.set('template_kata', editor_template.getValue());

            genericAjaxPopup('editorBuilder{$popup_id}', formData, null, null, '75%');
        };

        // Open the read-only changeset diff popup; its "Restore this version" button writes a historical version back into this editor.
        var openChangesets = function() {
            var formData = new FormData();
            formData.set('c', 'internal');
            formData.set('a', 'invoke');
            formData.set('module', 'records');
            formData.set('action', 'showChangesetsPopup');
            formData.set('record_type', 'workflow');
            formData.set('record_id', '{$model->id}');
            formData.set('record_key', 'template');

            var $editor_template_differ_popup = genericAjaxPopup('editorDiff{$popup_id}', formData, null, null, '80%');

            $editor_template_differ_popup.one('cerb-diff-viewer-ready', function(e) {
                e.stopPropagation();

                if(!e.hasOwnProperty('viewer'))
                    return;

                e.viewer.setCurrent(editor_template.getValue());

                e.viewer.onRestore(function(content) {
                    editor_template.setValue(content);
                    editor_template.clearSelection();
                });
            });
        };
        {/if}

        let editor_template = new CerbUI.KataEditor($popup.find('textarea[name="template[kata]"]')[0], {
            onAutocomplete: CerbUI.KataEditor.kataFieldSource({$autocomplete_suggestions|json_encode nofilter}),
            toolbar: {
                sections: [ $popup.find('[data-cerb-template-toolbar]')[0] ],
                onAction: function(value, ed) {
                    if(value === 'suggest') { ed.openAutocomplete(); return true; }
                    {if $model->id}
                    if(value === 'changesets') { openChangesets(); return true; }
                    if(value === 'builder') { openBuilder(); return true; }
                    {/if}
                    return false;
                }
            }
        });

        $tab_template.find('[data-cerb-fieldset-resources-import] input[type=checkbox]').on('change', function(e) {
            e.stopPropagation();

            let $this = $(this);
            let $container = $this.closest('fieldset').find('> div');

            if($this.is(':checked')) {
                $container.fadeIn();
            } else {
                $container.hide();
            }
        });

        $tab_template.find('[data-cerb-button-continue]').on('click', function(e) {
            e.stopPropagation();

            let formData = new FormData($frm[0]);
            formData.set('c', 'profiles');
            formData.set('a', 'invoke');
            formData.set('module', 'workflow');
            formData.set('action', 'saveTemplateJson');

            Devblocks.clearAlerts();

            genericAjaxPost(formData, null, null, function(json) {
                if('object' == typeof json && json.hasOwnProperty('html')) {
                    // Update the workflow name
                    if(json.hasOwnProperty('workflow_name')) {
                        $popup.find('div[data-cerb-workflow-name]').text(json.workflow_name);
                    }

                    $tab_config.find('[data-cerb-content]').html(json.html);
                    if(cerbWizardTabs) cerbWizardTabs.select(1);
                } else {
                    if('object' == typeof json && json.hasOwnProperty('error')) {
                        Devblocks.createAlertError(json.error);
                    } else {
                        Devblocks.createAlertError('An unexpected error occurred.');
                    }
                }
            });
        });

        let $tab_config = $('#{$popup_id}TabsConfig');

        $tab_config.find('[data-cerb-button-back]').on('click', function(e) {
            e.stopPropagation();
            if(cerbWizardTabs) cerbWizardTabs.select(0);
        });

        $tab_config.find('[data-cerb-button-continue]').on('click', function(e) {
            e.stopPropagation();

            // [TODO] Validate config values + required
            let formData = new FormData($frm[0]);
            formData.set('c', 'profiles');
            formData.set('a', 'invoke');
            formData.set('module', 'workflow');
            formData.set('action', 'saveConfigJson');

            Devblocks.clearAlerts();

            genericAjaxPost(formData, null, null, function(json) {
                if('object' == typeof json && json.hasOwnProperty('html')) {
                    // Inject while the tab is still hidden, then reveal — the DiffViewer's onFirstReveal hook
                    // recomputes its geometry (line heights / connectors) once the panes are actually visible.
                    $tab_changes.find('[data-cerb-content]').html(json.html);
                    if(cerbWizardTabs) cerbWizardTabs.select(2);
                } else {
                    if('object' == typeof json && json.hasOwnProperty('error')) {
                        Devblocks.createAlertError(json.error);
                    } else {
                        Devblocks.createAlertError('An unexpected error occurred.');
                    }
                }
            });
        });

        let $tab_changes = $('#{$popup_id}TabsChanges');

        $tab_changes.find('[data-cerb-button-back]').on('click', function(e) {
            e.stopPropagation();
            if(cerbWizardTabs) cerbWizardTabs.select(1);
        });

        $tab_changes.find('[data-cerb-button-continue]').on('click', function(e) {
            e.stopPropagation();

            // [TODO] Validate config values + required
            let formData = new FormData($frm[0]);
            formData.set('c', 'profiles');
            formData.set('a', 'invoke');
            formData.set('module', 'workflow');
            formData.set('action', 'saveChangesJson');

            Devblocks.clearAlerts();

            genericAjaxPost(formData, null, null, function(json) {
                if('object' == typeof json && json.hasOwnProperty('success')) {
                    genericAjaxPopupClose($popup, 'template_updated');

                } else {
                    if('object' == typeof json && json.hasOwnProperty('error')) {
                        Devblocks.createAlertError(json.error);
                    } else {
                        Devblocks.createAlertError('An unexpected error occurred.');
                    }
                }
            });
        });

        {if 'config' == $section}
        setTimeout(function() {
            $tab_template.find('[data-cerb-button-continue]').click();
        }, 0);
        {/if}
    });
});
</script>