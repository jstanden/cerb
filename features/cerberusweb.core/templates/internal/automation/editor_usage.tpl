{if $usage}
<div class="cerb-ui-tile-grid" style="margin-top:5px;">
	{foreach from=$usage item=record}
	<a href="{$record->record_url}" class="cerb-ui-tile cerb-peek-trigger" data-context="{$record->_context}" data-context-id="{$record->id}" style="text-decoration:none;color:inherit;">
		<span class="cerb-ui-tile--icon" style="background-color:var(--cerb-color-tag-{$record->_type_color});"><span class="cerb-icons cerb-icon-{$record->_type_icon}"></span></span>
		<div class="cerb-ui-tile--text">
			<div class="cerb-ui-tile--kind">{$record->_type_label}</div>
			<div class="cerb-ui-tile--name">{$record->_label}</div>
		</div>
	</a>
	{/foreach}
</div>
{else}
	(no usage found)
{/if}
