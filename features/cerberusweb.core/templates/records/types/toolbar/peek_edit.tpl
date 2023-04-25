{$peek_context = CerberusContexts::CONTEXT_TOOLBAR}
{$peek_context_id = $model->id}
{$form_id = uniqid('form')}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="toolbar">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    {if $model && $model->id}
        <input type="hidden" name="id" value="{$model->id}">
        <input type="hidden" name="name" value="{$model->name}">
    {/if}
    <input type="hidden" name="do_delete" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <h1>{$model->name}</h1>

    <div style="margin-bottom:10px;">
        {$model->description}
    </div>

    {if $custom_fields}
    <table cellspacing="0" cellpadding="2" border="0" width="98%">
        {include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
    </table>
    {/if}

    {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

    <fieldset class="peek">
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

            {if $model->id}
                <button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-changesets title="{'common.change_history'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-history"></span></button>
            {/if}
            
            {include file="devblocks:cerberusweb.core::toolbars/editor_toolbar_buttons.tpl"}
        </div>

        <textarea name="toolbar_kata" data-editor-mode="ace/mode/cerb_kata">{$model->toolbar_kata}</textarea>

        {$toolbar_ext = $model->getExtension()}
        {if $toolbar_ext}
            {include file="devblocks:cerberusweb.core::toolbars/editor_toolbar.tpl" toolbar_ext=$toolbar_ext}
        {/if}
    </fieldset>

    <div class="buttons" style="margin-top:10px;">
        {if $model->id}
            <button type="button" class="save"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
            <button type="button" class="save-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
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
            $popup.dialog('option','title',"{'Toolbar'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.css('overflow', 'inherit');

            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            //$popup.find('button.create-continue').click({ mode: 'create_continue' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

            // Editor

            var $editor = $popup.find('[name=toolbar_kata]')
                .cerbCodeEditor()
                .cerbCodeEditorAutocompleteKata({
                    autocomplete_suggestions: {$autocomplete_json nofilter}
                })
                .next('pre.ace_editor')
            ;

            var editor = ace.edit($editor.attr('id'));
            
            {if $model->id}
            $popup.find('[data-cerb-editor-button-changesets]').on('click', function(e) {
                e.stopPropagation();

                var formData = new FormData();
                formData.set('c', 'internal');
                formData.set('a', 'invoke');
                formData.set('module', 'records');
                formData.set('action', 'showChangesetsPopup');
                formData.set('record_type', 'toolbar');
                formData.set('record_id', '{$model->id}');
                formData.set('record_key', 'toolbar_kata');

                var $editor_policy_differ_popup = genericAjaxPopup('editorDiff{$form_id}', formData, null, null, '80%');

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
           
           $toolbar.cerbCodeEditorToolbarHandler({
               editor: editor
           });
        });
    });
</script>
