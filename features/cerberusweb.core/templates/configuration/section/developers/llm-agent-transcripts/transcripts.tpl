{foreach from=$transcripts item=transcript}
    <tbody>
    <tr data-cerb-transcript-id="{$transcript->uuid}">
        <td>
            {if $transcript->is_read}<span class="glyphicons glyphicons-circle-ok"></span>{/if}
            <b>{$transcript->uuid}</b>
            <br>
            <abbr title="{$transcript->created_at|devblocks_date}">{$transcript->created_at|devblocks_prettytime}</abbr>
            <br>
            {$transcript->provider} &nbsp; {$transcript->user_type}
            <br>
        </td>
    </tr>
    </tbody>
{/foreach}
{if $transcripts|count == $limit}
<tbody>
<tr>
    <td style="text-align:center;">
        <button type="button" data-cerb-button="more">{{'common.more'|devblocks_translate|capitalize}} <span class="glyphicons glyphicons-circle-arrow-down"></span></button>
    </td>
</tr>
</tbody>
{/if}