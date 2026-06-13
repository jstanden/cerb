{$uniqid = uniqid()}
{if !isset($focus)}{$focus = null}{/if}
{if isset($view) && is_a($view, 'IAbstractView_QuickSearch')}

<form action="#" method="post" id="{$uniqid}" class="quick-search">
	<input type="hidden" name="c" value="search">
	<input type="hidden" name="a" value="ajaxQuickSearch">
	<input type="hidden" name="view_id" value="{$view->id}">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	<div class="cerb-ui-searchquery" id="{$uniqid}_sq">
		<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
		<div class="cerb-ui-searchquery--field">
			<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
			<textarea name="query" class="cerb-ui-searchquery--input cerb-input-quicksearch" rows="1">{$view->getParamsQuery()}</textarea>
			<span class="cerb-ui-searchquery--caret-anchor"></span>
		</div>
		<div class="cerb-ui-searchquery--right">
			<a class="cerb-quick-search-menu-trigger" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-sparkles"></span></a>
		</div>
	</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$uniqid}');

	Devblocks.formDisableSubmit($frm);

	let sq = new CerbUI.SearchQuery(document.getElementById('{$uniqid}_sq'), {
		context: '{$view->getContext()}',
		onAutocomplete: CerbUI.SearchQuery.queryFieldSource('{$view->getContext()}'),
		onSearch: function() { $frm.submit(); } // Enter -> the existing submit flow below
	});

	{if $focus}
	sq.focus();
	{/if}

	$frm.find('a.cerb-quick-search-menu-trigger').on('click', function() {
		sq.focus();
		sq.openAutocomplete();
	});

	$frm.on('submit', function() {
	    let $view = $('#view{$view->id}');

	    // If a search is already in progress, abort
	    if($view.siblings('.cerb-search-progress').length > 0)
            return;

		genericAjaxPost('{$uniqid}','',null,function(json) {
			if(json && true === json.status) {
				{if !empty($return_url)}
					window.location.href = '{$return_url}';
				{else}
					let $view_filters = $('#viewCustomFilters{$view->id}');

					if(0 !== $view_filters.length) {
						$view_filters.html(json.html);
						$view_filters.trigger('view_refresh')
					}
				{/if}
			}

			sq.focus();
		});
	});
});
</script>
{/if}