{$uid = uniqid('el')}
{$completed = $progress.counts.done + $progress.counts.failed}
{$pct = ($progress.total > 0) ? ($completed / $progress.total * 100) : 0}

<div class="cerb-progress-bar--header">
    <span class="cerb-progress-bar--header-count">
        <span class="cerb-progress-bar--header-numerator">{$completed|number_format:0}</span>
        <span class="cerb-progress-bar--header-divider"> / </span>
        <span class="cerb-progress-bar--header-denominator">{$progress.total|number_format:0}</span>
    </span>
    <span class="cerb-progress-bar--header-percent">{$pct|number_format:1}%</span>
</div>

<svg width="100%" viewBox="0 0 600 14" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" class="cerb-progress-bar"
     role="meter" aria-label="Available: {$progress.counts.available}, In progress: {$progress.counts.inflight}, Done: {$progress.counts.done}, Error: {$progress.counts.failed}">
    <title>Available: {$progress.counts.available}, In progress: {$progress.counts.inflight}, Done: {$progress.counts.done}, Error: {$progress.counts.failed}</title>
    <defs>
        <clipPath id="{$uid}">
            <rect x="0" y="2" width="600" height="10" rx="4"/>
        </clipPath>
    </defs>
    <rect x="0" y="2" width="600" height="10" rx="4" fill="currentColor" opacity="0.1"/>
    <g clip-path="url(#{$uid})">
        <rect class="cerb-progress-bar--done" x="0" y="2" width="{$progress.percents.done*600}" height="10"/>
        <rect class="cerb-progress-bar--failed" x="{$progress.percents.done*600}" y="2" width="{$progress.percents.failed*600}" height="10"/>
        <rect class="cerb-progress-bar--inflight" x="{$progress.percents.done*600 + $progress.percents.failed*600}" y="2" width="{$progress.percents.inflight*600}" height="10"/>
        <rect class="cerb-progress-bar--available" x="{$progress.percents.done*600 + $progress.percents.failed*600 + $progress.percents.inflight*600}" y="2" width="{$progress.percents.available*600}" height="10"/>
    </g>
</svg>

<div class="cerb-progress-bar-legend">
    {if $progress.counts.inflight}
    <div>
        <span class="cerb-progress-bar-legend--swatch cerb-progress-bar-legend--inflight"></span>
        In progress ({$progress.counts.inflight|number_format:0})
    </div>
    {/if}
    {if $progress.counts.done}
    <div>
        <span class="cerb-progress-bar-legend--swatch cerb-progress-bar-legend--done"></span>
        Done ({$progress.counts.done|number_format:0})
    </div>
    {/if}
    {if $progress.counts.failed}
    <div>
        <span class="cerb-progress-bar-legend--swatch cerb-progress-bar-legend--failed"></span>
        Error ({$progress.counts.failed|number_format:0})
    </div>
    {/if}
    {if $progress.counts.available}
    <div>
        <span class="cerb-progress-bar-legend--swatch cerb-progress-bar-legend--available"></span>
        Available ({$progress.counts.available|number_format:0})
    </div>
    {/if}
</div>
