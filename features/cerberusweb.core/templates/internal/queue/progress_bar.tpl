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

{* The stacked bar + legend are drawn by CerbUI.Distbar, initialized in job_monitor.tpl
   (funcRenderDistbar) on first render and after each refresh. Segment order here sets the
   stack order and is matched 1:1 by DISTBAR_PALETTE; data-value sizes each segment and
   data-text is its formatted legend label. *}
<div class="cerb-ui-distbar" data-cerb-queue-distbar>
    <span data-label="Done"        data-value="{$progress.counts.done}"      data-text="{$progress.counts.done|number_format:0}"></span>
    <span data-label="Error"       data-value="{$progress.counts.failed}"    data-text="{$progress.counts.failed|number_format:0}"></span>
    <span data-label="In progress" data-value="{$progress.counts.inflight}"  data-text="{$progress.counts.inflight|number_format:0}"></span>
    <span data-label="Retrying"    data-value="{$progress.counts.scheduled}" data-text="{$progress.counts.scheduled|number_format:0}"></span>
    <span data-label="Available"   data-value="{$progress.counts.available}" data-text="{$progress.counts.available|number_format:0}"></span>
</div>
