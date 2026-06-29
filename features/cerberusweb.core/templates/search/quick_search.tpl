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
			{$search_toolbar = $view->getSearchToolbar()}
			{if $search_toolbar}{DevblocksPlatform::services()->ui()->toolbar()->render($search_toolbar, ['class'=>'cerb-ui-toolbar--bare-tiny','attr'=>'data-cerb-search-toolbar'])}{/if}
			<a class="cerb-quick-search-menu-trigger" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
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

	// The admin-configurable `records.worklist.search` toolbar (bare-tiny) in the --right slot.
	// (getElementById + scoped query — a uniqid() can start with a digit, an invalid `#id` CSS selector.)
	const sqEl = document.getElementById('{$uniqid}_sq');
	const tbEl = sqEl ? sqEl.querySelector('[data-cerb-search-toolbar]') : null;
	if(tbEl && window.CerbUI && CerbUI.Toolbar) {
		new CerbUI.Toolbar(tbEl, {
			bare: 'tiny',
			caller: {
				name: 'cerb.toolbar.records.worklist.search',
				params: {
					worklist_id: '{$view->id}',
					worklist_record_type: '{$view->getRecordType()}'
				}
			},
			done: function(e) {
				if(!e.eventData)
					return;

				Devblocks.interactionWorkerPostActions(e.eventData);

				// An interaction may return a `query` to drop into the search field and run.
				if(e.eventData.return && e.eventData.return.query) {
					sq.setValue(e.eventData.return.query).focus();
					$frm.submit();
					return;
				}

				// Otherwise honor `after: refresh_worklist` (default yes) when the worklist is on the page.
				let $target = e.trigger;
				let done_params = new URLSearchParams(($target && $target.attr) ? ($target.attr('data-interaction-done') || '') : '');

				if($('#view{$view->id}').length && (!done_params.has('refresh_worklist') || '1' === done_params.get('refresh_worklist'))) {
					genericAjaxGet('view{$view->id}', 'c=internal&a=invoke&module=worklists&action=refresh&id={$view->id}');
				}
			}
		});
	}

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