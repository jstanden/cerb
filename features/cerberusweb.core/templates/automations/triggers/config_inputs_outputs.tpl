<div>
    {if $inputs}
    <h3>{'common.inputs'|devblocks_translate|capitalize}</h3>

    <div class="cerb-markdown-content">
        <table cellpadding="2" cellspacing="2" width="100%">
            <colgroup>
                <col style="width:1%;white-space:nowrap;">
                <col style="padding-left:10px;">
            </colgroup>
            <tbody>
            {foreach from=$inputs item=input}
            <tr>
                <td valign="top">
                    <strong><code>{$input.key}</code></strong>
                </td>
                <td>
                    {$input.notes|devblocks_markdown_to_html nofilter}
                </td>
            </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
    {/if}

    {if $outputs}
    <h3>{'common.outputs'|devblocks_translate|capitalize}</h3>

    <div class="cerb-markdown-content">
        {foreach from=$outputs item=output_keys key=exit_code}
        <h3 style="margin:0 0 5px 0;">{$exit_code}:</h3>
        <table cellpadding="2" cellspacing="2" width="100%">
            <colgroup>
                <col style="width:1%;white-space:nowrap;">
                <col style="padding-left:10px;">
            </colgroup>
            <tbody>
            {foreach from=$output_keys item=output}
            <tr>
                <td valign="top">
                    <strong><code>{$output.key}:</code></strong>
                </td>
                <td>
                    {$output.notes|devblocks_markdown_to_html nofilter}
                </td>
            </tr>
            {/foreach}
            </tbody>
        </table>
        {/foreach}
    </div>
    {/if}
</div>

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" id="{$script_uid}">
$(function() {
    const script = document.getElementById('{$script_uid}');
    const container = script ? script.previousElementSibling : null;
    if(container && window.CerbUI && CerbUI.Accordion) {
        new CerbUI.Accordion(container, {
            active: -1,        // all sections collapsed (jQuery UI active:false)
            collapsible: true  // clicking the open header collapses it
        });
    }
});
</script>
