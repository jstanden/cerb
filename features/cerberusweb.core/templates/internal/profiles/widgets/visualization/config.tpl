{$viz_uid = uniqid()}
<div id="widget{$widget->id}Config" class="cerb-u-mt-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
			<div class="cerb-ui-header--title-sm">Run this data query:</div>
			<div class="cerb-ui-header--right">
				{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}
			</div>
		</div>

		<textarea id="widget{$widget->id}DataQuery" class="placeholders" name="params[data_query]" data-editor-lines="12" spellcheck="false">{$widget->extension_params.data_query}</textarea>

		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-mt-2">
			<b>Cache</b> results for
			<input type="text" name="params[cache_ttl]" value="{$widget->extension_params.cache_ttl}" placeholder="0" size="6" style="width:6em;"> seconds
			<input type="hidden" name="params[cache_by_worker]" id="cacheByWorker{$viz_uid}" value="{if $widget->extension_params.cache_by_worker}1{else}0{/if}">
			<div class="cerb-ui-switcher" data-cerb-input="cacheByWorker{$viz_uid}">
				<button type="button" data-value="0" {if !$widget->extension_params.cache_by_worker}class="cerb-ui-switcher--active"{/if}>for everyone</button>
				<button type="button" data-value="1" {if $widget->extension_params.cache_by_worker}class="cerb-ui-switcher--active"{/if}>per worker</button>
			</div>
		</div>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Render this template:</div>
		</div>

		<div>
			The results of the above query are available as <b>{literal}{{json}}{/literal}</b>
		</div>

		<div>
			<textarea id="widget{$widget->id}TemplateEditor" name="params[template]" class="placeholders" data-editor-lines="8" spellcheck="false">{$widget->extension_params.template}</textarea>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	new CerbUI.ScriptingEditor(document.getElementById('widget{$widget->id}TemplateEditor'), { minLines: 4 });

	const dq = new CerbUI.DataQuery(document.getElementById('widget{$widget->id}DataQuery'), {
		onAutocomplete: CerbUI.DataQuery.dataQueryFieldSource(),
		toolbar: true,
	});

	if(window.CerbUI && CerbUI.Switcher) {
		let cacheByWorkerEl = document.querySelector('#widget{$widget->id}Config .cerb-ui-switcher[data-cerb-input="cacheByWorker{$viz_uid}"]');
		let $cacheByWorker = $('#cacheByWorker{$viz_uid}');
		if(cacheByWorkerEl)
			new CerbUI.Switcher(cacheByWorkerEl, { value: $cacheByWorker.val(), onSelect: function(value) { $cacheByWorker.val(value); } });
	}
});
</script>