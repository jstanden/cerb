<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Worklist" class="peek">
		<legend>Worklist:</legend>
		
		<b>Record type:</b>
		
		<div style="margin-left:10px;">
			<select name="params[context]">
				<option value=""></option>
				{foreach from=$context_mfts item=context_mft}
				<option value="{$context_mft->id}" {if $widget->params.context == $context_mft->id}selected="selected"{/if}>{$context_mft->name}</option>
				{/foreach}
			</select>
		</div>
		
		<b>Filter using required query:</b>
		
		<div style="margin-left:10px;">
			<textarea name="params[query_required]" data-editor-mode="ace/mode/cerb_query" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="false">{$widget->params.query_required}</textarea>
		</div>
		
		<b>Default query:</b>
		
		<div style="margin-left:10px;">
			<textarea name="params[query]" data-editor-mode="ace/mode/cerb_query" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="false">{$widget->params.query}</textarea>
		</div>
		
		<b>Records per page:</b>
		
		<div style="margin-left:10px;">
			<input type="text" name="params[render_limit]" value="{$widget->params.render_limit|default:5}" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="false">
		</div>
		
		<b>{'common.color'|devblocks_translate|capitalize}:</b>
		
		<div style="margin-left:10px;">
			<input type="text" name="params[header_color]" value="{$widget->params.header_color|default:'#6a87db'}" class="color-picker">
		</div>
		
		<b>{'dashboard.columns'|devblocks_translate|capitalize}:</b>
		
		<div style="margin-left:10px;">
			<div class="cerb-columns" style="column-width:200px;">
				{foreach from=$columns item=column}
				<div>
					<label>
						{if $column.is_selected}
						<input type="checkbox" name="params[columns][]" value="{$column.key}" checked="checked"> 
						<b>{$column.label}</b>
						{else}
						<input type="checkbox" name="params[columns][]" value="{$column.key}"> 
						{$column.label}
						{/if}
					</label>
				</div>
				{/foreach}
			</div>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $select = $config.find("select[name='params[context]']");
	var $columns = $config.find('div.cerb-columns');
	
	$config.find('input:text.color-picker').minicolors({
		swatches: ['#6a87db','#9a9a9a','#CF2C1D','#FEAF03','#57970A','#9669DB','#626c70']
	});
	
	var $editors = $config.find('textarea[data-editor-mode="ace/mode/cerb_query"]')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteSearchQueries({
			'context': '{$widget->params.context}'
		})
		.nextAll('pre.ace_editor')
		;
	
	$select.on('change', function(e) {
		var ctx = $select.val();
		
		// Update editors
		$editors.trigger('cerb-code-editor-change-context', ctx);
		
		if(0 == ctx.length) {
			$columns.empty();
			return;
		}
		
		// Fetch columns by context
		
		var $spinner = $('<span class="cerb-ajax-spinner"/>').appendTo($columns.empty());
		
		genericAjaxGet('','c=profiles&a=invoke&module=profile_tab&action=getContextColumnsJson&context=' + encodeURIComponent(ctx), function(json) {
			if('object' == typeof(json) && json.length > 0) {
				$columns.empty();
				
				for(idx in json) {
					var field = json[idx];
					
					var $div = $('<div/>').appendTo($columns);
					var $label = $('<label/>').text(' ' + field.label).appendTo($div);
					var $checkbox = $('<input/>').attr('name', 'params[columns][]').attr('type','checkbox').attr('value',field.key).prependTo($label);
					
					$checkbox.on('change', function(e) {
						e.stopPropagation();
						
						var $this = $(this);
						var $label = $this.closest('label');
						if($this.is(':checked')) {
							$label.css('font-weight', 'bold');
						} else {
							$label.css('font-weight', 'normal');
						}
					});
					
					if(true == field.is_selected) {
						$label.css('font-weight', 'bold');
						$checkbox.attr('checked', 'checked');
					}
				}
			}
		});
	});
	
	$columns.sortable({
		tolerance: 'pointer',
		items: 'div',
		helper: 'clone',
		opacity: 0.7
	});
});
</script>