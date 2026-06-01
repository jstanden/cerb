<form id="frmSetupTeamConfig" action="{devblocks_url}{/devblocks_url}" method="post">
    <input type="hidden" name="c" value="config">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="team">
    <input type="hidden" name="action" value="saveConfigJson">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <fieldset>
        <legend>When creating a new worker:</legend>

        <div>
            <b>Add these pages to their default menu:</b><br>
            <button type="button" class="cerb-abstract-chooser" data-context="{CerberusContexts::CONTEXT_WORKSPACE_PAGE}" data-field-name="default_pages[]"><span class="cerb-icons cerb-icon-search"></span></button>
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
    </fieldset>

    <button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
    let $frm = $('#frmSetupTeamConfig');

    Devblocks.formDisableSubmit($frm);

    $frm.find('.cerb-abstract-chooser')
        .cerbChooserTrigger()
    ;

    $frm.find('BUTTON.submit')
        .click(function(e) {
            e.stopPropagation();
            Devblocks.saveAjaxForm($frm);
        })
    ;
});
</script>