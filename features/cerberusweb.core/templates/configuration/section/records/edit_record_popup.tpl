{$div_id = "peek{uniqid()}"}

<form action="{devblocks_url}{/devblocks_url}" method="POST" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="records">
<input type="hidden" name="action" value="saveRecordPopup">
<input type="hidden" name="context" value="{$context_ext->id}">

<div id="{$div_id}">
	<div id="configCardTabs{$div_id}">
		<ul>
			<li>
				<a href="#configCardTabFields{$div_id}">{'Card Fields'|devblocks_translate|capitalize}</a>
			</li>
			<li>
				<a href="#configCardTabSearch{$div_id}">{'Search Buttons'|devblocks_translate|capitalize}</a>
			</li>
		</ul>
	
		<div id="configCardTabFields{$div_id}">
			<table cellpadding="10" cellspacing="0">
				<tr>
					<td valign="top">
						<b>Fields:</b>
						
						<ul class="bubbles sortable" style="display:block;padding:0;">
							{foreach from=$tokens item=token}
							<li style="display: block; cursor: move; margin: 5px;"><input type="hidden" name="tokens[]" value="{$token}">{$labels.$token}{if '_label' == substr($token, -6)} (Record){/if}<a href="javascript:;" style="position: absolute; visibility: hidden; top: -7px; right: -6px; display: block;"><span class="glyphicons glyphicons-circle-remove"></span></a></li>		
							{/foreach}
						</ul>
					</td>
					
					<td valign="top">
						<b>{'common.add'|devblocks_translate|capitalize}:</b>
						
						{function tree level=0}
							{foreach from=$keys item=data key=idx}
								{if is_array($data->children) && !empty($data->children)}
									<li {if $data->key}data-token="{$data->key}" data-label="{$data->label}"{/if}>
										{if $data->key}
											<div style="font-weight:bold;">{$data->l|capitalize}</div>
										{else}
											<div>{$idx|capitalize}</div>
										{/if}
										<ul>
											{tree keys=$data->children level=$level+1}
										</ul>
									</li>
								{elseif $data->key}
									<li data-token="{$data->key}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
								{/if}
							{/foreach}
						{/function}
						
						<ul class="menu" style="width:250px;">
						{tree keys=$placeholders}
						</ul>
					</td>
				</tr>
			</table>
		</div>
		
		<div id="configCardTabSearch{$div_id}">
			<table cellpadding="3" cellspacing="0" width="100%">
				<thead>
					<tr>
						<td></td>
						<td><b>Record type:</b></td>
						<td><b>Search query to count:</b></td>
					</tr>
				</thead>
				
				{foreach from=$search_buttons item=search_button}
				<tbody>
					<tr>
						<td width="1%" nowrap="nowrap" valign="top">
							<button type="button" onclick="$(this).closest('tbody').remove();"><span class="glyphicons glyphicons-circle-minus"></span></button>
						</td>
						<td width="1%" nowrap="nowrap" valign="top">
							<select class="cerb-search-context" name="search[context][]">
								{foreach from=$search_contexts item=search_context}
								<option value="{$search_context->id}" {if $search_context->id == $search_button.context}selected="selected"{/if}>{$search_context->name}</option>
								{/foreach}
							</select>
							<br>
							<input type="text" name="search[label_singular][]" value="{$search_button.label_singular}" style="width:95%;border-color:rgb(200,200,200);" placeholder="(singular label; optional)">
							<br>
							<input type="text" name="search[label_plural][]" value="{$search_button.label_plural}" style="width:95%;border-color:rgb(200,200,200);" placeholder="(plural label; optional)">
						</td>
						<td width="98%" valign="top">
							<textarea name="search[query][]" style="width:100%;height:60px;" class="cerb-template-trigger" data-context="{$context_ext->id}">{$search_button.query}</textarea>
						</td>
					</tr>
				</tbody>
				{/foreach}
				
				<tbody class="cerb-placeholder" style="display:none;">
					<tr>
						<td width="1%" nowrap="nowrap" valign="top">
							<button type="button" onclick="$(this).closest('tbody').remove();"><span class="glyphicons glyphicons-circle-minus"></span></button>
						</td>
						<td width="1%" nowrap="nowrap" valign="top">
							<select class="cerb-search-context" name="search[context][]">
								{foreach from=$search_contexts item=search_context}
								<option value="{$search_context->id}">{$search_context->name}</option>
								{/foreach}
							</select>
							<br>
							<input type="text" name="search[label_singular][]" style="width:95%;border-color:rgb(200,200,200);" placeholder="(singular label; optional)">
							<br>
							<input type="text" name="search[label_plural][]" style="width:95%;border-color:rgb(200,200,200);" placeholder="(plural label; optional)">
						</td>
						<td width="98%" valign="top">
							<textarea name="search[query][]" style="width:100%;height:60px;" class="cerb-template-trigger" data-context="{$context_ext->id}"></textarea>
						</td>
					</tr>
				</tbody>
			</table>
			
			<button type="button" class="cerb-placeholder-add"><span class="glyphicons glyphicons-circle-plus"></span></button>
		</div>
	</div>

	<div style="margin-top:10px;">
		<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
</div>

</form>

<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	var $popup = genericAjaxPopupFind($div);
	var $layer = $popup.attr('data-layer');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', "{'common.record'|devblocks_translate|capitalize}: {$context_ext->manifest->name}");
		$popup.css('overflow', 'inherit');
		
		var $frm = $popup.find('form');
		
		var $tabs = $('#configCardTabs{$div_id}').tabs();
		
		// Tab: Displayed Fields
		
		var $tab_fields = $('#configCardTabFields{$div_id}');
		var $tab_search = $('#configCardTabSearch{$div_id}');
		
		var $bubbles = $tab_fields.find('ul.bubbles');
		
		var $placeholder_menu = $tab_fields.find('ul.menu').menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');
				
				if(undefined == token || undefined == label)
					return;
				
				var $bubble = $('<li style="display:block;"></li>')
					.css('cursor', 'move')
					.css('margin', '5px')
				;
				
				var $hidden = $('<input>');
				$hidden.attr('type', 'hidden');
				$hidden.attr('name', 'tokens[]');
				$hidden.attr('value', token);
				
				var $a = $('<a href="javascript:;" style="position: absolute; visibility: hidden; top: -7px; right: -6px; display: block;"><span class="glyphicons glyphicons-circle-remove"></span></a>');
				
				$bubble.append($hidden);
				$bubble.append(label);
				$bubble.append($a);
				$bubbles.append($bubble);
			}
		});
		
		$bubbles.on('click', function(e) {
			var $target = $(e.target);
			if($target.is('.glyphicons-circle-remove')) {
				e.stopPropagation();
				$target.closest('li').remove();
			}
		});
		
		$bubbles.on('mouseover', function(e) {
			$bubbles.find('a').css('visibility', 'visible');
		});
		
		$bubbles.on('mouseout', function(e) {
			$bubbles.find('a').css('visibility', 'hidden');
		});
		
		$tab_fields.find('ul.bubbles.sortable').sortable({
			placeholder: 'ui-state-highlight',
			items: 'li',
			distance: 10
		});
		
		// Search buttons
		
		$tab_search_template = $tab_search.find('tbody.cerb-placeholder').detach();
		$tab_search_table = $tab_search.find('> table:first');
		
		$tab_search.find('button.cerb-placeholder-add').on('click', function(e) {
			var $this = $(this);
			var $clone = $tab_search_template.clone();
			
			$clone
				.show()
				.removeClass('cerb-placeholder')
				.appendTo($tab_search_table)
				;
			
			$clone.find('.cerb-template-trigger')
				.cerbTemplateTrigger()
				;
		});
		
		$tab_search.find('> table').sortable({
			tolerance: 'pointer',
			placeholder: 'ui-state-highlight',
			items: 'tbody',
			helper: 'clone',
			opacity: 0.7,
			distance: 10
		});
		
		$popup.find('.cerb-template-trigger')
			.cerbTemplateTrigger()
			;
		
		// Sortable
		
		$popup.find('ul.bubbles')
			.sortable({
				'items': 'li',
				'helper': 'clone',
				'opacity': 0.5,
				'tolerance': 'pointer'
			})
			;
		
		// Submit
		
		$popup.find('button.submit').on('click', function() {
			genericAjaxPost($frm, '', '', function(json) {
				// [TODO] Error handling
				
				var event = new jQuery.Event('popup_saved');
				genericAjaxPopupClose($popup, event);
			});
		});
	});
});
</script>
