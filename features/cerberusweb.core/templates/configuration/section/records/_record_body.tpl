<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
	<div>
		<div class="cerb-ui-header--title-sm cerb-u-flex cerb-u-items-center cerb-u-gap-2"><span class="cerb-icons cerb-icon-{$rt.icon}"></span>{$rt.name}</div>
	</div>
	<div class="cerb-ui-header--right">
		<div class="cerb-ui-chip">
			{if $rt.uri}<div><div class="cerb-ui-chip--label">uri</div><div class="cerb-ui-chip--value">{$rt.uri}</div></div>{/if}
			<div><div class="cerb-ui-chip--label">extension_id</div><div class="cerb-ui-chip--value">{$rt.id}</div></div>
		</div>
		{if $rt.is_custom}<button type="button" class="cerb-records-add cerb-peek-trigger cerb-records-edit-type" title="Edit record type" data-context="{$context_custom_record}" data-context-id="{$rt.record_id}" data-edit="true"><span class="cerb-icons cerb-icon-edit"></span></button>{/if}
	</div>
</div>

{* Records API (DAO) keys vs the quick-search filter keys — a CerbUI.Separator titles each column *}
<div class="cerb-records-group">
	<div class="cerb-records-two cerb-u-flex cerb-u-flex-wrap cerb-u-items-start">
		<div class="cerb-records-two--col">
			{include file="devblocks:cerberusweb.core::configuration/section/records/_api_table.tpl" api=$rt.api custom=$rt.custom fieldsets=$rt.fieldsets record_context=$rt.id peek_context=$context_custom_field}
		</div>
		<div class="cerb-records-two--col">
			{include file="devblocks:cerberusweb.core::configuration/section/records/_keys_table.tpl" rows=$rt.search title="Quick search filters"}
		</div>
	</div>
</div>

{* Parameter sub-schemas (e.g. a draft's params (mail.compose)) — collapsed reference tables *}
{if $rt.params}
	<div class="cerb-records-group">
		<div class="cerb-records-group--label cerb-u-flex cerb-u-items-center cerb-u-justify-between cerb-u-gap-2 cerb-u-text-muted cerb-u-fs-n5 cerb-u-fw-700"><span>Parameters</span></div>
		{foreach from=$rt.params item=p}
			<div class="cerb-records-acc cerb-records-fieldset-acc">
				<h3><code>{$p.title}</code></h3>
				<div>
					<table class="cerb-records-table">
						<tbody>
							{foreach from=$p.rows item=pr}
								<tr class="cerb-records-table--row">
									<td><code>{$pr.key}</code></td>
									<td class="cerb-records-table--type cerb-u-text-muted">{$pr.value nofilter}</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
				</div>
			</div>
		{/foreach}
	</div>
{/if}

