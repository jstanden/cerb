{$element_id = uniqid('prompt_')}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-sheet" id="{$element_id}">
	{if is_string($label) && $label}
	<h6>{$label}</h6>
	{/if}

	{if isset($has_toolbar) && $has_toolbar}
		<div style="clear:both;"></div>

		<div data-cerb-sheet-toolbar class="cerb-code-editor-toolbar" style="margin:0.5em 0;">
			{if $toolbar}
				{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}
			{/if}
		</div>
	{/if}

	<div style="margin:0 10px;">
		{if $layout.filtering}
			<div style="position:relative;box-sizing:border-box;width:100%;border:1px solid var(--cerb-color-background-contrast-220);border-radius:10px;padding:0 5px;margin-bottom:5px;">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" style="width:16px;height:16px;top:3px;position:absolute;fill:var(--cerb-color-background-contrast-180);">
					<path d="M27.207,24.37866,20.6106,17.78235a9.03069,9.03069,0,1,0-2.82825,2.82825L24.37878,27.207a1,1,0,0,0,1.41425,0l1.414-1.41418A1,1,0,0,0,27.207,24.37866ZM13,19a6,6,0,1,1,6-6A6.00657,6.00657,0,0,1,13,19Z"/>
				</svg>
				<input data-cerb-sheet-query type="text" value="{$filter}" placeholder="Search" style="border:0;background-color:inherit;outline:none;margin-left:16px;width:calc(100% - 16px);">
			</div>
		{/if}

		{$selection_key = uniqid('selection_')}

		<div data-cerb-sheet-container>
		{if in_array($layout.style, ['columns','grid'])}
			{include file="devblocks:cerberusweb.core::ui/sheets/render_grid.tpl" layout_style=$layout.style sheet_selection_key=$selection_key default=$default}
		{elseif $layout.style == 'fieldsets'}
			{include file="devblocks:cerberusweb.core::ui/sheets/render_fieldsets.tpl" sheet_selection_key=$selection_key default=$default}
		{else}
			{include file="devblocks:cerberusweb.core::ui/sheets/render.tpl" sheet_selection_key=$selection_key default=$default}
		{/if}
		</div>

		<div data-cerb-sheet-selections style="display:none;">
			<ul class="bubbles chooser-container">
				{if is_array($default) && $default}
					{foreach from=$default item=v}
						<li>
							<input type="hidden" name="prompts[{$var}][]" value="{$v}">
							{$v}
						</li>
					{/foreach}
				{elseif is_string($default) && $default}
					<li>
						<input type="hidden" name="prompts[{$var}]" value="{$default}">
						{$default}
					</li>
				{/if}
			</ul>
		</div>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $prompt = $('#{$element_id}');
	var $form = $prompt.closest('form');
	var $sheet = $prompt.find('[data-cerb-sheet-container]');
	var $sheet_toolbar = $prompt.find('[data-cerb-sheet-toolbar]')
	var $sheet_query_editor = $prompt.find('[data-cerb-sheet-query]')
	var $sheet_selections = $prompt.find('[data-cerb-sheet-selections]').find('ul');

	var $remove = $('<span class="glyphicons glyphicons-circle-remove"/>')
		.css('position', 'absolute')
		.css('top', '-5px')
		.css('right', '-5px')
		.on('click', function(e) {
			e.stopPropagation();
			var $parent = $remove.closest('li');
			$remove.detach();
			$parent.remove();

			$prompt.triggerHandler('cerb-sheet--update-selections');
		})
	;

	$sheet_selections.on('mouseover', 'li', function(e) {
		e.stopPropagation();
		$remove.appendTo($(this));
	});

	$prompt.on('cerb-sheet--update-selections', function(e) {
		e.stopPropagation();

		$sheet.find('input[type=checkbox],input[type=radio]').prop('checked', null);
		$sheet.find('.cerb-sheet--row .cerb-sheet--row-selected').removeClass('cerb-sheet--row-selected');
		
		$sheet_selections.find('input[type=hidden]').each(function() {
			var $hidden = $(this);
			var $input = $sheet.find('input[value="' + $hidden.val() + '"]');
			$input.prop('checked', 'checked');
			$input.closest('.cerb-sheet--row').addClass('cerb-sheet--row-selected');
		});

		$prompt.triggerHandler('cerb-sheet--toolbar-refresh');
	});

	$prompt.on('cerb-sheet--toolbar-refresh', function(e) {
		e.stopPropagation();

		{if isset($has_toolbar) && $has_toolbar}
		// Update the toolbar
		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'automation');
		formData.set('action', 'invokePrompt');
		formData.set('prompt_key', 'sheet/{$var}');
		formData.set('prompt_action', 'updateToolbar');
		formData.set('continuation_token', '{$continuation_token}');

		$sheet_selections.find('input[type=hidden]').each(function() {
			formData.append('selections[]', $(this).val());
		});

		$sheet_toolbar.html(Devblocks.getSpinner().css('max-width', '16px')).show();

		genericAjaxPost(formData, null, null, function(html) {
			$sheet_toolbar
				.html(html)
				.show()
				.triggerHandler('cerb-toolbar--refreshed')
			;
			
			if(0 === $sheet_toolbar.children().length)
				$sheet_toolbar.hide().html('');
		});
		{/if}
	});

	$prompt.on('cerb-sheet--selections-clear', function(e) {
		e.stopPropagation();
		$sheet_selections.empty();
	});

	$prompt.on('cerb-sheet--refresh', function(e) {
		e.stopPropagation();

		// Update the sheet
		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'automation');
		formData.set('action', 'invokePrompt');
		formData.set('prompt_key', 'sheet/{$var}');
		formData.set('prompt_action', 'refresh');
		formData.set('continuation_token', '{$continuation_token}');

		$sheet.prepend(Devblocks.getSpinner(true));

		genericAjaxPost(formData, $sheet, null, function() {
			$prompt.triggerHandler('cerb-sheet--update-selections');
		});
	});

	$prompt.on('cerb-sheet--page-changed', function(e) {
		e.stopPropagation();

		// Update the sheet
		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'automation');
		formData.set('action', 'invokePrompt');
		formData.set('prompt_key', 'sheet/{$var}');
		formData.set('prompt_action', 'refresh');
		formData.set('continuation_token', '{$continuation_token}');

		formData.set('page', e.page);

		$sheet.prepend(Devblocks.getSpinner(true));

		genericAjaxPost(formData, $sheet, null, function() {
			$prompt.triggerHandler('cerb-sheet--update-selections');
		});
	});

	$sheet.on('cerb-sheet--selection', function(e) {
		e.stopPropagation();

		var $item = null;
		var no_toolbar_update = e.hasOwnProperty('no_toolbar_update') && e.no_toolbar_update;

		if(e.hasOwnProperty('is_multiple') && e.is_multiple) {
			if(e.selected) {
				$item = $('<li/>')
					.text(e.ui.item.closest('.cerb-sheet--row').text())
					.prepend(
						e.ui.item
						.clone()
						.attr('type','hidden')
						.attr('name', 'prompts[{$var}][]')
					)
				;
				$sheet_selections.append($item);
			} else {
				$sheet_selections.find('input[value="' + e.ui.item.val() + '"]').closest('li').remove();
			}

		} else {
			$sheet_selections.empty();
			
			if(e.selected) {
				$item = $('<li/>')
					.text(e.ui.item.closest('.cerb-sheet--row').text())
					.prepend(
						e.ui.item
						.clone()
						.attr('type', 'hidden')
						.attr('name', 'prompts[{$var}]')
					)
				;
				$sheet_selections.html($item);

				// If this is the only single selection prompt (no continue or other inputs)
				if(1 === $form.find('.cerb-form-builder-prompt,.cerb-form-builder-continue').length) {
					$form.triggerHandler($.Event('cerb-form-builder-submit'));
					return;
				}
			}
		}

		if(!no_toolbar_update) {
			$prompt.triggerHandler('cerb-sheet--toolbar-refresh');
		}
	});

	$sheet.on('cerb-sheet--selections-changed', function(e) {
		e.stopPropagation();
		
		// If we're in single selection and auto-submitting, don't refresh the toolbar
		if(
			!(e.hasOwnProperty('is_multiple') && e.is_multiple) 
			&& 1 === $form.find('.cerb-form-builder-prompt,.cerb-form-builder-continue').length
		) {
			return;
		}
		
		$prompt.triggerHandler('cerb-sheet--toolbar-refresh');
	});

	{if $layout.filtering}
	$sheet_query_editor.on('keypress', function(e) {
		e.stopPropagation();
		
		if(e.which === 13) {
			e.preventDefault();

			// Update the toolbar
			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'automation');
			formData.set('action', 'invokePrompt');
			formData.set('prompt_key', 'sheet/{$var}');
			formData.set('prompt_action', 'refresh');
			formData.set('continuation_token', '{$continuation_token}');

			formData.set('page', '0');
			formData.set('filter', $sheet_query_editor.val());

			$sheet.prepend(Devblocks.getSpinner(true));

			genericAjaxPost(formData, $sheet, null, function() {
				$sheet_query_editor.select().focus();
				$prompt.triggerHandler('cerb-sheet--update-selections');
			});
		}
	});
	{/if}

	$sheet_toolbar.cerbToolbar({
		caller: {
			name: 'cerb.toolbar.interaction.worker.await.sheet',
			params: {
			}
		},
		start: function(formData) {
		},
		done: function(e) {
			e.stopPropagation();

			// [TODO] Listen to the toolbar. Don't always refresh
			$sheet_toolbar.trigger('cerb-sheet--refresh');

			var $target = e.trigger;
			var done_params;

			if($target.is('.cerb-bot-trigger')) {
				done_params = new URLSearchParams($target.attr('data-interaction-done'));

				if (done_params.has('clear_selections')) {
					$sheet_toolbar.trigger('cerb-sheet--selections-clear');
				}
			}
		},
		reset: function(e) {
			e.stopPropagation();
			$sheet_toolbar.trigger('cerb-sheet--refresh');
		},
		error: function(e) {
			e.stopPropagation();
			$sheet_toolbar.trigger('cerb-sheet--refresh');
		}
	});

	// Update selection styles from defaults
	$prompt.triggerHandler('cerb-sheet--update-selections');
});
</script>