{* One scheduler job row. Rendered in the index loop and standalone after a save (saveJobJson)
   so the row can be swapped in place without a full page reload. Needs only $job + $max_parallel. *}
{$enabled = $job->getParam('enabled',0)}
{$locked = $job->getParam('locked',0)}
{$lastrun = $job->getParam('lastrun',0)}
{$is_concurrent = array_key_exists('parallel', $job->manifest->params)}
{$concurrency = $job->getParam('concurrency', $max_parallel)}
{$duration = $job->getParam('duration',5)}
{$term = $job->getParam('term','m')}
{$term_word = ($term=='d') ? 'day' : (($term=='h') ? 'hour' : 'minute')}
{$interval_seconds = $duration * (($term=='d') ? 86400 : (($term=='h') ? 3600 : 60))}
{$nextrun = $lastrun + $interval_seconds}

<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-sched-job cerb-u-flex cerb-u-items-center{if !$enabled} cerb-sched-off{/if}" data-sched-job data-job-id="{$job->id}"
	data-enabled="{if $enabled}1{else}0{/if}" data-concurrent="{if $is_concurrent}1{else}0{/if}" data-lastrun="{$lastrun}" data-nextfire="{$nextrun}" data-interval="{$interval_seconds}" data-name="{$job->manifest->name|escape}">
	<div class="cerb-sched-job--info cerb-u-flex cerb-u-items-center">
		<span class="cerb-ui-pip{if $enabled} cerb-ui-pip--live{/if}"></span>
		<div style="min-width:0;">
			<div class="cerb-sched-job--name">{$job->manifest->name}</div>
			<div class="cerb-sched-job--meta cerb-u-flex cerb-u-items-center cerb-u-flex-wrap">
				<span class="cerb-ui-pill">{if $enabled}every {$duration} {$term_word}{if $duration != 1}s{/if}{else}disabled{/if}</span>
				{if $is_concurrent}<span class="cerb-ui-pill cerb-u-bg-none cerb-u-border-0" style="color:var(--cerb-color-link);"><span class="cerb-icons cerb-icon-branch"></span> {$concurrency} parallel</span>{/if}
				<span data-sched-ago>&middot; &mdash;</span>
			</div>
		</div>
	</div>

	<div class="cerb-sched-job--chart"><div class="cerb-ui-sparkchart" data-sched-chart></div></div>

	<div class="cerb-sched-job--stats cerb-u-flex cerb-u-items-center cerb-u-flex-shrink-0">
		{* stats stack = a vertical legend; data-color-key matches the chart's series so colors agree *}
		<div class="cerb-ui-legend cerb-ui-legend--vertical" data-sched-stats>
			<div data-label="runs" data-color-key="runs" data-type="bar" data-sched-stat-runs></div>
			<div data-label="avg" data-color-key="duration" data-type="line" data-sched-stat-avg></div>
		</div>
		<div class="cerb-sched-ring-slot cerb-u-flex cerb-u-items-center cerb-u-justify-center cerb-u-flex-shrink-0 cerb-u-ml-auto">
			{if !$enabled}
				<span class="cerb-sched-ring-off cerb-u-text-uppercase cerb-u-nowrap cerb-u-text-center">off</span>
			{elseif $is_concurrent}
				<span class="cerb-sched-ring-off cerb-u-text-uppercase cerb-u-nowrap cerb-u-text-center">continuous</span>
			{else}
				<div class="cerb-ui-time-ring" data-sched-ring></div>
			{/if}
		</div>
		<button type="button" class="cerb-ui-button" data-sched-edit><span class="cerb-icons cerb-icon-edit"></span></button>
		{if $enabled && !$locked}
			<button type="button" class="cerb-ui-button" data-sched-run><span class="cerb-icons cerb-icon-play-button"></span></button>
		{/if}
	</div>
</div>
