{if $bulk_automations}
{$bulk_automations_btn = "bulk_automations_btn_"|cat:uniqid()}

<div class="bulk-automations-insertion"></div>

<div class="bulk-automations-add-wrap" style="margin-left:10px;margin-bottom:1.5em;">
	<button type="button" id="{$bulk_automations_btn}" class="action">Run Automation <span class="cerb-icons cerb-icon-chevron-down"></span></button>
	<ul class="cerb-popupmenu" style="border:0;display:none;">
		{if count($bulk_automations) > 10}
		<li style="background:none;">
			<input type="text" size="32" class="input_search filter" placeholder="Filter...">
		</li>
		{/if}
		{foreach from=$bulk_automations item=automation_item}
		<li class="item"
			data-key="{$automation_item.key}"
			data-context="{$automation_item.context}">
			<div>
				<a>{$automation_item.description|default:$automation_item.label|default:$automation_item.key}</a>
			</div>
		</li>
		{/foreach}
	</ul>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$('#{$bulk_automations_btn}')
	.each(function() {
		const $btn = $(this);
		const $menu = $btn.siblings('ul.cerb-popupmenu');
		const $wrap = $btn.closest('.bulk-automations-add-wrap');
		const $insertion = $wrap.siblings('.bulk-automations-insertion');
		let next_idx = 0;

		$menu
			.find('> li.item')
			.click(function(e) {
				e.stopPropagation();
				if(!$(e.target).is('li') && !$(e.target).is('div'))
					return;
				$(this).find('a').trigger('click');
			})
			.find('a')
			.click(function() {
				const $li = $(this).closest('li');
				const $ul = $li.closest('ul.cerb-popupmenu');
				const key = $li.attr('data-key');
				const context = $li.attr('data-context');
				const idx = next_idx++;

				const formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'automation');
				formData.set('action', 'getBulkUpdateFieldset');
				formData.set('handler', key);
				formData.set('context', context);
				formData.set('idx', idx);

				genericAjaxPost(formData, null, null, function(html) {
					if(!html) return;

					const $fieldset = $(html);

					// If the fieldset we added is removed, add its option back to the menu
					$fieldset.on('bulk_automation_remove', function(e) {
						const fieldset_key = e.key;
						$menu.find('> li.item').each(function() {
							if($(this).attr('data-key') === fieldset_key) {
								$(this).show();
							}
						});
						$wrap.show();
					});

					$fieldset.insertBefore($insertion);
				});

				$li.hide();

				if($ul.find('> li.item:visible').length === 0)
					$wrap.hide();

				$menu.hide();
			});

		$menu.find('> li > input.filter').on('keyup', function() {
			const term = $(this).val().toLowerCase();
			$menu.find('> li.item').each(function() {
				if($(this).text().toLowerCase().indexOf(term) !== -1) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		});

		$btn.data('menu', $menu);
	})
	.click(function() {
		const $ul = $(this).data('menu');
		$ul.toggle();

		if($ul.is(':hidden')) {
			$ul.blur();
		} else {
			$ul.find('input:text').first().focus();
		}
	});
</script>
{/if}
