<div class="cerb-form-builder-prompt cerb-form-builder-prompt-chart">
	<h6>{$label}</h6>

	<div style="margin-left:10px;">
		{if $error}
			<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--warn">
				<div class="cerb-ui-header cerb-ui-header--center">
					<div class="cerb-ui-callout">
						<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
						<div>
							<div class="cerb-ui-header--subtitle">{$error}</div>
						</div>
					</div>
				</div>
			</div>
		{else}
			{include file="devblocks:cerberusweb.core::internal/chart_kata/render.tpl"}
		{/if}
	</div>
</div>