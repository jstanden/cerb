<div class="cerb-form-builder-prompt cerb-form-builder-prompt-chart">
	<h6>{$label}</h6>

	<div style="margin-left:10px;">
		{if $error}
			<div class="error-box">{$error}</div>
		{else}
			{include file="devblocks:cerberusweb.core::internal/chart_kata/render.tpl"}
		{/if}
	</div>
</div>