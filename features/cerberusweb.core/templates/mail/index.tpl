<div class="cerb-menu" style="margin-top:-5px;">
	<ul>
		<li>
			<div>
				<a href="javascript:;" class="menu">Actions <span>&#x25be;</span></a>
				<ul class="cerb-popupmenu cerb-float">
					{if $active_worker->hasPriv('core.mail.send')}<li><a href="{devblocks_url}c=mail&a=compose{/devblocks_url}">{$translate->_('mail.send_mail')|capitalize}</a></li>{/if}
					<li><a href="{devblocks_url}c=mail&a=drafts{/devblocks_url}">{'mail.drafts'|devblocks_translate|capitalize}</a></li>
					
					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.mail','core.mail.menu.settings')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
	</ul>
</div>
<br clear="all" style="clear:both;">

{if !empty($subpage) && $subpage instanceof Extension_PageSection}
<div class="cerb-subpage" style="margin-top:10px;">
	{$subpage->render()}
</div>
{/if}

<script type="text/javascript">
	$('DIV.cerb-menu DIV A.menu')
		.closest('li')
		.hover(
			function(e) {
				$(this).find('ul:first').show();
			},
			function(e) {
				$(this).find('ul:first').hide();
			}
		)
		.find('.cerb-popupmenu > li')
			.click(function(e) {
				e.stopPropagation();
				if(!$(e.target).is('li'))
					return;

				$link = $(this).find('a');

				if($link.length > 0)
					window.location.href = $link.attr('href');
			})
		;
</script>
