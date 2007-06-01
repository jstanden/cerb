{assign var=lastrun value=$job->getParam('lastrun')}
{if $lastrun}Last run: {$lastrun|date_format:"%a, %b %d %Y %I:%M %p"}{/if}<br>

<div id="jobout_{$job->manifest->id}">
{if $job}
<br>
{$job->run()}
<br>
<a href="javascript:;" onclick="toggleDiv('jobout_{$job->manifest->id}','none');">hide output</a>
{/if}
</div>