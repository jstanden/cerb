{$skillsets_all = DAO_Skillset::getAll()}
{$skillsets_available = $skillsets_all}

{if !empty($skillsets_linked)}
	{$skillsets_available = array_diff_key($skillsets_available, $skillsets_linked)}
	
	{foreach from=$skillsets_linked item=skillset}
	{include file="devblocks:cerberusweb.core::internal/skillsets/fieldset.tpl" skillset=$skillset}
	{/foreach}
{/if}

<div class="skillset-insertion"></div>

{$btn_skillset_add = "skillsets_{uniqid()}"}
<div style="margin-left:10px;margin-bottom:10px;{if empty($skillsets_available)}display:none;{/if}">
	<button type="button" id="{$btn_skillset_add}" class="action">Add Skillset &#x25be;</button>
	<ul class="cerb-popupmenu" style="border:0;">
		<li style="background:none;">
			<input type="text" size="32" class="input_search filter">
		</li>
		{foreach from=$skillsets_all item=skillset}
		<li class="item" skillset_id="{$skillset->id}" style="{if isset($skillsets_linked.{$skillset->id})}display:none;{/if}">
			<div>
				<a href="javascript:;">
					{$skillset->name}
				</a>
			</div>
		</li>
		{/foreach}
	</ul>
</div>

<script type="text/javascript">
$('#{$btn_skillset_add}')
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
			var skillset_id = $li.attr('skillset_id');
			
			genericAjaxGet('', 'c=internal&a=handleSectionAction&section=skills&action=getSkillset&context={$context}&context_id={$context_id}&id=' + skillset_id, function(html) {
				if(undefined == html || null == html)
					return;

				var $at = $('#{$btn_skillset_add}')
					.closest('div')
					.siblings('div.skillset-insertion')
					;
				
				var $fieldset = $(html);
				
				$fieldset.insertBefore($at);
			});
			
			$li.hide();
			
			if($ul.find('> li.item:visible').length == 0)
				$ul.closest('div').hide();
		})
		;
		
		$menu.find('> li > input.filter').keyup(
			function(e) {
				var term = $(this).val().toLowerCase();
				var $fs_menu = $(this).closest('ul.cerb-popupmenu');
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

		$ul.toggle();
		
		if($ul.is(':hidden')) {
			$ul.blur();
		} else {
			$ul.find('input:text').first().focus();
		}
	})
	;
</script>