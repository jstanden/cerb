{$popup_id = uniqid('popup')}

<h1 data-cerb-workflow-name>{$model->name}</h1>

<form id="{$popup_id}">
    <input type="hidden" name="id" value="{$model->id}">

    <div id="{$popup_id}Tabs">
        <ul style="display:none;">
            <li><a href="#{$popup_id}TabsTemplate">{'common.template'|devblocks_translate|capitalize}</a></li>
            <li><a href="#{$popup_id}TabsConfig">{'common.configuration'|devblocks_translate|capitalize}</a></li>
            <li><a href="#{$popup_id}TabsChanges">{'common.changes'|devblocks_translate|capitalize}</a></li>
        </ul>

        <div id="{$popup_id}TabsTemplate">
            <div>
                <div class="cerb-code-editor-toolbar" style="margin:0.5em 0;">
                    <button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-changesets-template title="{'common.change_history'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-history"></span></button>
                    <button type="button" class="cerb-code-editor-commands-button" data-cerb-editor-button-commands title="Editor Commands"><span class="glyphicons glyphicons-adjust-alt"></span></button>
                    <button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-builder title="Workflow Builder"><span class="glyphicons glyphicons-claw-hammer"></span></button>
                </div>
                <textarea name="template[kata]" data-editor-mode="ace/mode/cerb_kata" data-editor-lines="35">{$model->workflow_kata}</textarea>
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
                        <textarea name="template[import_resources]" data-editor-mode="ace/mode/cerb_kata" data-editor-lines="15"></textarea>
                    </div>
                </fieldset>
            </div>

            <div style="margin-top:0.5em;">
                <button type="button" data-cerb-button-continue><span class="glyphicons glyphicons-circle-arrow-right"></span> {{'common.continue'|devblocks_translate|capitalize}}</button>
            </div>
        </div>

        <div id="{$popup_id}TabsConfig">
            <div data-cerb-content></div>

            <div style="margin-top:0.5em;">
                <button type="button" data-cerb-button-back><span class="glyphicons glyphicons-circle-arrow-left"></span> {{'common.back'|devblocks_translate|capitalize}}</button>
                <button type="button" data-cerb-button-continue><span class="glyphicons glyphicons-circle-arrow-right"></span> {{'common.continue'|devblocks_translate|capitalize}}</button>
            </div>
        </div>

        <div id="{$popup_id}TabsChanges">
            <div data-cerb-content></div>

            <div style="margin-top:0.5em;">
                <button type="button" data-cerb-button-back><span class="glyphicons glyphicons-circle-arrow-left"></span> {{'common.back'|devblocks_translate|capitalize}}</button>
                <button type="button" data-cerb-button-continue><span class="glyphicons glyphicons-circle-arrow-right"></span> {{'common.continue'|devblocks_translate|capitalize}}</button>
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

        $tabs.tabs({
            hide: { effect: "slide", direction: "up", duration: 250 },
            show: { effect: "slide", direction: "down", duration: 250 }
        });

        let $tab_template = $('#{$popup_id}TabsTemplate');

        // Editors
        let $editor = $popup.find('textarea[name="template[kata]"]')
            .cerbCodeEditor()
            .cerbCodeEditorAutocompleteKata({
                'autocomplete_suggestions': {$autocomplete_suggestions|json_encode nofilter}
            })
            .next('pre.ace_editor')
        ;

        $popup.find('textarea[name="template[import_resources]"]')
            .cerbCodeEditor()
            .cerbCodeEditorAutocompleteKata({
                'autocomplete_suggestions': {
                    '': [
                        'records:'
                    ],
                    'records:': [
                        'record_type/record_key@int: 1234'
                    ]
                }
            })
            .next('pre.ace_editor')
        ;

        {if $model->id}
        let editor_template = ace.edit($editor.attr('id'));

        $popup.find('[data-cerb-editor-button-commands]').on('click', function(e) {
            e.stopPropagation();
            editor_template.execCommand('openCommandPalette');
        });

        $popup.find('[data-cerb-editor-button-builder]').on('click', function(e) {
            e.stopPropagation();

            // Open the Workflow Builder popup w/ data persisted in the workflow
            var formData = new FormData();
            formData.set('c', 'profiles');
            formData.set('a', 'invoke');
            formData.set('module', 'workflow');
            formData.set('action', 'showBuilderPopup');
            formData.set('id', '{$model->id}');
            formData.set('template_kata', editor_template.getValue());

            genericAjaxPopup('editorBuilder{$popup_id}', formData, null, null, '75%');
        });

        $popup.find('[data-cerb-editor-button-changesets-template]').on('click', function(e) {
            e.stopPropagation();

            var formData = new FormData();
            formData.set('c', 'internal');
            formData.set('a', 'invoke');
            formData.set('module', 'records');
            formData.set('action', 'showChangesetsPopup');
            formData.set('record_type', 'workflow');
            formData.set('record_id', '{$model->id}');
            formData.set('record_key', 'template');

            var $editor_template_differ_popup = genericAjaxPopup('editorDiff{$popup_id}', formData, null, null, '80%');

            $editor_template_differ_popup.one('cerb-diff-editor-ready', function(e) {
                e.stopPropagation();

                if(!e.hasOwnProperty('differ'))
                    return;

                e.differ.editors.right.ace.setValue(editor_template.getValue());
                e.differ.editors.right.ace.clearSelection();

                e.differ.editors.right.ace.on('change', function() {
                    editor_template.setValue(e.differ.editors.right.ace.getValue());
                    editor_template.clearSelection();
                });
            });
        });
        {/if}

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
                        $popup.find('h1[data-cerb-workflow-name]').text(json.workflow_name);
                    }

                    $tab_config.find('[data-cerb-content]').html(json.html);
                    $tabs.tabs('option', 'active', 1);
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
            $tabs.tabs('option', 'active', 0);
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
                    $tab_changes.find('[data-cerb-content]').html(json.html);
                    $tabs.tabs('option', 'active', 2);
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
            $tabs.tabs('option', 'active', 1);
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