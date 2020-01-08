{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleProfileTabAction">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="records">

<b>While commenting:</b>

<div style="padding:0 0 0 15px;">
	<label>
		<input type="checkbox" name="comment_disable_formatting" value="1" {if $prefs.comment_disable_formatting}checked="checked"{/if}> Disable formatting by default
	</label>
</div>

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	
	$frm.find('button.submit').on('click', function(e) {
		Devblocks.saveAjaxTabForm($frm);
	});
});
</script>