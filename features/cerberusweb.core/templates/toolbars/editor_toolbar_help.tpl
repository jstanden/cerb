{$toolbar_placeholders = $toolbar_ext->getPlaceholdersMeta()}
{$toolbar_inputs = $toolbar_ext->getInteractionInputsMeta()}
{$toolbar_output = $toolbar_ext->getInteractionOutputMeta()}
{$toolbar_after = $toolbar_ext->getInteractionAfterMeta()}

<div class="cerb-ui-header cerb-ui-header--tight">
    <div class="cerb-ui-header--title-sm">{'common.help'|devblocks_translate|capitalize}</div>
</div>

{if $toolbar_placeholders}
    <div class="cerb-ui-header--title-sm cerb-u-mb-1">{'common.placeholders'|devblocks_translate|capitalize}</div>
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
    <div class="cerb-ui-header--title-sm cerb-u-mt-3 cerb-u-mb-1">{'common.inputs'|devblocks_translate|capitalize}</div>
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
    <div class="cerb-ui-header--title-sm cerb-u-mt-3 cerb-u-mb-1">{'common.output'|devblocks_translate|capitalize}</div>
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
    <div class="cerb-ui-header--title-sm cerb-u-mt-3 cerb-u-mb-1">After</div>
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
