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
                <button type="button" class="cerb-abstract-chooser" data-context="{$workspace_page_context}" data-field-name="default_pages[]"><span class="cerb-icons cerb-icon-search"></span></button>
                <ul class="bubbles chooser-container" style="display:inline-block;">
                    {if $default_workspaces}
                        {foreach from=$default_workspaces item=workspace}
                        <li>
                            {$workspace->name}
                            <input type="hidden" name="default_pages[]" value="{$workspace->id}">
                        </li>
                        {/foreach}
                    {/if}
                </ul>
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
    $frm.find('.cerb-abstract-chooser').cerbChooserTrigger();

    $frm.find('#btnSaveTeamConfig').on('click', function(e) {
        e.stopPropagation();
        Devblocks.saveAjaxForm($frm);
    });
});
</script>
