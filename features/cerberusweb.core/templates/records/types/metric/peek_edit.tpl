{$peek_context = CerberusContexts::CONTEXT_METRIC}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="metric">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
    <input type="hidden" name="do_delete" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-form">
            <div class="cerb-ui-form--field">
                <label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
                <input type="text" name="name" value="{$model->name}" placeholder="(example.metric.name)" autofocus="autofocus" spellcheck="false">
            </div>

            <div class="cerb-ui-form--field">
                <label class="cerb-ui-form--label">{'common.description'|devblocks_translate|capitalize}</label>
                <input type="text" name="description" value="{$model->description}" placeholder="(a description of your metric)">
            </div>

            <div class="cerb-ui-form--row">
                <div class="cerb-ui-form--field">
                    <label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
                    <div>
                        <input type="hidden" name="type" id="metricType_{$form_id}" value="{if 'gauge' == $model->type}gauge{else}counter{/if}">
                        <div class="cerb-ui-switcher" data-cerb-input="metricType_{$form_id}">
                            <button type="button" data-value="counter"{if !$model->type || 'counter' == $model->type} class="cerb-ui-switcher--active"{/if}>Counter</button>
                            <button type="button" data-value="gauge"{if 'gauge' == $model->type} class="cerb-ui-switcher--active"{/if}>Gauge</button>
                        </div>
                    </div>
                </div>

                <div class="cerb-ui-form--field">
                    <label class="cerb-ui-form--label">{'common.retention'|devblocks_translate|capitalize}</label>
                    <select name="retention_days">
                        {foreach from=$retention_options key=opt_value item=opt_label}
                            <option value="{$opt_value}"{if $model->retention_days == $opt_value} selected="selected"{/if}>{$opt_label}</option>
                        {/foreach}
                    </select>
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

    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-header cerb-ui-header--tight">
            <div class="cerb-ui-header--title-sm">Dimensions <small class="cerb-u-text-muted cerb-u-fw-400">(KATA)</small></div>
        </div>
        {$toolbar_dict = DevblocksDictionaryDelegate::instance([
        'caller_name' => 'cerb.toolbar.metrics.dimensions.editor',

        'worker__context' => CerberusContexts::CONTEXT_WORKER,
        'worker_id' => $active_worker->id
        ])}

        {$toolbar_kata =
"interaction/add:
  tooltip: Add dimension
  icon: magic
  uri: ai.cerb.metricBuilder.dimension
interaction/help:
  icon: circle-question-mark
  tooltip: Help
  uri: ai.cerb.metricBuilder.help
"}

        {$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

        {* The editor toolbar is the KataEditor's integrated strip below; these hidden <ul>s are its host sections. *}
        {if $toolbar}
            <div data-cerb-interaction-toolbar hidden>{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}</div>
        {/if}
        <ul class="cerb-ui-toolbar" data-cerb-metric-toolbar hidden>
            <li data-value="suggest" data-icon="autocomplete" title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl/⌘+Space)"></li>
            {if $model->id}
                <li data-value="changesets" data-icon="history" title="{'common.change_history'|devblocks_translate|capitalize}"></li>
            {/if}
        </ul>

        <textarea name="dimensions_kata" data-editor-lines="12" spellcheck="false">{$model->dimensions_kata}</textarea>
    </div>

    {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

    {if !empty($model->id)}
        {include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="metric"}
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
            $popup.dialog('option','title',"{'Metric'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.css('overflow', 'inherit');

            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
            if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

            // Type (counter/gauge) switcher bound to its hidden input
            if(window.CerbUI && CerbUI.Switcher) {
                $popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
                    var input = document.getElementById(this.getAttribute('data-cerb-input'));
                    if(!input) return;
                    new CerbUI.Switcher(this, { value: input.value, onSelect: function(value) { input.value = value; } });
                });
            }

            // Retention — native <select> keeps the POST value; enhance with type-to-filter
            if(window.CerbUI && CerbUI.SelectMenu)
                $popup.find('form select[name=retention_days]').each(function() { new CerbUI.SelectMenu(this); });

            // Editor — KataEditor with its integrated toolbar: the Add dimension / Help interactions merge in as a
            // host section; the Suggest + Change history buttons route through onAction.

            {if $model->id}
            // Open the read-only changeset diff popup; its "Restore this version" button writes a historical version back into this editor.
            var openChangesets = function() {
                var formData = new FormData();
                formData.set('c', 'internal');
                formData.set('a', 'invoke');
                formData.set('module', 'records');
                formData.set('action', 'showChangesetsPopup');
                formData.set('record_type', 'metric');
                formData.set('record_id', '{$model->id}');
                formData.set('record_key', 'dimensions_kata');

                var $editor_policy_differ_popup = genericAjaxPopup('editorDiff{$form_id}', formData, null, null, '80%');

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

            var editor = new CerbUI.KataEditor($popup.find('textarea[name=dimensions_kata]')[0], {
                onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataSchemaMetricDimension),
                toolbar: {
                    sections: [
                        $popup.find('[data-cerb-interaction-toolbar] ul.cerb-ui-toolbar')[0],
                        $popup.find('[data-cerb-metric-toolbar]')[0]
                    ].filter(Boolean),
                    toolbarOpts: {
                        caller: { name: 'cerb.toolbar.editor', params: { selected_text: '' } },
                        start: function(formData) {
                            formData.set('caller[params][selected_text]', editor.getSelectedText());
                        },
                        done: function(e) {
                            e.stopPropagation();
                            if(!e.trigger.is('.cerb-bot-trigger'))
                                return;
                            if(e.eventData.exit === 'return')
                                Devblocks.interactionWorkerPostActions(e.eventData, editor);
                        }
                    },
                    onAction: function(value, ed) {
                        if(value === 'suggest') { ed.openAutocomplete(); return true; }
                        {if $model->id}
                        if(value === 'changesets') { openChangesets(); return true; }
                        {/if}
                        return false;
                    }
                }
            });
            
        });
    });
</script>
