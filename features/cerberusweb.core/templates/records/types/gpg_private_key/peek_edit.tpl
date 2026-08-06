{$peek_context = CerberusContexts::CONTEXT_GPG_PRIVATE_KEY}
{$peek_context_id = $model->id}
{$tabs_id = uniqid('tabs')}

<div id="{$tabs_id}" class="cerb-tabs">
    {if !$model->id}
        <ul>
            <li><a href="#privkey-import_{$tabs_id}">{'common.import'|devblocks_translate|capitalize}</a></li>
            <li><a href="#privkey-generate_{$tabs_id}">{'common.create'|devblocks_translate|capitalize}</a></li>
        </ul>
    {/if}

    <div id="privkey-import_{$tabs_id}">
        <form action="{devblocks_url}{/devblocks_url}" method="post">
            <input type="hidden" name="c" value="profiles">
            <input type="hidden" name="a" value="invoke">
            <input type="hidden" name="module" value="gpg_private_key">
            <input type="hidden" name="action" value="savePeekJson">
            <input type="hidden" name="view_id" value="{$view_id}">
            {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
            <input type="hidden" name="do_delete" value="0">
            <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

            <div class="cerb-ui-panel cerb-ui-panel--spaced">
                <div class="cerb-ui-form">
                    {if $model->id}
                        <div class="cerb-ui-form--field">
                            <label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
                            <input type="text" name="name" value="{$model->name}" autofocus="autofocus">
                        </div>
                    {/if}

                    <div class="cerb-ui-form--field">
                        <label class="cerb-ui-form--label">{'common.key'|devblocks_translate|capitalize}</label>
                        <textarea name="key_text" autofocus="autofocus" style="height:150px;" placeholder="----- BEGIN PGP PRIVATE KEY BLOCK ..." spellcheck="false"></textarea>
                    </div>

                    <div class="cerb-ui-form--field">
                        <label class="cerb-ui-form--label">{'common.passphrase'|devblocks_translate|capitalize} <span class="cerb-ui-form--hint">{'common.optional'|devblocks_translate|lower}</span></label>
                        <input type="password" name="passphrase" value="" autocomplete="off" spellcheck="false" placeholder="••••••••">
                    </div>

                    {if !empty($custom_fields)}
                    {include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
                    {/if}
                </div>
            </div>

            {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

            {if !empty($model->id)}
                {include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="PGP private key"}
            {/if}

            <div class="buttons" style="margin-top:10px;">
                {if $model->id}
                    <button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
                    {if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
                {else}
                    <button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
                {/if}
            </div>
        </form>
    </div>

    {if !$model->id}
        <div id="privkey-generate_{$tabs_id}">
            <form action="{devblocks_url}{/devblocks_url}" method="post">
                <input type="hidden" name="c" value="profiles">
                <input type="hidden" name="a" value="invoke">
                <input type="hidden" name="module" value="gpg_private_key">
                <input type="hidden" name="action" value="generateJson">
                <input type="hidden" name="view_id" value="{$view_id}">
                <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

                <div class="cerb-ui-panel cerb-ui-panel--spaced">
                    <div class="cerb-ui-header cerb-ui-header--tight">
                        <div class="cerb-ui-header--title-sm">{'common.key'|devblocks_translate|capitalize}</div>
                    </div>
                    <div class="cerb-ui-form">
                        <div class="cerb-ui-form--row">
                            <div class="cerb-ui-form--field">
                                <label class="cerb-ui-form--label">Bits</label>
                                <select name="key_length">
                                    <option value="512">512</option>
                                    <option value="1048">1048</option>
                                    <option value="2048" selected="selected">2048</option>
                                    <option value="3072">3072</option>
                                    <option value="4096">4096</option>
                                </select>
                            </div>
                            <div class="cerb-ui-form--field">
                                <label class="cerb-ui-form--label">Algorithm</label>
                                <div class="cerb-u-flex cerb-u-items-center" style="min-height:2em;">RSA</div>
                            </div>
                            <div class="cerb-ui-form--field">
                                <label class="cerb-ui-form--label">Hash Algorithm</label>
                                <select name="hash_algorithm">
                                    <option value="SHA224">SHA224</option>
                                    <option value="SHA256" selected="selected">SHA256</option>
                                    <option value="SHA384">SHA384</option>
                                    <option value="SHA512">SHA512</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cerb-ui-panel cerb-ui-panel--spaced">
                    <div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
                        <div class="cerb-ui-header--title-sm">User IDs</div>
                        <div class="cerb-ui-header--right">
                            <button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-button="uid-add"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button>
                        </div>
                    </div>

                    <div class="cerb-ui-form" data-cerb-id="uid-rows">
                        <div class="cerb-ui-form--row cerb-u-items-end">
                            <div class="cerb-ui-form--field">
                                <label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
                                <input type="text" name="uid_names[]" value="" placeholder="e.g. Example, Inc.">
                            </div>
                            <div class="cerb-ui-form--field">
                                <label class="cerb-ui-form--label">{'common.email'|devblocks_translate|capitalize}</label>
                                <input type="text" name="uid_emails[]" value="" placeholder="support@example.com">
                            </div>
                        </div>
                    </div>

                    {* Hidden template row cloned by the "Add" button (includes a remove button). *}
                    <div data-cerb-id="uid-template" style="display:none;">
                        <div class="cerb-ui-form--row cerb-u-items-end">
                            <div class="cerb-ui-form--field">
                                <label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
                                <input type="text" name="uid_names[]" value="" placeholder="e.g. Example, Inc.">
                            </div>
                            <div class="cerb-ui-form--field">
                                <label class="cerb-ui-form--label">{'common.email'|devblocks_translate|capitalize}</label>
                                <input type="text" name="uid_emails[]" value="" placeholder="support@example.com">
                            </div>
                            <div class="cerb-ui-form--field" style="flex:0 0 auto;">
                                <button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-button="uid-remove"><span class="cerb-icons cerb-icon-circle-minus"></span></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="buttons" style="margin-top:10px;">
                    <button type="button" class="cerb-ui-button generate"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
                </div>
            </form>
        </div>
    {/if}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
    $(function() {
        let $tabs = $('#{$tabs_id}');
        let $popup = genericAjaxPopupFind($tabs);

        Devblocks.formDisableSubmit($popup);

        $popup.one('popup_open', function(event,ui) {
            $popup.dialog('option','title',"{'Gpg Private Key'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.css('overflow', 'inherit');

            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
            if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);
            $popup.find('button.generate').click(Devblocks.callbackPeekEditSave);

            let $uid_rows = $popup.find('[data-cerb-id=uid-rows]');
            let $uid_template = $popup.find('[data-cerb-id=uid-template]');

            $popup.find('button[data-cerb-button=uid-add]').on('click', function() {
                $uid_rows.append($uid_template.find('> div').clone());
            });

            $uid_rows.on('click', 'button[data-cerb-button=uid-remove]', function() {
               $(this).closest('.cerb-ui-form--row').remove();
            });

            // Tabs

            $tabs.find('> ul').each(function() { if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this); });
        });
    });
</script>
