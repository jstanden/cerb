<div class="cerb-search-buttons">
	{if $before}
	{$before nofilter}
	{/if}
	
	{foreach from=$search_buttons item=search_button}
	<button type="button" class="cerb-search-trigger" data-context="{$search_button.context}" data-query="{$search_button.query}"><div class="badge-count">{$search_button.count|default:0}</div> {$search_button.label|capitalize}</button>
	{/foreach}
	
	{if $after}
	{$after nofilter}
	{/if}
</div>
