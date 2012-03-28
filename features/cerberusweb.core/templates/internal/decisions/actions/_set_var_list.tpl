{$div_popup_worklist = uniqid()}
<b>Find objects using this worklist:</b>
<div style="margin:0px 0px 5px 10px;">
	<div id="popup{$div_popup_worklist}" class="badge badge-lightgray" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;"><span class="name">{if !empty($view->name)}{$view->name}{else}Worklist{/if}</span> &#x25be;</div>
	<input type="hidden" name="{$namePrefix}[view_model]" value="{$params.view_model}" class="model">
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
	$('#popup{$div_popup_worklist}').click(function(e) {
		$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenParams&context={$context}&view_id={$view->id}',null,true,'750');
		$chooser.bind('chooser_save',function(event) {
			if(null != event.view_model) {
				$('#popup{$div_popup_worklist}').find('span.name').html(event.view_name);
				$('#popup{$div_popup_worklist}').parent().find('input:hidden.model').val(event.view_model);
			}
		});
	});
</script>