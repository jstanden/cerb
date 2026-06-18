{* params: $rows (each: key, type, icon, color[, required, notes]); $title (optional leading separator) — a key-first table; the type lives in the icon tooltip *}
<table class="cerb-records-table">
	{if $title}
		<tbody>
			<tr class="cerb-records-seprow">
				<td colspan="2" class="cerb-records-sepcell">
					<div class="cerb-ui-separator"><span>{$title}</span></div>
				</td>
			</tr>
		</tbody>
	{/if}
	<tbody>
		{foreach from=$rows item=row}
			<tr class="cerb-records-table--row">
				<td class="cerb-records-table--icon"><span class="cerb-icons cerb-icon-{$row.icon} cerb-u-fs" style="color:var(--cerb-color-tag-{$row.color});" title="{$row.type}"></span></td>
				<td>
					<code>{$row.key}</code>{if $row.required} <span class="cerb-records-req cerb-u-fw-700" title="Required">*</span>{/if}
					{if $row.notes}<div class="cerb-records-keynote cerb-u-text-muted cerb-u-fs-n3">{$row.notes nofilter}</div>{/if}
				</td>
			</tr>
		{/foreach}
	</tbody>
</table>
