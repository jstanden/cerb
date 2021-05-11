{$toolbar_placeholders = $toolbar_ext->getPlaceholdersMeta()}
{$toolbar_inputs = $toolbar_ext->getInteractionInputsMeta()}
{$toolbar_output = $toolbar_ext->getInteractionOutputMeta()}
{$toolbar_after = $toolbar_ext->getInteractionAfterMeta()}
<fieldset data-cerb-toolbar-help class="peek black" style="display:none;margin:10px 0 0 0;">
    <legend style="font-size:140%;">{'common.help'|devblocks_translate|capitalize}</legend>
    
    {if $toolbar_placeholders}
    <h3 style="padding:0;margin:0 0 5px 0;">{'common.placeholders'|devblocks_translate|capitalize}</h3>
    <div>
        <div class="cerb-markdown-content">
            <table cellpadding="2" cellspacing="2" width="100%">
                <colgroup>
                    <col style="width:1%;white-space:nowrap;">
                    <col style="padding-left:10px;">
                </colgroup>
                <tbody>
                {foreach from=$toolbar_placeholders item=placeholder}
                    <tr>
                        <td valign="top">
                            <strong><code>{$placeholder.key}</code></strong>
                        </td>
                        <td>
                            {$placeholder.notes|devblocks_markdown_to_html nofilter}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>
    {/if}

    {if $toolbar_inputs}
    <h3 style="padding:0;margin:0 0 5px 0;">{'common.inputs'|devblocks_translate|capitalize}</h3>
    <div>
        <div class="cerb-markdown-content">
            <table cellpadding="2" cellspacing="2" width="100%">
                <colgroup>
                    <col style="width:1%;white-space:nowrap;">
                    <col style="padding-left:10px;">
                </colgroup>
                <tbody>
                {foreach from=$toolbar_inputs item=placeholder}
                    <tr>
                        <td valign="top">
                            <strong><code>{$placeholder.key}</code></strong>
                        </td>
                        <td>
                            {$placeholder.notes|devblocks_markdown_to_html nofilter}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>
    {/if}

    {if $toolbar_output}
    <h3 style="padding:0;margin:0 0 5px 0;">{'common.output'|devblocks_translate|capitalize}</h3>
    <div>
        <div class="cerb-markdown-content">
            <table cellpadding="2" cellspacing="2" width="100%">
                <colgroup>
                    <col style="width:1%;white-space:nowrap;">
                    <col style="padding-left:10px;">
                </colgroup>
                <tbody>
                {foreach from=$toolbar_output item=placeholder}
                    <tr>
                        <td valign="top">
                            <strong><code>{$placeholder.key}</code></strong>
                        </td>
                        <td>
                            {$placeholder.notes|devblocks_markdown_to_html nofilter}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>
    {/if}
    
    {if $toolbar_after}
    <h3 style="padding:0;margin:0;">After</h3>
    <div>
        <div class="cerb-markdown-content">
            <table cellpadding="2" cellspacing="2" width="100%">
                <colgroup>
                    <col style="width:1%;white-space:nowrap;">
                    <col style="padding-left:10px;">
                </colgroup>
                <tbody>
                {foreach from=$toolbar_after item=placeholder}
                    <tr>
                        <td valign="top">
                            <strong><code>{$placeholder.key}</code></strong>
                        </td>
                        <td>
                            {$placeholder.notes|devblocks_markdown_to_html nofilter}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>
    {/if}
</fieldset>

<fieldset data-cerb-toolbar-tester class="peek black" style="display:none;margin:10px 0 0 0;">
    <legend style="font-size:140%;">{'common.test'|devblocks_translate|capitalize}</legend>

    <div>
        <div data-cerb-toolbar-tester-editor-placeholders>
            <div class="cerb-code-editor-toolbar">
                <b>{'common.placeholders'|devblocks_translate|capitalize} (KATA)</b>
                <div class="cerb-code-editor-toolbar-divider"></div>
                <button type="button" class="cerb-code-editor-toolbar-button cerb-code-editor-toolbar-button--run" title="{'common.run'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-play"></span></button>
            </div>
            <textarea name="tester[placeholders]" data-editor-mode="ace/mode/cerb_kata" rows="5" cols="45"></textarea>
        </div>

        <div data-cerb-toolbar-tester-results style="margin-top:10px;position:relative;"></div>
    </div>
</fieldset>
