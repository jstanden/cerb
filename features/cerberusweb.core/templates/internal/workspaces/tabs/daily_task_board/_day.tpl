{* One day-row of the board. Rendered by tab.tpl's {foreach} and standalone by invoke('renderDay')
   for "Jump to Date". data-day-ymd is a tz-safe key for DOM matching + chronological insert order.
   Three shapes: today (TODO/In Progress/Done), a prior day (read-only Done log), and a future day
   (an interactive "stash_day" column = tasks stashed until that day). *}
{$is_future = $board.is_future|default:false}
<div class="dtb-day cerb-u-mb-4" data-cerb-dtb-day data-is-today="{if $board.is_today}1{else}0{/if}"{if $is_future} data-is-future="1"{/if} data-day-ts="{$board.date_key}" data-day-ymd="{$board.date_ymd}">
	<div class="dtb-day--title cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-fs-2x cerb-u-fw-400 cerb-u-m-0 cerb-u-mb-2">
		{* Only the chevron + date text toggles collapse — not the whole (wide) header row. *}
		<span class="dtb-day--toggle cerb-u-flex-inline cerb-u-items-center cerb-u-gap-2 cerb-u-cursor-pointer cerb-u-select-none" data-cerb-dtb-day-toggle><span class="cerb-icons cerb-icon-chevron-down dtb-day--chevron"></span> {$board.label}</span>
		{if $board.is_today}
			<span class="dtb-day--pip"></span>
			<span class="dtb-day--summary" data-cerb-dtb-summary></span>
			{* Collapsed-today summary: TODO / In Progress / Done count pills (always three, in order, zeros included). *}
			<span class="dtb-day--dist cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-fs-n10" data-cerb-dtb-dist><span class="cerb-ui-pill dtb-count cerb-u-fw-500 cerb-u-border-0" data-cerb-dtb-dist-col="todo">0</span><span class="cerb-ui-pill dtb-count cerb-u-fw-500 cerb-u-border-0" data-cerb-dtb-dist-col="in_progress">0</span><span class="cerb-ui-pill dtb-count cerb-u-fw-500 cerb-u-border-0" data-cerb-dtb-dist-col="done">0</span></span>
		{elseif $is_future}
			{* Future days hold only tasks stashed until then — a count pill (archive) after the date. *}
			<span class="cerb-ui-pill dtb-count dtb-day--count cerb-u-fw-500 cerb-u-border-0 cerb-u-fs-n10" data-cerb-dtb-day-count><span class="cerb-icons cerb-icon-archive"></span> <span data-cerb-dtb-day-count-n>{$board.columns.stash_day|count}</span></span>
		{else}
			{* Prior days hold only completed tasks — Done is implied, so just a count pill after the date. *}
			<span class="cerb-ui-pill dtb-count dtb-day--count cerb-u-fw-500 cerb-u-border-0 cerb-u-fs-n10" data-cerb-dtb-day-count><span data-cerb-dtb-day-count-n>{$board.columns.done|count}</span></span>
		{/if}
	</div>

	<div class="dtb-columns cerb-u-flex cerb-u-gap-3 cerb-u-items-stretch">
		{* today = TODO/In Progress/Done (interactive); prior days = a read-only Done log (drop target only);
		   future days = a single interactive stash-until column (drop target + quick-add). *}
		{foreach from=$board.columns key=col_key item=col_cards}
			<div class="dtb-column cerb-ui-panel cerb-u-flex cerb-u-flex-1 cerb-u-flex-column{if !$board.is_today && !$is_future} dtb-column--log{/if}{if $is_future} dtb-column--future{/if}">
				{* Every column keeps its label; today + future get controls, prior-day logs don't. *}
				<div class="cerb-ui-header cerb-ui-header--tight dtb-column--head cerb-u-mb-1">
					<div class="cerb-ui-header--label">{if $col_key == 'stash_day'}<span class="cerb-icons cerb-icon-archive"></span> Stashed{else}{$columns[$col_key]}{/if}</div>
					{if $board.is_today || $is_future}
					<div class="cerb-ui-header--right">
						<span class="cerb-ui-pill dtb-count cerb-u-fw-500 cerb-u-border-0">{$col_cards|count}</span>
						{if $board.is_today && $col_key == 'todo'}<span class="cerb-ui-pill dtb-count dtb-stash-toggle cerb-u-cursor-pointer cerb-u-fw-500 cerb-u-border-0" data-cerb-dtb="stash-toggle" title="Stashed tasks"><span class="cerb-icons cerb-icon-archive"></span> <span data-cerb-dtb-stash-count>{$stash_count}</span></span>{/if}
						<button type="button" class="dtb-icon-btn" title="Add task" data-cerb-dtb="add">
							<span class="cerb-icons cerb-icon-plus"></span>
						</button>
						<button type="button" class="dtb-icon-btn" title="More" data-cerb-dtb="col-menu">
							<span class="cerb-icons cerb-icon-more-vertical"></span>
						</button>
					</div>
					{/if}
				</div>

				<div class="dtb-column-cards cerb-u-flex cerb-u-flex-column cerb-u-cursor-pointer" data-cerb-dtb-column data-board-date="{$board.date_key}" data-column="{$col_key}">
					{if ($board.is_today && $col_key == 'todo') || ($is_future && $col_key == 'stash_day')}<div class="dtb-add-prompt cerb-u-opacity-50 cerb-u-fs-n1 cerb-u-text-center cerb-u-py-2 cerb-u-px-1" data-cerb-dtb-prompt{if $col_cards} hidden{/if}>Click anywhere to add a task</div>{/if}
					{foreach from=$col_cards item=card}
						{$accent = $project_colors[$card.project_id]|default:'var(--cerb-color-tag-gray)'}
						<div class="cerb-ui-panel cerb-ui-panel--accent dtb-card"
							style="--cerb-ui-accent:{$accent};"
							data-task-id="{$card.id}"
							data-project-id="{$card.project_id}"
							data-owner-id="{$card.owner_id|default:0}"
							data-importance="{$card.importance|default:0}">
							<div class="dtb-card--body">{if $col_key == 'done'}<span class="cerb-icons cerb-icon-check dtb-card--check"></span>{/if}{include file="devblocks:cerberusweb.core::internal/workspaces/tabs/daily_task_board/_card_meta.tpl" owner_id=$card.owner_id|default:0 project_id=$card.project_id}<div class="dtb-card--content"><span class="dtb-card--text">{$card.text}</span></div></div>
							<div class="dtb-card--meta cerb-u-flex cerb-u-items-center cerb-u-justify-end cerb-u-gap-2 cerb-u-mt-2 cerb-u-fs-n1">{if $card.comment_count > 0}<span class="cerb-ui-pill dtb-count cerb-u-fw-500 cerb-u-border-0" title="Comments"><span class="cerb-icons cerb-icon-conversation"></span> {$card.comment_count}</span>{/if}</div>
							<button type="button" class="dtb-card--menu" title="More" data-cerb-dtb="card-menu"><span class="cerb-icons cerb-icon-more-vertical"></span></button>
						</div>
					{/foreach}
				</div>
			</div>
		{/foreach}
	</div>
</div>
