{$popup_id = uniqid('popup')}

<form id="{$popup_id}">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="workflow">
    <input type="hidden" name="action" value="runBuilderPopup">
    <input type="hidden" name="id" value="{$model->id}">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <h1>Workflow Builder Schema: (KATA)</h1>

    <div class="cerb-code-editor-toolbar">
        <button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-magic title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl+Space)"><span class="glyphicons glyphicons-magic"></span></button>
    </div>

    <textarea name="workflow_builder_kata" data-editor-mode="ace/mode/cerb_kata" rows="5" cols="45">{$model->builder_kata}</textarea>
    <br>

    <button type="button" class="submit"><span class="glyphicons glyphicons-play"></span> {'common.build'|devblocks_translate|capitalize}</button>

    <div class="status" style="margin-top:10px;display:none;">
        <h2>Workflow KATA</h2>
        <textarea class="cerb-workflow-builder-results" data-editor-mode="ace/mode/cerb_kata" rows="5" cols="45"></textarea>
    </div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    let $frm = $('#{$popup_id}');
    let $popup = genericAjaxPopupFind($frm);
    var $status = $frm.find('div.status');
    var $button = $frm.find('BUTTON.submit');
    var $spinner = Devblocks.getSpinner();

    Devblocks.formDisableSubmit($frm);

    $popup.one('popup_open', function () {
        $popup.dialog('option', 'title', 'Workflow Builder');

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

        $frm.find('[data-cerb-editor-button-magic]').on('click', function (e) {
            editor.commands.byName.startAutocomplete.exec(editor);
        });

        $button
            .click(function (e) {
                e.stopPropagation();
                var editor_results = ace.edit($editor_results.attr('id'));

                Devblocks.clearAlerts();

                $button.hide();
                $status.hide();
                $spinner.insertBefore($status);
                editor_results.setValue('');

                let onError = function () {
                    $button.fadeIn();
                    $spinner.detach();
                    Devblocks.createAlertError('An unexpected error occurred.');
                };

                let onResponse = function (json) {
                    $button.fadeIn();
                    $spinner.detach();

                    if (null == json || 'object' !== typeof json || !json.status) {
                        if (json && json.hasOwnProperty('error')) {
                            Devblocks.createAlertError(json.error);
                        } else {
                            Devblocks.createAlertError('An unexpected error occurred.');
                        }

                    } else {
                        if (json.hasOwnProperty('workflow_kata')) {
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
});
</script>
