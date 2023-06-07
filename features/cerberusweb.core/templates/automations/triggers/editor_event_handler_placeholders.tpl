{if $trigger_inputs}
<legend>{'common.placeholders'|devblocks_translate|capitalize}</legend>
<div>
    <div class="cerb-markdown-content">
        <table cellpadding="2" cellspacing="2" width="100%">
            <colgroup>
                <col style="width:1%;white-space:nowrap;">
                <col style="padding-left:10px;">
            </colgroup>
            <tbody>
            {foreach from=$trigger_inputs item=input}
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
</div>
{/if}
