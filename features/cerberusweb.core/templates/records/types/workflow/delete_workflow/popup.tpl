{$popup_id = uniqid('popup')}

<h1>{$model->name}</h1>

<form id="{$popup_id}">
    <input type="hidden" name="id" value="{$model->id}">

    <div id="{$popup_id}Tabs">
        <ul style="display:none;">
            <li><a href="#{$popup_id}TabsChanges">{'common.changes'|devblocks_translate|capitalize}</a></li>
        </ul>

        <div id="{$popup_id}TabsChanges">
            {if $rows}
                {include file="devblocks:cerberusweb.core::ui/sheets/render.tpl"}
            {else}
                (no changes)
            {/if}

            <fieldset style="display:none;" class="delete">
                <legend>{'common.delete'|devblocks_translate|capitalize}</legend>

                <div>
                    Are you sure you want to permanently delete this workflow and all of its resources?
                </div>

                <button type="button" data-cerb-button-continue class="red">{'common.yes'|devblocks_translate|capitalize}</button>
                <button type="button" class="delete-cancel">{'common.no'|devblocks_translate|capitalize}</button>
            </fieldset>

            <div class="buttons" style="margin-top:0.5em;">
                <button type="button" class="delete-prompt"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {{'common.continue'|devblocks_translate|capitalize}}</button>
            </div>
        </div>
    </div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    let $frm = $('#{$popup_id}');
    let $tabs = $('#{$popup_id}Tabs');
    let $popup = genericAjaxPopupFind($frm);

    Devblocks.formDisableSubmit($frm);

    $popup.one('popup_open', function() {
        $popup.dialog('option','title','Delete Workflow');

        // Tabs

        $tabs.tabs({
            hide: { effect: "slide", direction: "up", duration: 250 },
            show: { effect: "slide", direction: "down", duration: 250 }
        });

        let $tab_changes = $('#{$popup_id}TabsChanges');

        // $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
        $popup.find('button.delete-prompt').click(Devblocks.callbackPeekEditDeletePrompt);
        $popup.find('button.delete-cancel').click(Devblocks.callbackPeekEditDeleteCancel);

        $tab_changes.find('[data-cerb-button-continue]').on('click', function(e) {
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