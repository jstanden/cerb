{$popup_id = uniqid('popup')}

<form id="{$popup_id}">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="workflow">
    <input type="hidden" name="action" value="runBuilderPopup">
    <input type="hidden" name="id" value="{$model->id}">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <div class="cerb-ui-header">
        <div>
            <div class="cerb-ui-header--title">Workflow Builder Schema: (KATA)</div>
        </div>
    </div>

    {* Integrated editor toolbar: a single "suggest" (autocomplete) button merged into the KataEditor's strip. *}
    <ul class="cerb-ui-toolbar" data-cerb-editor-toolbar hidden>
        <li data-value="suggest" data-icon="autocomplete" title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl/⌘+Space)"></li>
    </ul>

    <textarea name="workflow_builder_kata" data-editor-lines="25" spellcheck="false">{$model->builder_kata}</textarea>
    <br>

    <button type="button" class="submit"><span class="cerb-icons cerb-icon-play"></span> {'common.build'|devblocks_translate|capitalize}</button>

    <div class="status" style="margin-top:10px;display:none;">
        <h2>Workflow KATA</h2>
        <textarea class="cerb-workflow-builder-results" data-editor-lines="25" spellcheck="false"></textarea>
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

        var editor_results = new CerbUI.KataEditor($frm.find('.cerb-workflow-builder-results')[0], { readOnly: true, minLines: 15 });

        var editor = new CerbUI.KataEditor($frm.find('textarea[name=workflow_builder_kata]')[0], {
            minLines: 15,
            onAutocomplete: CerbUI.KataEditor.kataFieldSource({
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
            }),
            toolbar: {
                sections: [ $frm.find('[data-cerb-editor-toolbar]')[0] ],
                onAction: function(value, ed) {
                    if(value === 'suggest') { ed.openAutocomplete(); return true; }
                    return false;
                }
            }
        });

        $button
            .click(function (e) {
                e.stopPropagation();

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
