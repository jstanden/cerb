<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.query'|devblocks_translate|capitalize} {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}</label>
	<textarea name="{$namePrefix}[query]" data-editor-lines="8" spellcheck="false">{$params.query}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Save result to a placeholder named</label>
	<div>
		&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_results"}" required="required" spellcheck="false" size="32" placeholder="e.g. _results">&#125;&#125;
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
var $action = $('#{$namePrefix}_{$nonce}');

var dqEl = $action.find('textarea[name="{$namePrefix}[query]"]')[0];
if(dqEl && window.CerbUI && CerbUI.DataQuery)
	new CerbUI.DataQuery(dqEl, { onAutocomplete: CerbUI.DataQuery.dataQueryFieldSource() });
</script>
