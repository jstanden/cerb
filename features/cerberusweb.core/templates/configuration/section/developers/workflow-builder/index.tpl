<h2>Workflow Builder</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupWorkflowBuilder">
    <input type="hidden" name="c" value="config">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="workflow_builder">
    <input type="hidden" name="action" value="exportWorkflowKata">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <fieldset class="peek">
        <legend>
            Workflow Builder Schema: (KATA)
            {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/workflows/"}
        </legend>

        <div class="cerb-code-editor-toolbar">
            <button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-magic title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl+Space)"><span class="glyphicons glyphicons-magic"></span></button>
        </div>

        <textarea name="workflow_builder_kata" data-editor-mode="ace/mode/cerb_kata" rows="5" cols="45"></textarea>
        <br>

        <button type="button" class="submit"><span class="glyphicons glyphicons-play"></span> {'common.build'|devblocks_translate|capitalize}</button>

        <div class="status" style="margin-top:10px;display:none;">
            <h2>Workflow KATA</h2>
            <textarea class="cerb-workflow-builder-results" data-editor-mode="ace/mode/cerb_kata" rows="5" cols="45"></textarea>
        </div>
    </fieldset>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
    $(function() {
        var $frm = $('#frmSetupWorkflowBuilder');
        var $status = $frm.find('div.status');
        var $button = $frm.find('BUTTON.submit');
        var $spinner = Devblocks.getSpinner();

        Devblocks.formDisableSubmit($frm);

        var $editor_results =
            $frm.find('.cerb-workflow-builder-results')
                .cerbCodeEditor()
                .nextAll('pre.ace_editor')
        ;

        var $editor = $frm.find('textarea[name=workflow_builder_kata]')
            .cerbCodeEditor()
            .cerbCodeEditorAutocompleteKata({
                autocomplete_suggestions: {
                    '': [
                        'export:'
                    ],
                    'export:': [
                        'label_map:',
                        'records:',
                        'workflow:',
                    ],
                    'export:workflow:': [
                        'description:',
                        'instructions:',
                        'name:',
                        'requirements:',
                        'version:',
                        'website:',
                    ],
                    'export:workflow:requirements:': [
                        'cerb_version: >=11.0 <11.2',
                        'cerb_plugins: cerberusweb.core, ',
                    ],
                    'export:workflow:version:': [
                        '2025-12-31T00:00:00Z',
                    ],
                    'export:workflow:website:': [
                        'https://cerb.ai/resources/workflows/',
                    ],
                    'export:records:': [
                        'record_type/record_key:',
                    ],
                    'export:label_map:': [
                        'record_type_and_id: record_key',
                    ],
                    '*': {
                        'export:records:(.*?):': [
                            'query: id:[1,2,3]',
                            'include_children@bool: yes',
                        ]
                    }
                }
            })
            .nextAll('pre.ace_editor')
        ;

        let editor = ace.edit($editor.attr('id'));

        $frm.find('[data-cerb-editor-button-magic]').on('click', function(e) {
            editor.commands.byName.startAutocomplete.exec(editor);
        });

        $button
            .click(function(e) {
                e.stopPropagation();
                var editor_results = ace.edit($editor_results.attr('id'));

                Devblocks.clearAlerts();

                $button.hide();
                $status.hide();
                $spinner.insertBefore($status);
                editor_results.setValue('');

                let onError = function() {
                    $button.fadeIn();
                    $spinner.detach();
                    Devblocks.createAlertError('An unexpected error occurred.');
                };

                let onResponse = function(json) {
                    $button.fadeIn();
                    $spinner.detach();

                    if(null == json || 'object' !== typeof json || !json.status) {
                        if(json && json.hasOwnProperty('error')) {
                            Devblocks.createAlertError(json.error);
                        } else {
                            Devblocks.createAlertError('An unexpected error occurred.');
                        }

                    } else {
                        if(json.hasOwnProperty('workflow_kata')) {
                            editor_results.setValue(json.workflow_kata);
                        }
                        editor_results.clearSelection();
                        $status.show();
                    }
                };

                genericAjaxPost($frm, null, null, onResponse, { error: onError });
            })
        ;
    });
</script>
