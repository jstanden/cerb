{$uniqid = uniqid()}
{function renderWidget widget=[]}
	<div data-cerb-widget-name="{$widget.key}" class="cerb-portal-widget cerb-portal-widget--width-{$widget.width}">
		{if $widget.label}
		<div class="cerb-portal-widget--title">
			{if $widget.label_link}
				<a href="{$widget.label_link}">{$widget.label}</a>
			{else}
				{$widget.label}
			{/if}
		</div>
		{/if}
		<div class="cerb-portal-widget--content">
			{$widget._init nofilter}
		</div>
	</div>
{/function}
<article id="dashboard{$uniqid}" class="cerb-portal-dashboard" data-cerb-page-name="{$page.key}">
	<div class="cerb-portal-wrapper">
		{if $page.label}
			<h1>{$page.label}</h1>
		{/if}
		{if $layout == ['sidebar','content']}
			<div class="cerb-portal-dashboard-layout cerb-portal-dashboard-layout--sidebar-left" style="vertical-align:top;display:flex;flex-flow:row wrap;">
				<div data-layout-zone="sidebar" class="cerb-portal-dashboard-layout-zone" style="flex:1 1 33%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.sidebar item=widget name=widgets}
						{renderWidget widget=$widget}
					{/foreach}
					</div>
				</div>
				
				<div data-layout-zone="content" class="cerb-portal-dashboard-layout-zone" style="flex:2 2 66%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.content item=widget name=widgets}
						{renderWidget widget=$widget}
					{/foreach}
					</div>
				</div>
			</div>
		{elseif $layout == ['content','sidebar']}
			<div class="cerb-portal-dashboard-layout cerb-portal-dashboard-layout--sidebar-right" style="vertical-align:top;display:flex;flex-flow:row wrap;">
				<div data-layout-zone="content" class="cerb-portal-dashboard-layout-zone cerb-portal-dashboard-layout-zone--content" style="flex:2 2 66%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.content item=widget name=widgets}
						{renderWidget widget=$widget}
					{/foreach}
					</div>
				</div>
				
				<div data-layout-zone="sidebar" class="cerb-portal-dashboard-layout-zone cerb-portal-dashboard-layout-zone--sidebar" style="flex:1 1 33%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.sidebar item=widget name=widgets}
						{renderWidget widget=$widget}
					{/foreach}
					</div>
				</div>
			</div>
		{elseif $layout == ['left','center','right']}
			<div class="cerb-portal-dashboard-layout cerb-portal-dashboard-layout--thirds" style="vertical-align:top;display:flex;flex-flow:row wrap;">
				<div data-layout-zone="left" class="cerb-portal-dashboard-layout-zone cerb-portal-dashboard-layout-zone--left" style="flex:1 1 33%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.left item=widget name=widgets}
						{renderWidget widget=$widget}
					{/foreach}
					</div>
				</div>
				
				<div data-layout-zone="center" class="cerb-portal-dashboard-layout-zone cerb-portal-dashboard-layout-zone--center" style="flex:1 1 33%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.center item=widget name=widgets}
						{renderWidget widget=$widget}
					{/foreach}
					</div>
				</div>
				
				<div data-layout-zone="right" class="cerb-portal-dashboard-layout-zone cerb-portal-dashboard-layout-zone--right" style="flex:1 1 33%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.right item=widget name=widgets}
						{renderWidget widget=$widget}
					{/foreach}
					</div>
				</div>
			</div>
		{elseif $layout == ['left','right']}
			<div class="cerb-portal-dashboard-layout cerb-portal-dashboard-layout--halves" style="vertical-align:top;display:flex;flex-flow:row wrap;">
				<div data-layout-zone="left" class="cerb-portal-dashboard-layout-zone cerb-portal-dashboard-layout-zone--left" style="flex:1 1 50%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.left item=widget name=widgets}
						{renderWidget widget=$widget}
					{/foreach}
					</div>
				</div>
				<div data-layout-zone="right" class="cerb-portal-dashboard-layout-zone cerb-portal-dashboard-layout-zone--right" style="flex:1 1 50%;min-width:340px;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.right item=widget name=widgets}
						{renderWidget widget=$widget}
					{/foreach}
					</div>
				</div>
			</div>
		{else}
			<div class="cerb-portal-dashboard-layout cerb-portal-dashboard-layout--content" style="vertical-align:top;display:flex;flex-flow:row wrap;">
				<div data-layout-zone="content" class="cerb-portal-dashboard-layout-zone" style="flex:1 1 100%;overflow-x:hidden;">
					<div class="cerb-portal-dashboard-layout-zone--widgets" style="padding:2px;vertical-align:top;display:flex;flex-flow:row wrap;min-height:100px;">
					{foreach from=$zones.content item=widget name=widgets}
						{renderWidget widget=$widget}
					{/foreach}
					</div>
				</div>
			</div>
		{/if}
	</div>
</article>