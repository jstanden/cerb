{$element_id = uniqid('prompt_')}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-sheet" id="{$element_id}">
	{if is_string($label) && $label}
	<h6>{$label}</h6>
	{/if}

	<div style="margin-left:10px;">
		{if $layout.filtering}
			<div style="box-sizing:border-box;width:100%;border:1px solid rgb(220,220,220);border-radius:10px;padding:5px;">
				<textarea data-cerb-sheet-query data-editor-mode="ace/mode/text" data-editor-lines="1">{$filter}</textarea>
			</div>
		{/if}

		{if $toolbar}
			<div data-cerb-sheet-toolbar class="cerb-code-editor-toolbar">
			{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}
			</div>
		{/if}

		{$selection_key = uniqid('selection_')}

		<div data-cerb-sheet-container style="box-shadow:0 0 5px rgb(200,200,200);">
		{if $layout.style == 'grid'}
			{include file="devblocks:cerberusweb.core::ui/sheets/render_grid.tpl" sheet_selection_key=$selection_key default=$default}
		{elseif $layout.style == 'grid'}
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

		$sheet_selections.find('input[type=hidden]').each(function() {
			var $hidden = $(this);
			$sheet.find('input[value="' + $hidden.val() + '"]').prop('checked', 'checked');
		});

		$prompt.triggerHandler('cerb-sheet--toolbar-refresh');
	})

	$prompt.on('cerb-sheet--toolbar-refresh', function(e) {
		e.stopPropagation();

		{if $toolbar}
		// Update the toolbar
		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'automation');
		formData.set('action', 'invokePrompt');
		formData.set('prompt_key', 'sheet/{$var}');
		formData.set('prompt_action', 'updateToolbar');
		formData.set('execution_token', '{$execution_token}');

		$sheet_selections.find('input[type=hidden]').each(function() {
			formData.append('selections[]', $(this).val());
		});

		$sheet_toolbar.html(Devblocks.getSpinner().css('max-width', '16px'));

		genericAjaxPost(formData, null, null, function(html) {
			$sheet_toolbar
				.html(html)
				.triggerHandler('cerb-toolbar--refreshed')
			;
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
		formData.set('execution_token', '{$execution_token}');

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
		formData.set('execution_token', '{$execution_token}');

		formData.set('page', e.page);

		$sheet.prepend(Devblocks.getSpinner(true));

		genericAjaxPost(formData, $sheet, null, function() {
			$prompt.triggerHandler('cerb-sheet--update-selections');
		});
	});

	$sheet.on('cerb-sheet--selection', function(e) {
		e.stopPropagation();

		var $item = null;

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
			if(e.selected) {
				$item = $('<li/>')
					//.css('position', 'relative')
					.text(e.ui.item.closest('.cerb-sheet--row').text())
					.prepend(
						e.ui.item
						.clone()
						.attr('type', 'hidden')
						.attr('name', 'prompts[{$var}]')
					)
				;
				$sheet_selections.html($item);
			} else {
				$sheet_selections.empty();
			}
		}

		$prompt.triggerHandler('cerb-sheet--toolbar-refresh');
	});

	$sheet.on('cerb-sheet--selections-changed', function(e) {
		e.stopPropagation();
	});

	{if $layout.filtering}
	var $editor = $sheet_query_editor
		.cerbCodeEditor()
		.nextAll('pre.ace_editor')
	;

	var editor = ace.edit($editor.attr('id'));
	editor.setOption('highlightActiveLine', false);
	editor.renderer.setOption('showGutter', false);
	editor.renderer.setOption('showLineNumbers', false);

	editor.commands.addCommand({
		name: 'Submit',
		bindKey: { win: "Enter", mac: "Enter" },
		exec: function(editor) {
			// Update the toolbar
			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'automation');
			formData.set('action', 'invokePrompt');
			formData.set('prompt_key', 'sheet/{$var}');
			formData.set('prompt_action', 'refresh');
			formData.set('execution_token', '{$execution_token}');

			formData.set('page', 0);
			formData.set('filter', editor.getValue());

			$sheet.prepend(Devblocks.getSpinner(true));

			genericAjaxPost(formData, $sheet, null, function() {
				$prompt.triggerHandler('cerb-sheet--update-selections');
			});
		}
	});

	editor.commands.addCommand({
		name: 'TabPrev',
		bindKey: { win: "Shift+Tab", mac: "Shift+Tab" },
		exec: function () {
			var $focusable = $prompt.closest('form').find(':focusable');
			var idx = $focusable.index($prompt.find(':focus'));
			var $prev = $focusable[idx-1] || $focusable.last();
			$prev.focus();
		}
	});

	editor.commands.addCommand({
		name: 'TabNext',
		bindKey: { win: "Tab", mac: "Tab" },
		exec: function () {
			var $focusable = $prompt.closest('form').find(':focusable');
			var idx = $focusable.index($prompt.find(':focus'));
			var $next = $focusable[idx+1] || $focusable.first();
			$next.focus();
		}
	});
	{/if}

	$sheet_toolbar.cerbToolbar({
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
});
</script>