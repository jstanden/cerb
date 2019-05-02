{include file="devblocks:cerberusweb.core::portals/builder/includes/header.tpl"}

{$user_stylesheet = $portal->getParam('user_stylesheet')}
<style type="text/css">
{$user_stylesheet nofilter}
</style>

<div class="cerb-portal-layout-content">
	<header class="cerb-portal-nav">
		<div class="cerb-portal-wrapper">
			<a href="{devblocks_url}{/devblocks_url}"><div class="cerb-portal-logo"></div></a>
			
			{if $identity}
				Logged in as <b>{$identity->username}</b> 
				[<a href="{devblocks_url}c=login&a=logout{/devblocks_url}">{'header.signoff'|devblocks_translate|lower}</a>]
			{else}
				<a href="{devblocks_url}c=login{/devblocks_url}">{'header.signon'|devblocks_translate|lower}</a>
			{/if}
		</div>
	</header>
	
	<header class="cerb-portal-nav-pages">
		<div class="cerb-portal-wrapper">
			<nav>
				<ul>
				{foreach from=$pages item=menu_page}
					<li {if $page->id == $menu_page->id}class="selected"{/if}><a href="{devblocks_url}c={$menu_page->uri}{/devblocks_url}">{$menu_page->name}</a></li>
				{/foreach}
				</ul>
			</nav>
		</div>
	</header>
	
	{if $renderer instanceof Extension_PortalPageRenderer}
	{$renderer->render()}
	{/if}
</div>

<div class="cerb-portal-layout-spacer"></div>

<footer class="cerb-portal-layout-footer">
{$footer_html nofilter}
</footer>

{include file="devblocks:cerberusweb.core::portals/builder/includes/footer.tpl"}