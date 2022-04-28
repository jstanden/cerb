{$element_id = uniqid('prompt_')}
<div class="cerb-interaction-panel--form-elements--prompt cerb-interaction-panel--form-elements-sheet" id="{$element_id}">
	{if is_string($label) && $label}
	<h6>{$label}</h6>
	{/if}

	<div>
		{if $layout.filtering}
			<div class="cerb-interaction-panel--form-elements-sheet-filter">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
					<path d="M27.207,24.37866,20.6106,17.78235a9.03069,9.03069,0,1,0-2.82825,2.82825L24.37878,27.207a1,1,0,0,0,1.41425,0l1.414-1.41418A1,1,0,0,0,27.207,24.37866ZM13,19a6,6,0,1,1,6-6A6.00657,6.00657,0,0,1,13,19Z"/>
				</svg>
				<input class="cerb-interaction-panel--form-elements-sheet-filter-input" type="text" value="{$filter}" placeholder="Search">
			</div>
		{/if}
		
		{$selection_key = uniqid('selection_')}

		<div data-cerb-sheet-container>
			{if $layout.style == 'scale'}
				{include file="devblocks:cerberusweb.core::automations/triggers/interaction.portal/await/sheet/render_scale.tpl" sheet_selection_key=$selection_key default=$default}
			{else}
				{include file="devblocks:cerberusweb.core::automations/triggers/interaction.portal/await/sheet/render.tpl" sheet_selection_key=$selection_key default=$default}
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
$$.ready(function() {
	var $prompt = document.querySelector('#{$element_id}');
	var $panel = $prompt.closest('.cerb-interaction-panel');
	var $sheet = $prompt.querySelector('[data-cerb-sheet-container]');
	var $sheet_selections = $prompt.querySelector('[data-cerb-sheet-selections]').querySelector('ul');

	$prompt.addEventListener('cerb-sheet--page-changed', function(e) {
		e.stopPropagation();
		
		if(!e.detail.hasOwnProperty('page'))
			return;

		var page_name = $prompt.closest('article.cerb-portal-dashboard').getAttribute('data-cerb-page-name');
		var widget_name = $prompt.closest('.cerb-portal-widget').getAttribute('data-cerb-widget-name');
		
		// Update the sheet
		var formData = new FormData();
		formData.set('continuation_token', '{$continuation_token}');
		formData.set('page', page_name);
		formData.set('widget', widget_name);
		formData.set('prompt_key', 'sheet/{$var}');
		formData.set('prompt_action', 'refresh');
		formData.set('prompt_params[page]', e.detail.page);

		var $spinner = $$.getSpinner();
		$spinner.style.position = 'absolute';
		$spinner.style.marginTop = '-16px';
		$spinner.style.marginLeft = '-16px';
		$spinner.style.left = '50%';
		$spinner.style.top = '50%';

		$sheet.prepend($spinner);
		$sheet.style.opacity = 0.35;

		$$.interactionInvoke(formData, function(err, res) {
			$sheet.style.opacity = 1.0;
			$spinner.remove();

			if(200 === res.status) {
				$$.html($sheet, res.responseText);
				$prompt.dispatchEvent($$.createEvent('cerb-sheet--update-selections'));
			}
		});
	});	
	
	/*
	var $remove = document.createElement('span');
	$remove.classList.add(['glyphicons','glyphicons-circle-remove']);
	$remove.style.position = 'absolute';
	$remove.style.top = '-5px';
	$remove.style.right = '-5px';
	$remove.addEventListener('click', function(e) {
		e.stopPropagation();
		var $parent = $remove.closest('li');
		$parent.removeChild($remove);
		$parent.remove();
		
		$prompt.dispatchEvent($$.createEvent('cerb-sheet--update-selections'));
	});

	$$.forEach($sheet_selections, function(index, $sel) {
		$sel.addEventListener('mouseover', function(e) {
			if('li' !== e.target.nodeName.toLowerCase())
				return;
			
			e.stopPropagation();
			$sel.appendChild($remove);
		});
	});
	*/
	
	$prompt.addEventListener('cerb-sheet--update-selections', function(e) {
		e.stopPropagation();
		
		var $checkboxes = $sheet.querySelectorAll('input[type=checkbox],input[type=radio]');
		var $selections = $sheet_selections.querySelectorAll('input[type=hidden]');
		
		$$.forEach($checkboxes, function(index, $el) {
			$el.checked = false;
		});
		
		$$.forEach($selections, function(index, $hidden) {
			var $el = $sheet.querySelector('input[value="' + $hidden.value + '"]');
			if($el) $el.checked = true;
		});
	});

	$prompt.addEventListener('cerb-sheet--selections-clear', function(e) {
		e.stopPropagation();
		$sheet_selections.innerHTML = '';
	});

	$prompt.addEventListener('cerb-sheet--refresh', function(e) {
		e.stopPropagation();
	});
	
	$prompt.addEventListener('cerb-sheet--selection', function(e) {
		e.stopPropagation();
		
		var $li = null;
		var $clone = null;
		var is_multiple = e.detail.hasOwnProperty('is_multiple') ? e.detail.is_multiple : false;
		var item = e.detail.hasOwnProperty('ui') ? e.detail.ui.item : null;
		
		if(!item) return;

		var $checkbox = $sheet_selections.querySelector('input[value="' + item.value + '"]');

		if(is_multiple) {
			if($checkbox) {
				$checkbox.closest('li').remove();
			} else {
				$li = document.createElement('li');
				$li.innerText = item.closest('.cerb-sheet--row').innerText;
				
				$clone = item.cloneNode(true);
				$clone.setAttribute('type', 'hidden');
				$clone.setAttribute('name', 'prompts[{$var}][]');
				$li.prepend($clone);
				
				$sheet_selections.appendChild($li);
			}

		} else {
			$sheet_selections.innerHTML = '';
			
			if(!item.checked)
				return;
			
			$li = document.createElement('li');
			$li.innerText = item.closest('.cerb-sheet--row').innerText;
			
			$clone = item.cloneNode(true);
			$clone.setAttribute('type', 'hidden');
			$clone.setAttribute('name', 'prompts[{$var}]');
			$li.appendChild($clone);
			
			$sheet_selections.appendChild($li);
		}
		
		{if $layout.style == 'buttons'}
			if(0 === $panel.querySelectorAll('.cerb-interaction-panel--form-elements-continue').length) {
				$panel.dispatchEvent($$.createEvent('cerb-interaction-event--submit'));
			}
		{/if}
	});

	$prompt.addEventListener('cerb-sheet--selections-changed', function(e) {
		e.stopPropagation();
		// [TODO]
	});

	{if $layout.filtering}
	$prompt.querySelector('.cerb-interaction-panel--form-elements-sheet-filter-input')
		.addEventListener('keyup', function(e) {
			e.stopPropagation();
			
			if(13 === e.keyCode) {
				$prompt.dispatchEvent($$.createEvent('cerb-sheet--filter-changed', { query: this.value }));
			}
		})
	;
	
	$prompt.addEventListener('cerb-sheet--filter-changed', function(e) {
		e.stopPropagation();
		
		var formData = new FormData();
		formData.set('continuation_token', '{$continuation_token}');
		formData.set('prompt_key', 'sheet/{$var}');
		formData.set('prompt_action', 'refresh');
		formData.set('prompt_params[page]', '0');
		formData.set('prompt_params[filter]', e.detail.query);

		var $spinner = $$.getSpinner();
		$spinner.style.position = 'absolute';
		$spinner.style.marginTop = '-16px';
		$spinner.style.marginLeft = '-16px';
		$spinner.style.left = '50%';
		$spinner.style.top = '50%';

		$sheet.prepend($spinner);
		$sheet.style.opacity = 0.35;
		
		$$.interactionInvoke(formData, function(err, res) {
			$sheet.style.opacity = 1.0;
			$spinner.remove();

			if(200 === res.status) {
				$$.html($sheet, res.responseText);
				$prompt.dispatchEvent($$.createEvent('cerb-sheet--update-selections'));
			}
		});
	});
	{/if}

	{if $layout.style != 'buttons'}
	$prompt.dispatchEvent($$.createEvent('cerb-sheet--update-selections'));
	{/if}
});
</script>