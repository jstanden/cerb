<form id="frmSetupMailHtml" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
    <input type="hidden" name="c" value="config">
    <input type="hidden" name="a" value="invoke">
    <input type="hidden" name="module" value="mail_incoming">
    <input type="hidden" name="action" value="saveMailHtmlJson">
    <input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

    <fieldset>
        <legend>Images</legend>

        <div style="margin-bottom:15px;">
            <p>
                Remote images in HTML email can: set tracking/advertising cookies, record your IP address, view your browser/device details, and estimate your approximate location.
            <p>

            <p>
                Cerb will load external images using a proxy server to protect your team's privacy.
            </p>
        </div>

        <fieldset class="peek black">
            <legend>Proxy</legend>

            <table>
                <tr>
                    <td>
                        <b>Timeout:</b>
                    </td>
                    <td>
                        Stop loading remote images after
                        <input type="text" name="proxy_image_timeout_ms" value="{$params.proxy_image_timeout_ms}" maxlength="4" style="width:3.5em;">
                        milliseconds
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>Redirects:</b>
                    </td>
                    <td>
                        <label><input type="radio" name="proxy_image_redirects_disabled" value="0" {if !$params.proxy_image_redirects_disabled}checked="checked"{/if}> Allow</label>
                        <label><input type="radio" name="proxy_image_redirects_disabled" value="1" {if $params.proxy_image_redirects_disabled}checked="checked"{/if}> Deny</label>
                    </td>
                </tr>
            </table>
        </fieldset>

        <fieldset class="peek black">
            <legend>Blocklist</legend>

            <p>
                Images matching these patterns will <b>always</b> be blocked:
            </p>

            <div>
                <textarea name="proxy_image_blocklist" class="cerb-code-editor">{$params.proxy_image_blocklist}</textarea>
            </div>
        </fieldset>
    </fieldset>

    <fieldset>
        <legend>Links</legend>

        <div style="margin-bottom:15px;">
            <p>
                Links in HTML email can: deceive you about their destination to steal personal information (phishing).
            </p>

            <p>
                Cerb will show you the true destination when clicking an external link.
            </p>
        </div>

        <fieldset class="peek black">
            <legend>Whitelist</legend>

            <p>
                Links matching these patterns will <b>not</b> display a warning:
            </p>

            <div>
                <textarea name="links_whitelist" class="cerb-code-editor">{$params.links_whitelist}</textarea>
            </div>

        </fieldset>
    </fieldset>

    <div style="margin-top:10px;">
        <button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
    </div>
</form>

<script type="text/javascript">
    $(function() {
        var $frm = $('#frmSetupMailHtml');

        $frm.find('.cerb-code-editor').cerbCodeEditor();

        $frm.find('button.submit')
            .click(function(e) {
                Devblocks.saveAjaxTabForm($frm);
            })
        ;
    });
</script>