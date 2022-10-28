{$peek_context = CerberusContexts::CONTEXT_RESOURCE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="resource">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
    <input type="hidden" name="do_delete" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <table cellspacing="0" cellpadding="2" border="0" width="98%">
        <tr>
            <td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
            <td width="99%">
                <input type="text" name="name" value="{$model->name}" style="width:98%;" spellcheck="false" autofocus="autofocus">
            </td>
        </tr>

        <tr>
            <td width="1%" nowrap="nowrap">
                <b>{'common.description'|devblocks_translate|capitalize}:</b>
            </td>
            <td width="99%">
                <input type="text" name="description" value="{$model->description}" style="width:100%;">
            </td>
        </tr>

        <tr>
            <td width="1%" nowrap="nowrap">
                <b>{'common.type'|devblocks_translate|capitalize}:</b>
            </td>
            <td width="99%">
                <select name="extension_id">
                    <option value=""></option>
                    {foreach from=$resource_extensions item=resource_extension}
                        <option value="{$resource_extension->id}" {if $model->extension_id==$resource_extension->id}selected="selected"{/if}>{$resource_extension->name}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        
        {if !empty($custom_fields)}
            {include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
        {/if}
    </table>
    
    <fieldset data-cerb-resource-config style="margin-top:10px;">
        <div>
            <label>
                <input type="radio" name="is_dynamic" value="0" {if !$model->is_dynamic}checked="checked"{/if}>
                {'common.file'|devblocks_translate|capitalize}
            </label>
            <label>
                <input type="radio" name="is_dynamic" value="1" {if $model->is_dynamic}checked="checked"{/if}>
                {'common.automation'|devblocks_translate|capitalize}
            </label>
        </div>

        <div style="margin:5px 10px 0 0;">
            <div data-cerb-file-static style="display:{if !$model->is_dynamic}block{else}none{/if};">
                <table cellpadding="2" cellspacing="0" width="100%">
                    <tr>
                        <td width="1%" nowrap="nowrap">
                            <b>{'common.upload'|devblocks_translate|capitalize}:</b>
                        </td>
                        <td>
                            <input type="file" name="file" value="">
                        </td>
                    </tr>
                </table>
            </div>

            <div data-cerb-file-dynamic style="display:{if $model->is_dynamic}block{else}none{/if};">
                <fieldset data-cerb-event-resource-get class="peek black">
                    <legend>Event: Get resource (KATA)</legend>
                    <div class="cerb-code-editor-toolbar">
                        {$toolbar_dict = DevblocksDictionaryDelegate::instance([
                            'caller_name' => 'cerb.toolbar.eventHandlers.editor'
                        ])}

                        {$toolbar_kata =
"interaction/automation:
  uri: ai.cerb.eventHandler.automation
  icon: circle-plus
  tooltip: Automation
"}

                        {$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

                        {DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

                        <div class="cerb-code-editor-toolbar-divider"></div>
                        {include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler_buttons.tpl"}
                    </div>
                    
                    <textarea name="automation_kata" data-editor-mode="ace/mode/cerb_kata">{$model->automation_kata}</textarea>

                    {$trigger_ext = Extension_AutomationTrigger::get(AutomationTrigger_ResourceGet::ID, true)}
                    {if $trigger_ext}
                        {include file="devblocks:cerberusweb.core::automations/triggers/editor_event_handler.tpl" trigger_inputs=$trigger_ext->getEventPlaceholders()}
                    {/if}
                </fieldset>
            </div>
        </div>
    </fieldset>

    {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

    {if !empty($model->id)}
        <fieldset style="display:none;" class="delete">
            <legend>{'common.delete'|devblocks_translate|capitalize}</legend>

            <div>
                Are you sure you want to permanently delete this resource?
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

        $popup.one('popup_open', function(event,ui) {
            $popup.dialog('option','title',"{'Resource'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.css('overflow', 'inherit');

            var $file_mode = $popup.find('input[name=is_dynamic]');
            var $file_mode_static = $popup.find('[data-cerb-file-static]');
            var $file_mode_dynamic = $popup.find('[data-cerb-file-dynamic]');
            
            $file_mode.on('click', function(e) {
                e.stopPropagation();
                
                var $target = $(e.target);
                
                if('1' === $target.val()) {
                    $file_mode_static.hide();
                    $file_mode_dynamic.fadeIn();
                } else {
                    $file_mode_dynamic.hide();
                    $file_mode_static.fadeIn();
                }
            });
            
            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

            // Editors

            var $automation_editor = $popup.find('textarea[name=automation_kata]')
                .cerbCodeEditor()
                .nextAll('pre.ace_editor')
            ;

            var automation_editor = ace.edit($automation_editor.attr('id'));
            
            var $fieldset_resource_get = $popup.find('[data-cerb-event-resource-get]');

            var doneFunc = function(e) {
                e.stopPropagation();

                var $target = e.trigger;

                if(!$target.is('.cerb-bot-trigger'))
                    return;

                if (e.eventData.exit === 'error') {

                } else if(e.eventData.exit === 'return') {
                    Devblocks.interactionWorkerPostActions(e.eventData, automation_editor);
                }
            };

            var $toolbar = $fieldset_resource_get.find('.cerb-code-editor-toolbar')
                .cerbToolbar({
                    caller: {
                        name: 'cerb.toolbar.eventHandlers.editor',
                        params: {
                            selected_text: ''
                        }
                    },
                    width: '75%',
                    start: function(formData) {
                        var pos = automation_editor.getCursorPosition();
                        var token_path = Devblocks.cerbCodeEditor.getKataTokenPath(pos, automation_editor).join('');

                        formData.set('caller[params][selected_text]', automation_editor.getSelectedText());
                        formData.set('caller[params][token_path]', token_path);
                        formData.set('caller[params][cursor_row]', pos.row);
                        formData.set('caller[params][cursor_column]', pos.column);
                        formData.set('caller[params][trigger]', 'cerb.trigger.resource.get');
                        formData.set('caller[params][value]', automation_editor.getValue());
                    },
                    done: doneFunc
                })
            ;

            $toolbar.cerbCodeEditorToolbarEventHandler({
                editor: automation_editor
            });
        });
    });
</script>
