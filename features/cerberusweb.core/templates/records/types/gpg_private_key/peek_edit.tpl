{$peek_context = Context_GpgPrivateKey::ID}
{$peek_context_id = $model->id}
{$tabs_id = uniqid('tabs')}

<div id="{$tabs_id}" class="cerb-tabs">
    {if !$model->id}
        <ul>
            <li><a href="#privkey-import">{'common.import'|devblocks_translate|capitalize}</a></li>
            <li><a href="#privkey-generate">{'common.create'|devblocks_translate|capitalize}</a></li>
        </ul>
    {/if}

    <div id="privkey-import">
        <form action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
            <input type="hidden" name="c" value="profiles">
            <input type="hidden" name="a" value="invoke">
            <input type="hidden" name="module" value="gpg_private_key">
            <input type="hidden" name="action" value="savePeekJson">
            <input type="hidden" name="view_id" value="{$view_id}">
            {if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
            <input type="hidden" name="do_delete" value="0">
            <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

            <table cellspacing="0" cellpadding="2" border="0" width="98%">
                {if $model->id}
                    <tr>
                        <td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
                        <td width="99%">
                            <input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
                        </td>
                    </tr>
                {/if}
                <tr>
                    <td width="1%" valign="top" nowrap="nowrap"><b>{'common.key'|devblocks_translate|capitalize}:</b></td>
                    <td width="99%">
                        <textarea name="key_text" autofocus="autofocus" style="width:100%;height:150px;" placeholder="----- BEGIN PGP PRIVATE KEY BLOCK ..."></textarea>
                    </td>
                </tr>
                <tr>
                    <td width="1%" nowrap="nowrap"><b>{'common.passphrase'|devblocks_translate|capitalize}:</b></td>
                    <td width="99%">
                        <input type="password" name="passphrase" value="" autocomplete="off" spellcheck="false" style="width:98%;" placeholder="({'common.optional'|devblocks_translate|lower})">
                    </td>
                </tr>

                {if !empty($custom_fields)}
                    {include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
                {/if}
            </table>

            {include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

            {if !empty($model->id)}
                <fieldset style="display:none;" class="delete">
                    <legend>{'common.delete'|devblocks_translate|capitalize}</legend>

                    <div>
                        Are you sure you want to permanently delete this PGP private key?
                    </div>

                    <button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
                    <button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
                </fieldset>
            {/if}

            <div class="buttons" style="margin-top:10px;">
                {if $model->id}
                    <button type="button" class="save"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
                    {if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
                {else}
                    <button type="button" class="save"><span class="glyphicons glyphicons-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
                {/if}
            </div>
        </form>
    </div>

    {if !$model->id}
        <div id="privkey-generate">
            <form action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
                <input type="hidden" name="c" value="profiles">
                <input type="hidden" name="a" value="invoke">
                <input type="hidden" name="module" value="gpg_private_key">
                <input type="hidden" name="action" value="generateJson">
                <input type="hidden" name="view_id" value="{$view_id}">
                <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

                <fieldset>
                    <legend>Key Length (bits)</legend>

                    <select name="key_length">
                        <option value="512">512</option>
                        <option value="1048">1048</option>
                        <option value="2048" selected="selected">2048</option>
                        <option value="3072">3072</option>
                        <option value="4096">4096</option>
                    </select>
                </fieldset>

                <fieldset>
                    <legend>User IDs</legend>

                    <table cellspacing="5" cellpadding="0" border="0" width="100%">
                        <thead>
                            <tr>
                                <td style="font-weight:bold;">{'common.name'|devblocks_translate|capitalize}</td>
                                <td style="font-weight:bold;">{'common.email'|devblocks_translate|capitalize}</td>
                            </tr>
                        </thead>
                        <tbody data-cerb-id="uid-rows">
                            <tr>
                                <td>
                                    <input type="text" name="uid_names[]" value="" placeholder="e.g. Example, Inc." style="width:100%">
                                </td>
                                <td>
                                    <input type="text" name="uid_emails[]" value="" placeholder="support@example.com" style="width:100%">
                                </td>
                            </tr>
                        </tbody>
                        <tbody data-cerb-id="uid-template" style="display:none;">
                            <tr>
                                <td>
                                    <input type="text" name="uid_names[]" value="" placeholder="e.g. Example, Inc." style="width:100%">
                                </td>
                                <td>
                                    <input type="text" name="uid_emails[]" value="" placeholder="support@example.com" style="width:100%">
                                </td>
                                <td>
                                    <button type="button" data-cerb-button="uid-remove"><span class="glyphicons glyphicons-circle-minus"></span></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <button type="button" data-cerb-button="uid-add"><span class="glyphicons glyphicons-circle-plus"></span></button>
                </fieldset>

                <button type="button" class="generate"><span class="glyphicons glyphicons-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
            </form>
        </div>
    {/if}
</div>

<script type="text/javascript">
    $(function() {
        var $tabs = $('#{$tabs_id}');
        var $popup = genericAjaxPopupFind($tabs);

        $popup.one('popup_open', function(event,ui) {
            $popup.dialog('option','title',"{'Gpg Private Key'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
            $popup.css('overflow', 'inherit');

            // Buttons

            $popup.find('button.save').click(Devblocks.callbackPeekEditSave);
            $popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
            $popup.find('button.generate').click(Devblocks.callbackPeekEditSave);

            var $uid_rows = $popup.find('tbody[data-cerb-id=uid-rows]');
            var $uid_template = $popup.find('tbody[data-cerb-id=uid-template]');

            $popup.find('button[data-cerb-button=uid-add]').on('click', function() {
                $uid_rows.append($uid_template.find('> tr').clone());
            });

            $uid_rows.on('click', 'button[data-cerb-button=uid-remove]', function() {
               $(this).closest('tr').remove();
            });

            // Tabs

            $tabs.tabs();

            // Choosers

            $popup.find('.chooser-abstract').cerbChooserTrigger();

            // Close confirmation

            $popup.on('dialogbeforeclose', function(e, ui) {
                var keycode = e.keyCode || e.which;
                if(keycode == 27)
                    return confirm('{'warning.core.editor.close'|devblocks_translate}');
            });
        });
    });
</script>
