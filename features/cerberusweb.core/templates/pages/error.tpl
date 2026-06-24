{if $page}
<div style="margin:5px 0;">
	<h2>{$page->name}</h2>
</div>
{/if}

<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--warn cerb-u-mt-2">
	<div class="cerb-ui-header cerb-ui-header--center">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
			<div>
				{if $error_title}
					<div class="cerb-ui-header--title-sm">{$error_title}</div>
				{/if}
				<div class="cerb-ui-header--subtitle">
					{$error_message}
				</div>
			</div>
		</div>
	</div>
</div>