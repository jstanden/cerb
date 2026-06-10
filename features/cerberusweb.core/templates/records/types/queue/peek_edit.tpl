{$peek_context = CerberusContexts::CONTEXT_QUEUE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
    <input type="hidden" name="c" value="profiles">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="queue">
    <input type="hidden" name="action" value="savePeekJson">
    <input type="hidden" name="view_id" value="{$view_id}">
    {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
    <input type="hidden" name="do_delete" value="0">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <table cellspacing="0" cellpadding="2" border="0" width="98%">
        <tr>
            <td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
            <td width="99%">
                <input type="text" name="name" value="{$model->name}" placeholder="(example.queue.name)" style="width:98%;" autofocus="autofocus" spellcheck="false">
            </td>
        </tr>

        <tr>
            <td width="1%" nowrap="nowrap" align="top">
                <b>{'common.queue.consumer'|devblocks_translate|capitalize}:</b>
            </td>
            <td width="99%">
                {if $model}
                    <div class="bubble">
                        {if $queue_extension}
                            {$queue_extension->manifest->name}
                        {else}
                            {$model->extension_id}
                        {/if}
                    </div>
                    <input type="hidden" name="extension_id" value="{$model->extension_id}"
                {else}
                    <select name="extension_id">
                        <option value=""></option>
                        {if !empty($queue_extensions)}
                            {foreach from=$queue_extensions item=queue_ext}
                                <option value="{$queue_ext->id}">{$queue_ext->name}</option>
                            {/foreach}
                        {/if}
                    </select>
                {/if}
            </td>
        </tr>

        <tr>
            <td width="1%" nowrap="nowrap"><b>Retries:</b></td>
            <td width="99%">
                <input type="number" name="retry_max" value="{$model->retry_max|default:0}" min="0" max="16" style="width:5em;">
                <small>max attempts (0 = never retry) over a window of</small>
                <input type="number" name="retry_window_secs" value="{$model->retry_window_secs|default:86400}" min="0" style="width:7em;">
                <small>seconds</small>
            </td>
        </tr>

        {if !empty($custom_fields)}
            {include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
        {/if}
    </table>

    <div class="queue-consumer-params">
        {if $queue_extension}
            {$queue_extension->renderConfig($model)}
        {/if}
    </div>

    {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

    {if !empty($model->id)}
        <fieldset style="display:none;" class="delete">
            <legend>{'common.delete'|devblocks_translate|capitalize}</legend>

            <div>
                Are you sure you want to permanently delete this queue and its messages?
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
            $popup.dialog('option','title',"{'common.queue'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.css('overflow', 'inherit');

            let $extension = $popup.find('select[name=extension_id]');
            let $params = $popup.find('.queue-consumer-params');

            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.delete-prompt').click(Devblocks.callbackPeekEditDeletePrompt);
            $popup.find('button.delete-cancel').click(Devblocks.callbackPeekEditDeleteCancel);

            // Load the consumer's config form when the extension changes (create only)
            {if !$model->id}
            $extension.on('change', function(e) {
                e.stopPropagation();
                let extension_id = $extension.val();

                if('' === extension_id) {
                    $params.html('');
                    return;
                }

                let $spinner = Devblocks.getSpinner();
                $params.html('').append($spinner);

                let formData = new FormData();
                formData.set('c', 'profiles');
                formData.set('a', 'invoke');
                formData.set('module', 'queue');
                formData.set('action', 'getExtensionConfig');
                formData.set('extension_id', extension_id);

                genericAjaxPost(formData, $params);
            });
            {/if}
        });
    });
</script>
