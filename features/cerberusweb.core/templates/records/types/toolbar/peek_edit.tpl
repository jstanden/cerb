{$peek_context = CerberusContexts::CONTEXT_TOOLBAR}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="toolbar">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
    <input type="hidden" name="do_delete" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <table cellspacing="0" cellpadding="2" border="0" width="98%">
        <tr>
            <td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
            <td width="99%">
                <input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus" spellcheck="false">
            </td>
        </tr>

        <tr>
            <td width="1%" nowrap="nowrap"><b>{'common.description'|devblocks_translate|capitalize}:</b></td>
            <td width="99%">
                <input type="text" name="description" value="{$model->description}" style="width:98%;">
            </td>
        </tr>

        {if !empty($custom_fields)}
            {include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
        {/if}
    </table>


    {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

    <fieldset>
        <legend>Toolbar: (KATA)</legend>
        <div class="cerb-code-editor-toolbar">
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

            {DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

            <div class="cerb-code-editor-toolbar-divider"></div>

            <button type="button" data-cerb-button="interactions-preview" class="cerb-code-editor-toolbar-button"><span class="glyphicons glyphicons-play"></span></button>

            <button type="button" style="float:right;" class="cerb-code-editor-toolbar-button cerb-editor-button-help"><a href="#" target="_blank"><span class="glyphicons glyphicons-circle-question-mark"></span></a></button>
        </div>

        <textarea name="toolbar_kata" data-editor-mode="ace/mode/cerb_kata">{$model->toolbar_kata}</textarea>
    </fieldset>

    {if !empty($model->id)}
        <fieldset style="display:none;" class="delete">
            <legend>{'common.delete'|devblocks_translate|capitalize}</legend>

            <div>
                Are you sure you want to permanently delete this toolbar?
            </div>

            <button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
            <button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
        </fieldset>
    {/if}

    <div class="buttons" style="margin-top:10px;">
        {if $model->id}
            <button type="button" class="save"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
            <button type="button" class="save-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
            {if $smarty.const.DEVELOPMENT_MODE && $active_worker->is_superuser}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
        {else}
            <button type="button" class="save"><span class="glyphicons glyphicons-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
        {/if}
    </div>

</form>

<script type="text/javascript">
    $(function() {
        var $frm = $('#{$form_id}');
        var $popup = genericAjaxPopupFind($frm);

        $popup.one('popup_open', function(event,ui) {
            $popup.dialog('option','title',"{'Toolbar'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.css('overflow', 'inherit');

            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            //$popup.find('button.create-continue').click({ mode: 'create_continue' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

            // Close confirmation

            $popup.on('dialogbeforeclose', function(e, ui) {
                var keycode = e.keyCode || e.which;
                if(keycode === 27)
                    return confirm('{'warning.core.editor.close'|devblocks_translate}');
            });

            // Editor

            var $editor = $popup.find('[name=toolbar_kata]')
                .cerbCodeEditor()
                .next('pre.ace_editor')
            ;

            var editor = ace.edit($editor.attr('id'));
            
            // Toolbar
            
            var doneFunc = function(e) {
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
            };

            var resetFunc = function(e) {
                e.stopPropagation();
            };

            $popup.find('.cerb-code-editor-toolbar').cerbToolbar({
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
                done: doneFunc,
                reset: resetFunc,
            });
            
            // [UI] Editor behaviors
            {include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
        });
    });
</script>
