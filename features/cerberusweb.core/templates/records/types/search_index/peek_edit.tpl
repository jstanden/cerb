{$peek_context = 'cerb.contexts.search.index'}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="search_index">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
    <input type="hidden" name="do_delete" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <table cellspacing="0" cellpadding="2" border="0" width="98%">
        <tr>
            <td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
            <td width="99%">
                <input type="text" name="name" value="{$model->name}" placeholder="Example Docs" style="width:98%;" autofocus="autofocus">
            </td>
        </tr>

        <tr>
            <td width="1%" valign="top" nowrap="nowrap"><b>{'common.uri'|devblocks_translate}:</b></td>
            <td width="99%">
                <input type="text" name="uri" value="{$model->uri}" placeholder="(example.text)" style="width:20em;" spellcheck="false"> <small>(letters, numbers, and dots; globally unique)</small>
            </td>
        </tr>

        <tr>
            <td width="1%" valign="top" nowrap="nowrap"><b>Filter Name:</b></td>
            <td width="99%">
                <input type="text" name="record_filter" value="{$model->record_filter}" placeholder="(text)" style="width:20em;" spellcheck="false"> <small>(letters, numbers, and dots; unique per record type; blank to omit)</small>
            </td>
        </tr>

        <tr>
            <td width="1%" nowrap="nowrap"><b>{'common.priority'|devblocks_translate|capitalize}:</b></td>
            <td width="99%">
                <input type="text" name="priority" maxlength="3" size="3" value="{$model->priority|default:100}">
                <span>
					(0 = first/default, 255 = last)
				</span>
            </td>
        </tr>

        <tr>
            <td width="1%" nowrap="nowrap" valign="top">
                <b>{'common.record.type'|devblocks_translate|capitalize}:</b><br>
            </td>
            <td width="99%">
                {if $model && $model->record_type}
                    <div class="bubble">{$model->record_type|capitalize}</div>
                    <input type="hidden" name="record_type" value="{$model->record_type}">
                {else}
                    <select name="record_type">
                        <option value=""></option>
                        {foreach from=$contexts item=ctx key=k}
                            <option value="{$ctx->params.alias}" {if $model->record_type==$ctx->params.alias}selected="selected"{/if}>{$ctx->name}</option>
                        {/foreach}
                    </select>
                {/if}
            </td>
        </tr>

        <tr>
            <td width="1%" nowrap="nowrap" align="top">
                <b>{'common.type'|devblocks_translate|capitalize}:</b>
            </td>
            <td width="99%">
                {if $model}
                    <div class="bubble">
                        {if $search_extension}
                            {$search_extension->manifest->name}
                        {else}
                            {$model->extension_id}
                        {/if}
                    </div>
                    <input type="hidden" name="extension_id" value="{$model->extension_id}"
                {else}
                    <select name="extension_id">
                        <option value=""></option>
                        {if !empty($search_extensions)}
                            {foreach from=$search_extensions item=search_ext}
                                <option value="{$search_ext->id}">{$search_ext->name}</option>
                            {/foreach}
                        {/if}
                    </select>
                {/if}
            </td>
        </tr>

        {if !empty($custom_fields)}
            {include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
        {/if}
    </table>

    <div class="search-index-params">
        {if $search_extension}
            {$search_extension->renderConfig($model)}
        {/if}
    </div>

    {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

    {if !empty($model->id)}
        <fieldset style="display:none;" class="delete">
            <legend>{'common.delete'|devblocks_translate|capitalize}</legend>

            <div>
                Are you sure you want to permanently delete this search index and all of its documents?
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
            $popup.dialog('option','title',"{'Search Index'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.find('[autofocus]:first').focus();
            $popup.css('overflow', 'inherit');

            let $record_type = $popup.find('[name=record_type]');
            let $extension = $popup.find('select[name=extension_id]');
            let $params = $popup.find('.search-index-params');

            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete-prompt').click(Devblocks.callbackPeekEditDeletePrompt);
            $popup.find('button.delete-cancel').click(Devblocks.callbackPeekEditDeleteCancel);

            // Change the params when the extension changes (create only)
            {if !$model->id}
            $extension.on('change', function(e) {
                e.stopPropagation();
                let context = $record_type.val();
                let extension_id = $extension.val();

                if('' === extension_id) {
                    $params.html('');
                    return;
                }

                let $spinner = Devblocks.getSpinner();
                $params.html('').append($spinner, extension_id, ' ', context);

                let formData = new FormData();
                formData.set('c', 'profiles');
                formData.set('a', 'invoke');
                formData.set('module', 'search_index');
                formData.set('action', 'getExtensionConfig');
                formData.set('extension_id', extension_id);
                formData.set('record_type', context);

                genericAjaxPost(formData, $params);
            });
            {/if}

            // Change the query context when the dropdown changes

            $record_type.on('change', function(e) {
                e.stopPropagation();
                let record_type = $record_type.val();

                // Update extension params when context changes
                $params.trigger('cerb-search-index-params-change-context', record_type);
            });
        });
    });
</script>
