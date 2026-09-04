{$peek_context = CerberusContexts::CONTEXT_MAIL_ROUTING_RULE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<div id="routingAgentMount{$form_id}">
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="mail_routing_rule">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
    <input type="hidden" name="do_delete" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    {include file="devblocks:cerberusweb.core::records/types/workflow/managed_callout.tpl" workflow=$workflow workflow_url=$workflow_url noun="rule"}

    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-form">
            <div class="cerb-ui-form--field">
                <label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
                <input type="text" name="name" value="{$model->name}" autofocus="autofocus">
            </div>

            <div class="cerb-ui-form--row">
                <div class="cerb-ui-form--field">
                    <label class="cerb-ui-form--label">{'common.priority'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-sort-asc" title="0=first, 255=last"></span></label>
                    <div><input type="number" name="priority" min="0" max="255" value="{$model->priority|default:100}" style="width:5em;"></div>
                </div>

                <div class="cerb-ui-form--field">
                    <label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
                    <div>
                        <input type="hidden" name="is_disabled" id="ruleStatus_{$form_id}" value="{if $model->is_disabled}1{else}0{/if}">
                        <div class="cerb-ui-switcher" data-cerb-input="ruleStatus_{$form_id}">
                            <button type="button" data-value="0"{if !$model->is_disabled} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.enabled'|devblocks_translate|capitalize}</button>
                            <button type="button" data-value="1"{if $model->is_disabled} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-ban"></span> {'common.disabled'|devblocks_translate|capitalize}</button>
                        </div>
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
            <div class="cerb-ui-header--title-sm">Routing <small class="cerb-u-text-muted cerb-u-fw-400">(KATA)</small></div>
        </div>
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

        {* Editor toolbar sections merged into the KataEditor's integrated strip: the server-rendered
           Insert menu + the local Suggest/Change history/Help/Test items. *}
        <div data-cerb-interaction-toolbar hidden>{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}</div>
        <ul class="cerb-ui-toolbar" data-cerb-routing-toolbar-items hidden>
            <li data-value="suggest" data-icon="autocomplete" title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl/⌘+Space)"></li>
            {if $model->id}
                <li data-value="changesets" data-icon="history" title="{'common.change_history'|devblocks_translate|capitalize}"></li>
            {/if}
            <li></li>
            <li data-value="help" data-toggle data-key="help" data-icon="circle-question-mark" title="{'common.help'|devblocks_translate|capitalize}"></li>
            <li data-value="tester" data-toggle data-key="tester" data-icon="lab" title="{'common.test'|devblocks_translate|capitalize}"></li>
        </ul>

        <textarea name="routing_kata" data-editor-lines="30" spellcheck="false">{$model->routing_kata}</textarea>
    </div>

    <div data-cerb-fieldset-help class="cerb-ui-panel cerb-ui-panel--spaced cerb-hidden">
        <div class="cerb-ui-header cerb-ui-header--tight">
            <div class="cerb-ui-header--title-sm">{'common.help'|devblocks_translate|capitalize}</div>
        </div>

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
    </div>

    <div data-cerb-routing-tester class="cerb-ui-panel cerb-ui-panel--spaced cerb-hidden">
        <div class="cerb-ui-header cerb-ui-header--tight">
            <div class="cerb-ui-header--title-sm">{'common.test'|devblocks_translate|capitalize}</div>
        </div>

        <div>
            <div data-cerb-routing-tester-editor-placeholders>
                <div class="cerb-ui-editor-toolbar cerb-u-flex cerb-u-items-center cerb-u-gap-2">
                    <span class="cerb-u-text-muted cerb-u-fs-n1">{'common.placeholders'|devblocks_translate|capitalize} (KATA)</span>
                    <button type="button" class="cerb-ui-button cerb-ui-button--transparent cerb-code-editor-toolbar-button--chooser" title="{'common.choose'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-search"></span></button>
                    <button type="button" class="cerb-ui-button cerb-ui-button--transparent cerb-code-editor-toolbar-button--run" title="{'common.run'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-play"></span></button>
                </div>
                <textarea name="tester[placeholders]" data-editor-lines="6" spellcheck="false"></textarea>
            </div>

            <div data-cerb-routing-tester-results style="margin-top:10px;position:relative;"></div>
        </div>
    </div>

    {if !empty($model->id)}
        {include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="mail routing rule"}
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
</div>{* #routingAgentMount -- AgentPane wraps this *}

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
            if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

            // Status (enabled/disabled) switcher bound to its hidden input
            if(window.CerbUI && CerbUI.Switcher) {
                $popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
                    var input = document.getElementById(this.getAttribute('data-cerb-input'));
                    if(!input) return;
                    new CerbUI.Switcher(this, { value: input.value, onSelect: function(value) { input.value = value; } });
                });
            }

            $popup.find('a.cerb-peek-trigger').cerbPeekTrigger();

            // Editor

            let autocomplete_suggestions = {if $autocomplete_json}{$autocomplete_json nofilter}{else}[]{/if};

            let editor = new CerbUI.KataEditor($popup.find('textarea[name=routing_kata]')[0], {
                onAutocomplete: CerbUI.KataEditor.kataFieldSource(autocomplete_suggestions),
                // Mark what changed since the last save. The checkpoint is captured on open and re-taken on
                // save-and-continue, so the gutter answers "what have we touched in this sitting" -- whether
                // the edit came from a person or from the agent, which has no other way to show its work.
                diffGutter: true,
                toolbar: {
                    sections: [
                        $popup.find('[data-cerb-interaction-toolbar] ul.cerb-ui-toolbar')[0],
                        $popup.find('[data-cerb-routing-toolbar-items]')[0]
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
                        if(value === 'suggest')    { ed.openAutocomplete(); return true; }
                        {if $model->id}
                        if(value === 'changesets') { openChangesets(); return true; }
                        {/if}
                        if(value === 'help')   { $popup.find('[data-cerb-fieldset-help]').toggle(!!(item && item.pressed)); return true; }
                        if(value === 'tester') { $popup.find('[data-cerb-routing-tester]').toggle(!!(item && item.pressed)); return true; }
                        return false;
                    }
                }
            });

            {if $model->id}
            let openChangesets = function() {
                let formData = new FormData();
                formData.set('c', 'internal');
                formData.set('a', 'invoke');
                formData.set('module', 'records');
                formData.set('action', 'showChangesetsPopup');
                formData.set('record_type', 'mail_routing_rule');
                formData.set('record_id', '{$model->id}');
                formData.set('record_key', 'routing_kata');

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

            let $fieldset_tester = $popup.find('[data-cerb-routing-tester]');
            let $fieldset_tester_results = $fieldset_tester.find('[data-cerb-routing-tester-results]');

            let editor_tester = new CerbUI.KataEditor($fieldset_tester.find('textarea')[0]);

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

            // A plain save closes the popup, so only save-and-continue needs the checkpoint re-taken.
            $popup.on('peek_saved', function(e) { if(e.is_continue) editor.resetDiffBaseline(); });

            {include file="devblocks:cerberusweb.core::records/types/mail_routing_rule/_agent_pane.tpl" routing_scope="rule" mount="routingAgentMount`$form_id`" split=true}

            $fieldset_tester.find('.cerb-code-editor-toolbar-button--run').on('click', function(e) {
                e.stopPropagation();

                editor.clearHighlight();

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
                        $fieldset_tester_results.append($('<div/>').addClass('cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--note').text('(no matching rules)')).fadeIn();

                    } else if(json.hasOwnProperty('line')) {
                        let row = json['line'];
                        editor.highlightLine(row, { color: 'green' });
                        editor.scrollToLine(row);

                        $fieldset_tester_results.append($('<div/>').addClass('cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--success').text('Matched ' + json['key'])).fadeIn();
                    }
                });
            });
        });
    });
</script>
