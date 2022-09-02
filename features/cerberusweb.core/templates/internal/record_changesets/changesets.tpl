{foreach from=$changesets item=changeset name=changesets}
    <tbody>
    <tr data-cerb-changeset-id="{$changeset->id}" class="{if $smarty.foreach.changesets.iteration % 2}even{else}odd{/if}{if $smarty.foreach.changesets.first} selected{/if}">
        <td style="cursor:pointer;padding:0.5em;position:relative;">
            <b>{$changeset->created_at|devblocks_date}</b>
            <br>
            {$changeset_worker = $changeset->getWorker()}
            {if $changeset_worker}
                <img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$changeset_worker->id}{/devblocks_url}?v={$changeset_worker->updated}">
                {$changeset_worker->getName()}
            {/if}
        </td>
    </tr>
    </tbody>
{foreachelse}
    <tbody>
        <tr>
            <td>
                (no data)
            </td>
        </tr>
    </tbody>
{/foreach}
