{if $logs}
<div class="cerb-job-log">
    <h2 style="margin:0 0 0.25em 0;">{{'common.log'|devblocks_translate|capitalize}}</h2>
    <ul class="cerb-job-log--entries">
        {foreach $logs as $log}
        <li class="cerb-job-log--entry cerb-job-log--entry--level-{$log->level}">
            <span class="cerb-job-log--time"><abbr title="{$log->created_at|devblocks_date}">{$log->created_at|devblocks_prettytime}</abbr></span>
            <span class="cerb-job-log--icon glyphicons {if $log->level >= 3}glyphicons-circle-remove{elseif $log->level == 2}glyphicons-warning-sign{else}glyphicons-circle-ok{/if}"></span>
            <span class="cerb-job-log--message">{$log->message|escape}</span>
        </li>
        {/foreach}
    </ul>
</div>
{/if}
