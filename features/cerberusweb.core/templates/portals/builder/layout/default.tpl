<!DOCTYPE html>
<html lang="en">
	<head>
		{include file="devblocks:cerberusweb.core::portals/builder/includes/head.tpl"}
	</head>
	
	<body>
		{if $header}
		<header>
			<div class="cerb-portal-wrapper">
				{include file="devblocks:cerberusweb.core::portals/builder/layout/widgets.tpl" sections=$header}
			</div>
		</header>
		{/if}

		<main>
			{if is_a($renderer, 'Extension_PortalPageRenderer')}
			{$renderer->render()}
			{/if}
		</main>
		
		<div class="cerb-portal-layout-spacer"></div>
	
		{if $footer}
		<footer>
			<div class="cerb-portal-wrapper">
				{include file="devblocks:cerberusweb.core::portals/builder/layout/widgets.tpl" sections=$footer}
			</div>
		</footer>
		{/if}
	</body>
</html>