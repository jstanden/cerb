<h2>Cache</h2>

<fieldset>
	<legend>
		{$cacher->manifest->name}
		(<a onclick="genericAjaxPopup('peek','c=config&a=invoke&module=cache&action=showCachePeek', null, false);">{'common.edit'|devblocks_translate|lower}</a>)
	</legend>
	
	<div>
		{$cacher->renderStatus()}
	</div>
</fieldset>
