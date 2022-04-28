<article>
	<div class="cerb-portal-wrapper">
		{if $page.label}
			<h1>
				{if $page.label_link}
					<a href="{$page.label_link}">{$page.label}</a>
				{else}
					{$page.label}
				{/if}
			</h1>
		{/if}
		<div class="cerb-portal-page-content">
			{$content_html nofilter}
		</div>
	</div>
</article>