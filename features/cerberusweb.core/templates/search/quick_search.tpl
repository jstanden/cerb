{$uniqid = uniqid()}
{if !isset($focus)}{$focus = null}{/if}
{if !isset($agent_disabled)}{$agent_disabled = false}{/if}
{if isset($view) && is_a($view, 'IAbstractView_QuickSearch')}
{$agent_toolbar = null}
{if !$agent_disabled}{$agent_toolbar = $view->getAgentToolbar()}{/if}

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
			{if $agent_toolbar}<a data-cerb-editor-action="agent" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Ask an agent"><span class="cerb-icons cerb-icon-bot-message"></span></a>{/if}
		</div>
	</div>
</form>

{* The `agent.pane` toolbar (component: worklist) the chat's launcher tiles are built from. Parked in a hidden
   div rather than inlined into the JS below so the markup never has to survive JS-string escaping. *}
{if $agent_toolbar}<div id="{$uniqid}_agenttb" style="display:none;">{DevblocksPlatform::services()->ui()->toolbar()->fetch($agent_toolbar) nofilter}</div>{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$uniqid}');

	Devblocks.formDisableSubmit($frm);

	// getElementById, not an `#id` selector -- a uniqid() can start with a digit, which is invalid CSS.
	const sqEl = document.getElementById('{$uniqid}_sq');
	const agentTbEl = document.getElementById('{$uniqid}_agenttb');

	let sq = new CerbUI.SearchQuery(sqEl, {
		context: '{$view->getContext()}',
		onAutocomplete: CerbUI.SearchQuery.queryFieldSource('{$view->getContext()}'),
		onSearch: function() { $frm.submit(); }, // Enter -> the existing submit flow below
		{if $agent_toolbar}
		// The floating agent chat. SearchQuery's own getFields/setField/runSearch bridge covers this host
		// completely -- runSearch resolves to the onSearch above, which is the same submit Enter runs, so the
		// worklist repaints through the existing flow. `worklist_*` ride along as caller_params; an interaction
		// that wants them as INPUTS declares them in the toolbar KATA off the worklist_* placeholders.
		agent: {
			component: 'worklist',
			capabilities: 'getFields,setField,runSearch',
			toolbarHtml: agentTbEl ? agentTbEl.innerHTML : '',
			storageKey: 'cerb-worklist-search-agent-chat',
			chatTitle: 'Search agent',
			callerParams: {
				worklist_id: '{$view->id}',
				worklist_record_type: '{$view->getRecordType()}'
			},
			// Only getFields is overridden; setField/runSearch fall through to the component's defaults. The
			// component would report `record_type` as the CONTEXT ID (what it needs for autocomplete), but an
			// automation wants the alias for `data.query ... of:` -- so name both, unambiguously.
			runCommand: function(name) {
				if('getFields' !== name)
					return undefined;

				return JSON.stringify({
					query: sq.getValue(),
					record_type: '{$view->getRecordType()}',
					record_context: '{$view->getContext()}',
					view_id: '{$view->id}'
				});
			}
		}
		{/if}
	});

	{if $focus}
	sq.focus();
	{/if}

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