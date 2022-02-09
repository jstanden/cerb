{$peek_context = CerberusContexts::CONTEXT_METRIC}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="metric">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
    <input type="hidden" name="do_delete" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <table cellspacing="0" cellpadding="2" border="0" width="98%">
        <tr>
            <td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
            <td width="99%">
                <input type="text" name="name" value="{$model->name}" style="width:98%;" placeholder="(example.metric.name)" autofocus="autofocus" spellcheck="false">
            </td>
        </tr>
        
        <tr>
            <td width="1%" nowrap="nowrap"><b>{'common.description'|devblocks_translate|capitalize}:</b></td>
            <td width="99%">
                <input type="text" name="description" value="{$model->description}" placeholder="(a description of your metric)" style="width:98%;">
            </td>
        </tr>

        <tr>
            <td width="1%" nowrap="nowrap"><b>{'common.type'|devblocks_translate|capitalize}:</b></td>
            <td width="99%">
                <label>
                    <input type="radio" name="type" value="counter" {if !$model->type || 'counter' == $model->type}checked="checked"{/if}> Counter
                </label>
                <label>
                    <input type="radio" name="type" value="gauge" {if 'gauge' == $model->type}checked="checked"{/if}> Gauge
                </label>
            </td>
        </tr>

        {if !empty($custom_fields)}
            {include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
        {/if}
    </table>

    <fieldset class="peek">
        <legend>Dimensions: <small>(KATA)</small></legend>
        <div class="cerb-code-editor-toolbar">
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
            
            {if $toolbar}
                {DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}
            {/if}
        </div>

        <textarea name="dimensions_kata" data-editor-mode="ace/mode/cerb_kata">{$model->dimensions_kata}</textarea>
    </fieldset>

    {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

    {if !empty($model->id)}
        <fieldset style="display:none;" class="delete">
            <legend>{'common.delete'|devblocks_translate|capitalize}</legend>

            <div>
                Are you sure you want to permanently delete this metric?
            </div>

            <button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
            <button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
        </fieldset>
    {/if}

    <div class="buttons" style="margin-top:10px;">
        {if $model->id}
            <button type="button" class="save"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
            <button type="button" class="save-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
            {if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
        {else}
            <button type="button" class="save"><span class="glyphicons glyphicons-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
        {/if}
    </div>

</form>

<script type="text/javascript">
    $(function() {
        var $frm = $('#{$form_id}');
        var $popup = genericAjaxPopupFind($frm);

        $popup.one('popup_open', function() {
            $popup.dialog('option','title',"{'Metric'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.css('overflow', 'inherit');

            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

            // Close confirmation

            $popup.on('dialogbeforeclose', function(e) {
                var keycode = e.keyCode || e.which;
                if(27 === keycode)
                    return confirm('{'warning.core.editor.close'|devblocks_translate}');
            });

            // Editor

            var $editor = $popup.find('[name=dimensions_kata]')
                .cerbCodeEditor()
                .cerbCodeEditorAutocompleteKata({
                    autocomplete_suggestions: cerbAutocompleteSuggestions.kataSchemaMetricDimension
                })
                .next('pre.ace_editor')
            ;

            var editor = ace.edit($editor.attr('id'));

            // Toolbar

            var $toolbar = $popup.find('.cerb-code-editor-toolbar');

            $toolbar.cerbToolbar({
                caller: {
                    name: 'cerb.toolbar.editor',
                    params: {
                        //toolbar: 'cerb.toolbar.cardWidget.interactions',
                        selected_text: ''
                    }
                },
                start: function(formData) {
                    // [TODO]
                    //formData.set('toolbar', '');
                    formData.set('caller[params][selected_text]', editor.getSelectedText());
                },
                done: function(e) {
                    e.stopPropagation();

                    var $target = e.trigger;

                    if(!$target.is('.cerb-bot-trigger'))
                        return;

                    if(!e.eventData || !e.eventData.exit)
                        return;

                    if (e.eventData.exit === 'error') {
                        // [TODO] Show error

                    } else if(e.eventData.exit === 'return' && e.eventData.return.snippet) {
                        editor.insertSnippet(e.eventData.return.snippet);
                    }
                },
                reset: function(e) {
                    e.stopPropagation();
                }
            });
        });
    });
</script>
