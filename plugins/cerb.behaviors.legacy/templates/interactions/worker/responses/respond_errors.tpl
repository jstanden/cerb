<div class="cerb-ui-panel cerb-ui-panel--alert">
	<div class="cerb-ui-header">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">Correct the following errors to continue:</div>
				<ul class="cerb-ui-header--subtitle" style="margin:0.4em 0 0 0;padding-left:1.2em;">
					{foreach from=$errors item=error}
					<li>{$error}</li>
					{/foreach}
				</ul>
			</div>
		</div>
	</div>
</div>