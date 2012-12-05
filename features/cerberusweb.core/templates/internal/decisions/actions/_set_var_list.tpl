{$uniq_id = uniqid()}
<b>Find objects using this worklist:</b>
<div style="margin:0px 0px 5px 10px;">
	<div id="popup{$uniq_id}" class="badge badge-lightgray" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;"><span class="name">{if !empty($view->name)}{$view->name}{else}Worklist{/if}</span> &#x25be;</div>
	<input type="hidden" name="{$namePrefix}[view_model]" value="{$params.view_model}" class="model">
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
	$div = $('#popup{$uniq_id}');
	
	$div.click(function(e) {
		$chooser=genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpenParams&context={$context}&view_id={$view->id}&trigger_id={$trigger->id}',null,true,'750');
		$chooser.bind('chooser_save',function(event) {
			if(null != event.view_model) {
				$('#popup{$uniq_id}').find('span.name').html(event.view_name);
				$('#popup{$uniq_id}').parent().find('input:hidden.model').val(event.view_model);
			}
		});
	});
	
	$select = $('#select{$uniq_id}');
	
	$select.change(function(e) {
		val=$(this).val();
		
		if(val.length > 0)
			$(this).next('span').show();
		else
			$(this).next('span').hide();
	});
</script>