<form id="frmSetupTeamConfig" action="{devblocks_url}{/devblocks_url}" method="post" class="cerb-ui-form">
    <input type="hidden" name="c" value="config">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="team">
    <input type="hidden" name="action" value="saveConfigJson">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-header cerb-ui-header--tight">
            <div class="cerb-ui-header--title-sm">When creating a new worker</div>
        </div>

        <div class="cerb-ui-form--field">
            <label class="cerb-ui-form--label">Add these pages to their default menu</label>
            <div>
                <div class="cerb-ui-record-chooser cerb-pages-chooser">
                    {if $default_workspaces}
                        {foreach from=$default_workspaces item=workspace}
                        <li data-context="{$workspace_page_context}" data-context-id="{$workspace->id}" data-label="{$workspace->name}"></li>
                        {/foreach}
                    {/if}
                </div>
            </div>
        </div>
    </div>

    <div>
        <button type="button" id="btnSaveTeamConfig" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
    </div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
$(function() {
    const $frm = $('#frmSetupTeamConfig');

    Devblocks.formDisableSubmit($frm);

    if(window.CerbUI && CerbUI.RecordChooser)
        $frm.find('.cerb-pages-chooser').each(function() {
            new CerbUI.RecordChooser(this, { context: '{$workspace_page_context}', name: 'default_pages', multiple: true });
        });

    $frm.find('#btnSaveTeamConfig').on('click', function(e) {
        e.stopPropagation();
        Devblocks.saveAjaxForm($frm);
    });
});
</script>
