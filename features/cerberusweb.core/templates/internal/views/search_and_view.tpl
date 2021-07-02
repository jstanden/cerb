{if !empty($view)}
<form action="#" method="POST" id="filter{$view->id}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div id="viewCustomFilters{$view->id}">
{include file="devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl"}
</div>
</form>

<div id="view{$view->id}" data-context="{$view->getContext()}">{$view->render()}</div>

<script type="text/javascript">
$(function() {
	$('#viewCustomFilters{$view->id}').bind('view_refresh', function(event) {
		if(event.target === event.currentTarget) {
			var $div = $('<div/>')
				.addClass('cerb-search-progress')
				.css('position', 'absolute')
				.css('width', '200px')
				.css('left', '50%')
				.css('margin-top', '5px')
				.css('margin-left', '-100px')
			;
			
			$div.append(Devblocks.getSpinner().css('max-width', '16px'));
			$div.append($('<b>Searching, please wait...</b>'));
			
			$div.insertBefore($('#view{$view->id}').hide());
			
			genericAjaxGet($('#view{$view->id}'), 'c=internal&a=invoke&module=worklists&action=refresh&id={$view->id}', function() {
				$div.remove();
			});
		}
	});
});
</script>
{/if}