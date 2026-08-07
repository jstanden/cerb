{$popup_id = uniqid('popup')}

<div class="cerb-ui-header">
    <div>
        <div class="cerb-ui-header--title">{$model->name}</div>
    </div>
</div>

<form id="{$popup_id}">
    <input type="hidden" name="id" value="{$model->id}">

    {if $rows}
    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-header cerb-ui-header--tight">
            <div class="cerb-ui-header--title-sm">{'common.changes'|devblocks_translate|capitalize}</div>
        </div>

        {include file="devblocks:cerberusweb.core::ui/sheets/render.tpl" layout=$layout columns=$columns rows=$rows}
    </div>
    {/if}

    <div class="cerb-ui-panel cerb-ui-panel--alert cerb-ui-panel--spaced{if $rows} cerb-u-mt-4{/if}">
        <div class="cerb-ui-header">
            <div class="cerb-ui-callout">
                <span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
                <div>
                    <div class="cerb-ui-header--title-sm">{'common.delete'|devblocks_translate|capitalize}</div>
                    <div class="cerb-ui-header--subtitle">Permanently delete this workflow and all of its resources? This can't be undone.</div>
                </div>
            </div>
            <div class="cerb-ui-header--right">
                <button type="button" class="cerb-ui-button" data-cerb-button-delete><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>
            </div>
        </div>
    </div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    let $frm = $('#{$popup_id}');
    let $popup = genericAjaxPopupFind($frm);

    Devblocks.formDisableSubmit($frm);

    $popup.one('popup_open', function() {
        $popup.dialog('option','title','Delete Workflow');

        $frm.find('[data-cerb-button-delete]').on('click', function(e) {
            e.stopPropagation();

            let formData = new FormData($frm[0]);
            formData.set('c', 'profiles');
            formData.set('a', 'invoke');
            formData.set('module', 'workflow');
            formData.set('action', 'saveChangesJson');
            formData.set('delete', '1');

            Devblocks.clearAlerts();

            genericAjaxPost(formData, null, null, function(json) {
                if('object' == typeof json && json.hasOwnProperty('success')) {
                    genericAjaxPopupClose($popup, 'workflow_delete');

                    // [TODO] Refresh worklist?

                } else {
                    if('object' == typeof json && json.hasOwnProperty('error')) {
                        Devblocks.createAlertError(json.error);
                    } else {
                        Devblocks.createAlertError('An unexpected error occurred.');
                    }
                }
            });
        });
    });
});
</script>