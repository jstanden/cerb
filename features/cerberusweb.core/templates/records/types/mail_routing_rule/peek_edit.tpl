{$peek_context = CerberusContexts::CONTEXT_MAIL_ROUTING_RULE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="mail_routing_rule">
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

    <fieldset class="peek">
        <legend>Routing: (KATA)</legend>
        <div class="cerb-code-editor-toolbar">
            <button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-magic title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl+Space)"><span class="cerb-icons cerb-icon-sparkles"></span></button>
            {if $model->id}
                <button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-changesets title="{'common.change_history'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-history"></span></button>
            {/if}

            {$toolbar_dict = DevblocksDictionaryDelegate::instance([
            'caller_name' => 'cerb.toolbar.editor',

            'worker__context' => CerberusContexts::CONTEXT_WORKER,
            'worker_id' => $active_worker->id
            ])}

            {$toolbar_kata =
"menu/insert:
  icon: circle-plus
  hidden@bool: yes
  items:
    interaction/rule:
      label: Rule
      uri: ai.cerb.mailRoutingRuleBuilder.rule
"}

            {$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

            {DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

            <div class="cerb-code-editor-toolbar-divider"></div>

            <button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-help title="{'common.help'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-circle-question-mark"></span></button>
            <button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-tester title="{'common.test'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-lab"></span></button>

            <div class="cerb-code-editor-toolbar-divider"></div>
        </div>

        <textarea name="routing_kata" data-editor-mode="ace/mode/cerb_kata" data-editor-lines="30">{$model->routing_kata}</textarea>
    </fieldset>

    <fieldset data-cerb-fieldset-help class="peek black cerb-hidden">
        <legend style="font-size:140%;">{'common.help'|devblocks_translate|capitalize}</legend>

        {if $routing_placeholders}
            <h3 style="padding:0;margin:0 0 5px 0;">{'common.placeholders'|devblocks_translate|capitalize}</h3>
            <div>
                <div class="cerb-markdown-content">
                    <table cellpadding="2" cellspacing="2" width="100%">
                        <colgroup>
                            <col style="width:1%;white-space:nowrap;">
                            <col style="padding-left:10px;">
                        </colgroup>
                        <tbody>
                        {foreach from=$routing_placeholders item=placeholder_notes key=placeholder_key}
                            <tr>
                                <td valign="top">
                                    <strong><code>{$placeholder_key}</code></strong>
                                </td>
                                <td>
                                    {$placeholder_notes|devblocks_markdown_to_html nofilter}
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        {/if}
    </fieldset>

    <fieldset data-cerb-routing-tester class="peek black cerb-hidden" style="margin:10px 0 0 0;">
        <legend style="font-size:140%;">{'common.test'|devblocks_translate|capitalize}</legend>

        <div>
            <div data-cerb-routing-tester-editor-placeholders>
                <div class="cerb-code-editor-toolbar">
                    <b>{'common.placeholders'|devblocks_translate|capitalize} (KATA)</b>
                    <div class="cerb-code-editor-toolbar-divider"></div>
                    <button type="button" class="cerb-code-editor-toolbar-button cerb-code-editor-toolbar-button--chooser" title="{'common.choose'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-search"></span></button>
                    <button type="button" class="cerb-code-editor-toolbar-button cerb-code-editor-toolbar-button--run" title="{'common.run'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-play"></span></button>
                </div>
                <textarea name="tester[placeholders]" data-editor-mode="ace/mode/cerb_kata" rows="5" cols="45"></textarea>
            </div>

            <div data-cerb-routing-tester-results style="margin-top:10px;position:relative;"></div>
        </div>
    </fieldset>

    {if !empty($model->id)}
        <fieldset style="display:none;" class="delete">
            <legend>{'common.delete'|devblocks_translate|capitalize}</legend>

            <div>
                Are you sure you want to permanently delete this mail routing rule?
            </div>

            <button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
            <button type="button" class="delete-cancel">{'common.no'|devblocks_translate|capitalize}</button>
        </fieldset>
    {/if}

    <div class="buttons" style="margin-top:10px;">
        {if $model->id}
            <button type="button" class="save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
            <button type="button" class="save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
            {if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
        {else}
            <button type="button" class="save"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
        {/if}
    </div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
    $(function() {
        let $frm = $('#{$form_id}');
        let $popup = genericAjaxPopupFind($frm);

        Devblocks.formDisableSubmit($frm);

        $popup.one('popup_open', function() {
            $popup.dialog('option','title',"{'Mail Routing Rule'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.find('[autofocus]:first').focus();
            $popup.css('overflow', 'inherit');

            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete-prompt').click(Devblocks.callbackPeekEditDeletePrompt);
            $popup.find('button.delete-cancel').click(Devblocks.callbackPeekEditDeleteCancel);

            // Editor

            let autocomplete_suggestions = {if $autocomplete_json}{$autocomplete_json nofilter}{else}[]{/if};

            let $editor = $popup.find('[name=routing_kata]')
                .cerbCodeEditor()
                .cerbCodeEditorAutocompleteKata({
                    autocomplete_suggestions: autocomplete_suggestions
                })
            ;

            let editor = ace.edit($editor.next('pre.ace_editor').attr('id'));

            {if $model->id}
            $popup.find('[data-cerb-editor-button-changesets]').on('click', function(e) {
                e.stopPropagation();

                let formData = new FormData();
                formData.set('c', 'internal');
                formData.set('a', 'invoke');
                formData.set('module', 'records');
                formData.set('action', 'showChangesetsPopup');
                formData.set('record_type', 'mail_routing_rule');
                formData.set('record_id', '{$model->id}');
                formData.set('record_key', 'routing_kata');

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

            var doneFunc = function(e) {
                e.stopPropagation();

                var $target = e.trigger;

                if(!$target.is('.cerb-bot-trigger'))
                    return;

                if (e.eventData.exit === 'error') {

                } else if(e.eventData.exit === 'return') {
                    Devblocks.interactionWorkerPostActions(e.eventData, editor);
                }
            };

            var resetFunc = function(e) {
                e.stopPropagation();
            };

            var $toolbar = $popup.find('.cerb-code-editor-toolbar').cerbToolbar({
                caller: {
                    name: 'cerb.toolbar.editor',
                    params: {
                        toolbar: 'cerb.toolbar.recordEditor.toolbarSection',
                        selected_text: ''
                    }
                },
                start: function(formData) {
                    let pos = editor.getCursorPosition();
                    let token_path = Devblocks.cerbCodeEditor.getKataTokenPath(pos, editor).join('');

                    formData.set('caller[params][selected_text]', editor.getSelectedText());
                    formData.set('caller[params][token_path]', token_path);
                    formData.set('caller[params][cursor_row]', pos.row);
                    formData.set('caller[params][cursor_column]', pos.column);

                    formData.set('caller[params][toolbar]', '{if $toolbar_ext}{$toolbar_ext->id}{/if}');
                    formData.set('caller[params][value]', editor.getValue());
                },
                done: doneFunc,
                reset: resetFunc,
            });

            $toolbar.find('[data-cerb-editor-button-help]').on('click', function(e) {
                e.stopPropagation();
                let $button = $(this);
                let $fieldset = $popup.find('[data-cerb-fieldset-help]').toggle();

                if($fieldset.is(':visible')) {
                    $button.addClass('cerb-code-editor-toolbar-button--enabled');
                } else {
                    $button.removeClass('cerb-code-editor-toolbar-button--enabled');
                }
            });

            let $fieldset_tester = $popup.find('[data-cerb-routing-tester]');
            let highlight_marker = null;
            let $fieldset_tester_results = $fieldset_tester.find('[data-cerb-routing-tester-results]');

            let $editor_tester = $fieldset_tester.find('textarea[name="tester[placeholders]"]')
                .cerbCodeEditor()
                .next('pre.ace_editor')
            ;

            let editor_tester = ace.edit($editor_tester.attr('id'));

            $toolbar.find('[data-cerb-editor-button-tester]').on('click', function(e) {
                e.stopPropagation();
                let $button = $(this);

                if($fieldset_tester.toggle().is(':visible')) {
                    $button.addClass('cerb-code-editor-toolbar-button--enabled');
                } else {
                    $button.removeClass('cerb-code-editor-toolbar-button--enabled');
                }
            });

            $fieldset_tester.find('.cerb-code-editor-toolbar-button--chooser')
                .attr('data-interaction-uri', 'cerb:automation:ai.cerb.routingRuleBuilder.inputChooser')
                .attr('data-interaction-params', '')
                .cerbBotTrigger({
                    'width': '80%',
                    'done': function(e) {
                        Devblocks.interactionWorkerPostActions(e.eventData, editor_tester);
                    },
                })
            ;

            $fieldset_tester.find('.cerb-code-editor-toolbar-button--run').on('click', function(e) {
                e.stopPropagation();

                if(null != highlight_marker) {
                    editor.session.removeMarker(highlight_marker.id);
                    highlight_marker = null;
                }

                $fieldset_tester_results.html('').hide();

                let formData = new FormData($frm.get(0));
                formData.set('c', 'profiles');
                formData.set('a', 'invoke');
                formData.set('module', 'mail_routing_rule');
                formData.set('action', 'testRoutingKataJson');

                genericAjaxPost(formData, null, null, function(json) {
                    if('object' !== typeof json)
                        return;

                    if(!json.hasOwnProperty('key')) {
                        let $h1 = $('<h1/>').text('(no matching rules)');
                        $fieldset_tester_results.append($h1).fadeIn();

                    } else if(json.hasOwnProperty('line')) {
                        let row = json['line'];
                        highlight_marker = editor.session.highlightLines(row, row);
                        editor.scrollToLine(row);

                        let $h1 = $('<h1/>').text('Matched ' + json['key']);
                        $fieldset_tester_results.append($h1).fadeIn();
                    }
                });
            });

            $toolbar.find('[data-cerb-editor-button-magic]').on('click', function(e) {
                e.stopPropagation();
            	editor.commands.byName.startAutocomplete.exec(editor);
            });

            $toolbar.cerbCodeEditorToolbarHandler({
                editor: editor
            });
        });
    });
</script>
