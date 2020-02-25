{$div_popup_worklist = uniqid()}

<b>Load </b>

<select name="{$namePrefix}[context]" class="context">
	<option value=""> - {'common.choose'|devblocks_translate|lower} - </option>
	{foreach from=$context_mfts item=context_mft key=context_id}
	<option value="{$context_id}" {if $params.context==$context_id}selected="selected"{/if}>{$context_mft->name}</option>
	{/foreach}
</select>

data using 

<div id="popup{$div_popup_worklist}" class="badge badge-lightgray" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;display:inline;"><span class="name">Worklist</span> &#x25be;</div>

<input type="hidden" name="{$namePrefix}[worklist_model_json]" value="{$params.worklist_model|json_encode}" class="model">

<div style="margin:5px 0px 5px 10px;">
	<label>Then filter using quick search:</label>
	
	<div>
		<input type="text" name="{$namePrefix}[quick_search]" value="{$params.quick_search}" class="quicksearch placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="false">
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	
	$action.find('select.context').change(function(e) {
		var ctx = $(this).val();
		
		// [TODO] Hide options until we know the context
		var $select = $(this);
		
		if(0 == ctx.length)
			return;
		
		genericAjaxGet('','c=ui&a=getContextFieldsJson&context=' + ctx, function(json) {
		});
	});
	
	$('#popup{$div_popup_worklist}').click(function(e) {
		var $select = $(this).siblings('select.context');
		var context = $select.val();
		var q = $action.find('input.quicksearch').val();
		
		if(context.length == 0) {
			$select.effect('highlight','slow');
			return;
		}
		
		var $chooser = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=invoke&module=records&action=chooserOpenParams&context='+context+'&view_id={"{$trigger->id}{$namePrefix}_worklist"}&q=' + encodeURIComponent(q),null,true,'90%');
		
		$chooser.bind('chooser_save',function(event) {
			if(null != event.worklist_model) {
				$action.find('input:hidden.model').val(event.worklist_model);
				$action.find('input:text.quicksearch').val(event.worklist_quicksearch);
			}
		});
	});
});
</script>