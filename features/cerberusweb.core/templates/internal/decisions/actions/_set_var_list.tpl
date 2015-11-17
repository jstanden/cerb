{$uniq_id = uniqid()}
<b>Find records using this worklist:</b>
<div style="margin:0px 0px 5px 10px;">
	<div id="popup{$uniq_id}" class="badge badge-lightgray" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;"><span class="name">{if !empty($view->name)}{$view->name}{else}Worklist{/if}</span> &#x25be;</div>
	<input type="hidden" name="{$namePrefix}[worklist_model_json]" value="{$params.worklist_model|json_encode}" class="model">
	
	<div style="margin-top:10px;">
		<label><input type="checkbox" name="{$namePrefix}[search_mode]" value="quick_search" class="mode" {if $params.search_mode == "quick_search"}checked="checked"{/if}> <b>and filter using quick search:</b></label>
		<div style="margin-left:20px;">
			<input type="text" name="{$namePrefix}[quick_search]" value="{$params.quick_search}" class="quicksearch placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="off">
		</div>
	</div>
</div>

<b>Limit to:</b>
<div style="margin:0px 0px 5px 10px;">
	<select name="{$namePrefix}[limit]" id="select{$uniq_id}">
		<option value="" {if empty($params.limit)}selected="selected"{/if}>All objects</option>
		<option value="first" {if $params.limit=='first'}selected="selected"{/if}>First</option>
		<option value="last" {if $params.limit=='last'}selected="selected"{/if}>Last</option>
		<option value="random" {if $params.limit=='random'}selected="selected"{/if}>Random</option>
	</select>
	<span style="{if empty($params.limit)}display:none;{/if}">
		<input type="text" name="{$namePrefix}[limit_count]" size="2" maxlength="2" value="{$params.limit_count|default:'10'}">
	</span>
	<br>
</div>

<b>Then:</b>
<div style="margin:0px 0px 5px 10px;">
	<select name="{$namePrefix}[mode]">
		<option value="add" {if !isset($params.mode) || $params.mode=='add'}selected="selected"{/if}>Add these objects to the variable</option>
		<option value="subtract" {if $params.mode=='subtract'}selected="selected"{/if}>Remove these objects from the variable</option>
		<option value="replace" {if $params.mode=='replace'}selected="selected"{/if}>Replace the variable with these objects</option>
	</select>
</div>

<script type="text/javascript">
$(function() {
	var $div = $('#popup{$uniq_id}');
	var $parent = $div.parent();
	var $popup = $div.closest('.ui-dialog');
	
	$div.click(function(e) {
		var width = $(window).width()-100;
		var $mode = $popup.parent().find('input.mode');
		var q = '';
		
		if($mode.is(':checked')) {
			q = $parent.find('input.quicksearch').val();
		}
		
		var $chooser = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpenParams&context={$context}&view_id={$view->id}&trigger_id={$trigger->id}&q=' + encodeURIComponent(q),null,true,width);
		
		$chooser.on('chooser_save',function(event) {
			if(null != event.worklist_model) {
				$div.find('span.name').text(event.view_name);
				$parent.find('input:hidden.model').val(event.worklist_model);
				$parent.find('input.quicksearch').val(event.worklist_quicksearch);
			}
		});
	});
	
	var $select = $('#select{$uniq_id}');
	
	$select.change(function(e) {
		var val = $(this).val();
		
		if(val.length > 0)
			$(this).next('span').show();
		else
			$(this).next('span').hide();
	});
	
	$popup.css('overflow', 'inherit');
});
</script>