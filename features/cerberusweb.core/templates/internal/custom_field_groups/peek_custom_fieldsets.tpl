{$custom_field_groups_available = DAO_CustomFieldGroup::getByContext($context)}
{$custom_field_groups_linked = DAO_CustomFieldGroup::getByContextLink($context, $context_id)}
{$custom_field_groups_available = array_diff_key($custom_field_groups_available, $custom_field_groups_linked)}

{foreach from=$custom_field_groups_linked item=cf_group}
{include file="devblocks:cerberusweb.core::internal/custom_field_groups/fieldset.tpl" bulk=false custom_field_group=$cf_group}
{/foreach}

<div class="custom-fieldset-insertion"></div>

{if !empty($custom_field_groups_available)}
{$btn_cfield_group_domid = "cfield_groups_{uniqid()}"}
<div style="margin-left:10px;margin-bottom:10px;">
	<button type="button" id="{$btn_cfield_group_domid}" class="action cerb-popupmenu-trigger">Add Fieldset &#x25be;</button>
	<ul class="cerb-popupmenu" style="border:0;">
		<li style="background:none;">
			<input type="text" size="32" class="input_search filter">
		</li>
		{foreach from=$custom_field_groups_available item=cf_group}
		{$owner_ctx = Extension_DevblocksContext::get($cf_group->owner_context)}
		<li class="item" cf_group_id="{$cf_group->id}">
			<div>
				<a href="javascript:;">
					{$cf_group->name}
				</a>
			</div>
			<div style="margin-left:10px;">
				{$meta = $owner_ctx->getMeta($cf_group->owner_context_id)}
				{$meta.name} ({$owner_ctx->manifest->name})
			</div>
		</li>
		{/foreach}
	</ul>
</div>

<script type="text/javascript">
$('#{$btn_cfield_group_domid}')
	.each(function() {
		var $menu = $(this).siblings('ul.cerb-popupmenu');

		$menu
		.find('> li')
		.click(function(e) {
			e.stopPropagation();
			if(!$(e.target).is('li') && !$(e.target).is('div'))
				return;

			$(this).find('a').trigger('click');
		})
		.find('a')
		.click(function() {
			var $li = $(this).closest('li');
			var $ul = $li.closest('ul.cerb-popupmenu');
			var cf_group_id = $li.attr('cf_group_id');
			
			genericAjaxGet('', 'c=internal&a=handleSectionAction&section=custom_field_groups&action=getCustomFieldSet&id=' + cf_group_id, function(html) {
				if(undefined == html || null == html)
					return;

				$popup = genericAjaxPopupFetch('peek');
				$at = $popup.find('div.custom-fieldset-insertion');
				
				var $fieldset = $(html);
				$fieldset.insertBefore($at);
			});
			
			$li.remove();
			
			if($ul.find('> li').length < 2)
				$ul.closest('div').remove();
		})
		;
		
		$menu.find('> li > input.filter').keyup(
			function(e) {
				term = $(this).val().toLowerCase();
				$fs_menu = $(this).closest('ul.cerb-popupmenu');
				$fs_menu.find('> li.item').each(function(e) {
					if(-1 != $(this).text().toLowerCase().indexOf(term)) {
						$(this).show();
					} else {
						$(this).hide();
					}
				});
			}
		);
		
		$(this).data('menu', $menu);
	})
	.click(function() {
		var $ul = $(this).data('menu');
		var off = $(this).offset();
		
		$ul.css('top', off.top + 20);
		$ul.css('left', off.left - 100);
		
		$ul.toggle();
		
		if($ul.is(':hidden')) {
			$ul.blur();
		} else {
			$ul.find('input:text').first().focus();
		}
	})
	;
</script>
{/if}