{$peek_context = CerberusContexts::CONTEXT_WORKFLOW}
{$peek_context_id = $model->id}
{$form_id = uniqid('form')}
{$tabset_id = uniqid('tabs')}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="workflow">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    <input type="hidden" name="id" value="{if $model}{$model->id}{/if}">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <div id="{$tabset_id}" class="cerb-tabs">
        {if !$model->id && $packages}
            <ul>
                <li><a href="#workflow-library">{'common.library'|devblocks_translate|capitalize}</a></li>
                <li><a href="#workflow-builder">{'common.build'|devblocks_translate|capitalize}</a></li>
            </ul>
        {/if}

        {if !$model->id && $packages}
            <div id="workflow-library" class="package-library">
                {include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
            </div>
        {/if}

        <div id="workflow-builder">
        <table cellspacing="0" cellpadding="2" border="0" width="98%">
            {if $model->name}
                <h1>{$model->name}</h1>
                <div>{$model->description}</div>
            {/if}

            {if !empty($custom_fields)}
                {include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
            {/if}
        </table>

        {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

        {if $model->id}
        <div class="cerb-code-editor-toolbar" style="margin:0.5em 0;">
            {if $model->config_kata}
            <button type="button" data-cerb-button-config-update data-cerb-template-section="config"><span class="cerb-icons cerb-icon-adjust"></span> Edit Configuration</button>
            {/if}
            <button type="button" data-cerb-button-template-update><span class="cerb-icons cerb-icon-file-import"></span> Update Template</button>
        </div>
        {else}
            {if $templates_layout.filtering}
                <div style="position:relative;box-sizing:border-box;width:100%;border:1px solid var(--cerb-color-background-contrast-220);border-radius:10px;padding:0 5px;margin-bottom:5px;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" style="width:16px;height:16px;top:3px;position:absolute;fill:var(--cerb-color-background-contrast-180);">
                        <path d="M27.207,24.37866,20.6106,17.78235a9.03069,9.03069,0,1,0-2.82825,2.82825L24.37878,27.207a1,1,0,0,0,1.41425,0l1.414-1.41418A1,1,0,0,0,27.207,24.37866ZM13,19a6,6,0,1,1,6-6A6.00657,6.00657,0,0,1,13,19Z"/>
                    </svg>
                    <input data-cerb-sheet-query type="text" value="{$filter}" placeholder="Search" style="border:0;background-color:inherit;outline:none;margin-left:16px;width:calc(100% - 16px);" autofocus="autofocus" spellcheck="false">
                </div>
            {/if}

            {if $templates_layout && $templates_rows}
                <div>
                    {if 'fieldsets' == $templates_layout.style}
                        {include file="devblocks:cerberusweb.core::ui/sheets/render_fieldsets.tpl" layout=$templates_layout columns=$templates_columns rows=$templates_rows}
                    {elseif in_array($templates_layout.style, ['columns','grid'])}
                        {include file="devblocks:cerberusweb.core::ui/sheets/render_grid.tpl" layout=$templates_layout columns=$templates_columns rows=$templates_rows}
                    {else}
                        {include file="devblocks:cerberusweb.core::ui/sheets/render.tpl" layout=$templates_layout columns=$templates_columns rows=$templates_rows}
                    {/if}
                </div>
            {/if}
        {/if}

        <div data-cerb-summary>
        {include file="devblocks:cerberusweb.core::records/types/workflow/peek_edit/summary.tpl"}
        </div>

        <div class="buttons" style="margin-top:10px;">
            {if $model->id}
                <button type="button" class="save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
                <button type="button" class="save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
                {if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
            {else}
                <button type="button" class="create-library" style="display:none;"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
                <button type="button" class="create"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.create_and_continue'|devblocks_translate|capitalize}</button>
            {/if}
        </div>
    </div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
    $(function() {
        let $frm = $('#{$form_id}');
        let $popup = genericAjaxPopupFind($frm);

        Devblocks.formDisableSubmit($frm);

        $popup.one('popup_open', function() {
            $popup.dialog('option','title',"{'common.workflow'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.find('[autofocus]:first').focus();
            $popup.css('overflow', 'inherit');

            let $tab_builder = $popup.find('#workflow-builder');

            let funcAfter = function(e) {
                let popup_url = 'c=internal&a=invoke&module=records&action=showPeekPopup' +
                    '&context=' + encodeURIComponent(e.context) +
                    '&context_id=' + encodeURIComponent(e.id) +
                    '&view_id=' + encodeURIComponent(e.view_id) +
                    '&edit=true'
                ;

                let $new_popup = genericAjaxPopup('editor' + Devblocks.uniqueId(), popup_url, null, null, '50%');

                $new_popup.one('popup_open', function(evt) {
                    evt.stopPropagation();
                    setTimeout(function() {
                        $new_popup.find('button[data-cerb-button-template-update]').click();
                    }, 50);
                });
            };

            // Buttons
            $tab_builder.find('button.save').click(Devblocks.callbackPeekEditSave);
            $tab_builder.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            $tab_builder.find('button.create').click({ mode: 'create', after: funcAfter }, Devblocks.callbackPeekEditSave);

            let onButtonTemplateUpdate = function(e) {
                e.preventDefault();
                e.stopPropagation();

                let $button = $(this);
                let section = $button.attr('data-cerb-template-section');

                let model_id = $frm.find('input[name=id]').val();
                if(!model_id) return;

                let formData = new FormData();
                formData.set('c', 'profiles');
                formData.set('a', 'invoke');
                formData.set('module', 'workflow');
                formData.set('action', 'showTemplateUpdatePopup');
                formData.set('id', model_id);

                if(section)
                    formData.set('section', section);

                let $update_popup = genericAjaxPopup('workflowTemplate', formData, '', '', '80%');

                $update_popup.on('template_updated', function(evt) {
                    evt.stopPropagation();

                    var layer = $popup.attr('data-layer');
                    var popup_url = 'c=internal&a=invoke&module=records&action=showPeekPopup' +
                        '&context=' + encodeURIComponent('workflow') +
                        '&context_id=' + encodeURIComponent(model_id) +
                        '&view_id=' + encodeURIComponent("{$view_id}") +
                        '&edit=true'
                    ;

                    // Body snatch
                    var $new_popup = genericAjaxPopup(layer, popup_url, 'reuse', false);
                    $new_popup.focus();
                });
            };

            $tab_builder.find('button[data-cerb-button-config-update').on('click', onButtonTemplateUpdate);
            $tab_builder.find('button[data-cerb-button-template-update').on('click', onButtonTemplateUpdate);

            let $template_cells = $tab_builder.find('.cerb-sheet--row-item');

            {if !$model->id && $templates_layout.filtering}
            $tab_builder.find('[data-cerb-sheet-query]').on('keyup', $.debounce(250, function(e) {
                e.stopPropagation();
                e.preventDefault();

                if(13 === e.which)
                    return;

                let term = $tab_builder.find('[data-cerb-sheet-query]').val();

                $template_cells.each(function() {
                    let $this = $(this);

                    if($this.text().toLowerCase().indexOf(term) > -1) {
                        $this.parent().show();
                    } else {
                        $this.parent().hide();
                    }
                });
            }));
            {/if}

            {if $model->id}
            $tab_builder.find('button.delete-prompt').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let formData = new FormData();
                formData.set('c', 'profiles');
                formData.set('a', 'invoke');
                formData.set('module', 'workflow');
                formData.set('action', 'showWorkflowDeletePopup');
                formData.set('id', '{$model->id}');

                let $delete_popup = genericAjaxPopup('workflowDelete', formData, '', '', '80%');

                $delete_popup.on('workflow_delete', function(evt) {
                    evt.stopPropagation();

                    {if $view_id}
                    genericAjaxGet('view{$view_id}', 'c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
                    {/if}

                    genericAjaxPopupClose($popup, 'peek_deleted');
                });
            });
            {/if}

            // Package Library

            {if !$model->id && $packages}
            let tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
            tabOptions.active = Devblocks.getjQueryUiTabSelected('{$tabset_id}');

            let $tabs = $popup.find('.cerb-tabs').tabs(tabOptions);

            let $library_container = $tabs;
            {include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}

            $library_container.on('cerb-package-library-form-submit', function(e) {
                e.stopPropagation();

                $tab_builder.find('button.create-library')
                    .off('click')
                    .click(
                        {
                            mode: 'continue',
                            after: function(evt) {
                                $library_container.triggerHandler('cerb-package-library-form-submit--done');

                                if(evt.hasOwnProperty('error')) {
                                    return;
                                }

                                let layer = $popup.attr('data-layer');
                                let popup_url = 'c=internal&a=invoke&module=records&action=showPeekPopup' +
                                    '&context=' + encodeURIComponent(evt.context) +
                                    '&context_id=' + encodeURIComponent(evt.id) +
                                    '&view_id=' + encodeURIComponent(evt.view_id) +
                                    '&edit=true'
                                ;
                                let $new_popup = genericAjaxPopup(layer, popup_url, 'reuse', false);

                                setTimeout(function() {
                                    $new_popup.find('button[data-cerb-button-template-update]').click();
                                }, 50);
                            }
                        },
                        Devblocks.callbackPeekEditSave
                    )
                    .click()
                ;
            });
            {/if}
        });
    });
</script>
