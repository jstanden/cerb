<div class="help-box">
	<h1>
		<span class="glyphicons glyphicons-flash" style="vertical-align:middle;"></span> Plugins are migrating to workflows
	</h1>

	<p>
		<a href="https://cerb.ai/docs/workflows/">Workflows</a> are the preferred way to extend Cerb. Custom functionality can be built in your browser using automations and custom records.
	</p>
</div>

{* Modern features *}
<div style="margin-top:20px;"></div>
{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view_current}

{* Legacy features *}
<div style="margin-top:20px;"></div>
{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view_legacy}

{* Third-party plugins (storage/plugins/) — only when something's installed there *}
{if isset($view_plugins)}
<div style="margin-top:20px;"></div>
{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view_plugins}
{/if}
