{$rule_schema_id = $schema->manifest->id}
{$stores = $schema_stores[$rule_schema_id]|default:[]}
{$_all_jobs = $storage_migration_jobs|default:[]}
{$jobs = $_all_jobs[$rule_schema_id]|default:[]}

{* Card header totals = sum across every store; the Objects/Size toggle swaps which is shown (see --summary) *}
{$total_count = 0}
{$total_bytes = 0}
{foreach from=$stores item=store}
	{$total_count = $total_count + $store.count}
	{$total_bytes = $total_bytes + $store.bytes}
{/foreach}

<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-u-border" data-cerb-cerbui-card>
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm">
			<span class="cerb-icons cerb-icon-cube"></span>
			{$schema->manifest->name}
		</div>
		<div class="cerb-ui-header--right">
			<span class="cerb-ui-header--summary cerb-storage-metric-swap">
					<span data-metric="objects"><b>{$total_count}</b> object{if $total_count != 1}s{/if}</span>
					<span data-metric="size"><b>{$total_bytes|devblocks_prettybytes}</b></span>
				</span>
			{if !$smarty.const.DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE}
				<button type="button" class="cerb-ui-button" data-cerb-schema-id="{$rule_schema_id}"><span class="cerb-icons cerb-icon-edit"></span></button>
			{/if}
		</div>
	</div>

	{if !empty($stores)}
		{* The active + archive profiles lead (a role badge marks each), then any other store still holding
		   objects. Each row's swatch + bar share a store-identity color key (the shared CerbUI color scale);
		   the metric shows objects or size depending on the page's Objects/Size toggle. *}
		<div class="cerb-storage-stores">
			{foreach from=$stores item=store}
				<div class="cerb-storage-store">
					<span class="cerb-storage-store--role">
						{if $store.role == 'active'}
							<span class="cerb-icons cerb-icon-star" title="Active — new content is written here"></span>
						{elseif $store.role == 'archive'}
							<span class="cerb-icons cerb-icon-archive" title="Archive{if $store.archive_after_days !== null} — objects move here after {$store.archive_after_days} day{if $store.archive_after_days != 1}s{/if}{/if}"></span>
						{/if}
					</span>
					<span class="cerb-storage-store--swatch" data-color-key="{$store.key}"></span>
					<span class="cerb-storage-store--name">{$store.name}</span>
					<span class="cerb-storage-store--bar"><span data-color-key="{$store.key}" data-value-objects="{$store.count}" data-value-size="{$store.bytes}"></span></span>
					<span class="cerb-storage-store--metric cerb-storage-metric-swap">
						<span data-metric="objects"><b>{$store.count}</b> object{if $store.count != 1}s{/if}</span>
						<span data-metric="size"><b>{$store.bytes|devblocks_prettybytes}</b></span>
					</span>
					<span class="cerb-storage-store--action-slot">
						{if !$smarty.const.DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE}
							{$job = $jobs[$store.key]|default:null}
							{if $job}
								<a class="cerb-storage-store--action" data-cerb-migrate-monitor data-job-id="{$job->id}"><span class="cerb-icons cerb-icon-repeat"></span> migrating...</a>
							{elseif $store.count > 0}
								<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-migrate data-schema-id="{$rule_schema_id}" data-src-extension="{$store.extension}" data-src-profile-id="{$store.profile_id}" title="Migrate"><span class="cerb-icons cerb-icon-transfer"></span></button>
							{/if}
						{/if}
					</span>
				</div>
			{/foreach}
		</div>
	{/if}
</div>
