<form action="{devblocks_url}{/devblocks_url}" method="post" target="_blank" id="frm{$view_id}_export">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="viewDoExport">
<input type="hidden" name="view_id" value="{$view_id}">

<h1>{'common.export'|devblocks_translate|capitalize}</h1>
<br>

<div style="margin-bottom:10px;">
	<b>Fields:</b>
	 &nbsp; 
	<a href="javascript:;" class="check-all">select all</a>
	 | 
	<a href="javascript:;" class="check-none">select none</a>
 </div>

<div class="sortable" style="margin:0px 0px 10px 10px;">
{foreach from=$context_labels item=label key=token}
	<div class="drag">
		<label><input type="checkbox" name="tokens[]" value="{$token}"> {$label}</label>
	</div>
{/foreach}
</div>

<div style="margin-bottom:10px;">
	<b>Export List As:</b><br>
	<select name="export_as">
		<option value="csv" selected="selected">Comma-separated values (.csv)</option>
		<option value="json">JSON (.json)</option>
		<option value="xml">XML (.xml)</option>
	</select>
</div>

<button type="button" onclick="this.form.submit();" style=""><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.export'|devblocks_translate|capitalize}</button>
<button type="button" onclick="$('#{$view_id}_tips').html('').hide();" style=""><span class="cerb-sprite2 sprite-cross-circle"></span> Cancel</button>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frm{$view_id}_export');
	
	$frm.find('div.sortable').sortable({
		placeholder: 'ui-state-highlight',
		items: 'div.drag',
		distance: 10
	});
	
	$frm.find('a.check-all').on('click', function() {
		$frm.find('div.sortable input:checkbox').prop('checked', true);
	});
	
	$frm.find('a.check-none').on('click', function() {
		$frm.find('div.sortable input:checkbox').prop('checked', false);
	});
});
</script>