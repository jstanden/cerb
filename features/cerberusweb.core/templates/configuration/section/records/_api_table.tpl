{*
 * The Records API column: one table (icon + key; type lives in the icon tooltip), several <tbody>s —
 *   1) built-in DAO keys, 2) a CerbUI.Separator + custom fields (no fieldset),
 *   3) one collapsible <tbody> per custom fieldset (a CerbUI.Separator header toggles its field rows),
 *   4) a dashed CerbUI.Separator to add a fieldset.
 * params: $api, $custom, $fieldsets, $record_context (add-field/add-fieldset), $peek_context (custom_field peek)
 *}
<table class="cerb-records-table cerb-records-apitable">

	{* Column title *}
	<tbody>
		<tr class="cerb-records-seprow">
			<td colspan="2" class="cerb-records-sepcell">
				<div class="cerb-ui-separator"><span>Records API</span></div>
			</td>
		</tr>
	</tbody>

	{* Built-in DAO keys *}
	<tbody>
		{if $api}
			{foreach from=$api item=row}
				<tr class="cerb-records-table--row">
					<td class="cerb-records-table--icon"><span class="cerb-icons cerb-icon-{$row.icon} cerb-u-fs" style="color:var(--cerb-color-tag-{$row.color});" title="{$row.type}"></span></td>
					<td>
						<code>{$row.key}</code>{if $row.required} <span class="cerb-records-req cerb-u-fw-700" title="Required">*</span>{/if}
						{if $row.notes}<div class="cerb-records-keynote cerb-u-text-muted cerb-u-fs-n3">{$row.notes nofilter}</div>{/if}
					</td>
				</tr>
			{/foreach}
		{else}
			<tr><td colspan="2" class="cerb-records-empty cerb-u-text-muted cerb-u-italic">No writable API keys.</td></tr>
		{/if}
	</tbody>

	{* Custom fields (no fieldset) — separator-introduced, + to add *}
	<tbody>
		<tr class="cerb-records-seprow">
			<td colspan="2" class="cerb-records-sepcell">
				<div class="cerb-ui-separator">
					<span>Custom fields</span>
					<button type="button" class="cerb-records-add cerb-u-ml-1" data-cerb-add-field data-context="{$record_context}" title="Add a custom field"><span class="cerb-icons cerb-icon-circle-plus"></span></button>
				</div>
			</td>
		</tr>
		{foreach from=$custom item=row}
			<tr class="cerb-records-table--row cerb-records-table--link cerb-peek-trigger cerb-u-cursor-pointer" data-context="{$peek_context}" data-context-id="{$row.id}" data-edit="true">
				<td class="cerb-records-table--icon"><span class="cerb-icons cerb-icon-{$row.icon} cerb-u-fs" style="color:var(--cerb-color-tag-{$row.color});" title="{$row.type}"></span></td>
				<td>
					<code>{$row.key}</code>
					<div class="cerb-records-keynote cerb-u-text-muted cerb-u-fs-n3">{$row.label}</div>
				</td>
			</tr>
		{/foreach}
	</tbody>

	{* One collapsible tbody per fieldset (expanded by default) — the header (a CerbUI.Separator,
	   matching 'Custom fields' with the chevron at the end) toggles its field rows *}
	{foreach from=$fieldsets item=fs}
		<tbody class="cerb-records-fs is-open" data-fieldset="{$fs.id}">
			<tr class="cerb-records-fsrow">
				<td colspan="2">
					<button type="button" class="cerb-records-fstoggle cerb-u-bgg-hover">
						<span class="cerb-ui-separator cerb-records-fssep">
							<span>{$fs.name}</span>
							<span class="cerb-icons cerb-icon-chevron-right cerb-records-fschevron cerb-u-fs-n3 cerb-u-text-muted"></span>
						</span>
					</button>
				</td>
			</tr>
			{if $fs.fields}
				{foreach from=$fs.fields item=row}
					<tr class="cerb-records-table--row cerb-records-table--link cerb-peek-trigger cerb-u-cursor-pointer" data-context="{$peek_context}" data-context-id="{$row.id}" data-edit="true">
						<td class="cerb-records-table--icon"><span class="cerb-icons cerb-icon-{$row.icon} cerb-u-fs" style="color:var(--cerb-color-tag-{$row.color});" title="{$row.type}"></span></td>
						<td>
							<code>{$row.key}</code>
							<div class="cerb-records-keynote cerb-u-text-muted cerb-u-fs-n3">{$row.label}</div>
						</td>
					</tr>
				{/foreach}
			{else}
				<tr class="cerb-records-table--row"><td colspan="2" class="cerb-records-empty cerb-u-text-muted cerb-u-italic">No fields in this fieldset yet.</td></tr>
			{/if}
		</tbody>
	{/foreach}

	{* Add a fieldset — a CerbUI.Separator matching the 'Custom fields' section above *}
	<tbody>
		<tr class="cerb-records-seprow">
			<td colspan="2" class="cerb-records-sepcell">
				<div class="cerb-ui-separator">
					<span>Add a fieldset</span>
					<button type="button" class="cerb-records-add cerb-u-ml-1" data-cerb-add-fieldset data-context="{$record_context}" title="Add a fieldset"><span class="cerb-icons cerb-icon-circle-plus"></span></button>
				</div>
			</td>
		</tr>
	</tbody>
</table>
