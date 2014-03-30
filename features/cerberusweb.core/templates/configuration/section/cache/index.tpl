<h2>Cache</h2>

<fieldset>
	<legend>
		{$cacher->manifest->name}
		(<a href="javascript:;" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=cache&action=showCachePeek', null, false);">{'common.edit'|devblocks_translate|lower}</a>)
	</legend>
	
	<div>
		{$cacher->renderStatus()}
	</div>
</fieldset>
