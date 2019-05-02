<div id="page{$model->id}Config" style="margin-top:10px;">
	<fieldset class="peek">
		<legend>{'common.layout'|devblocks_translate|capitalize}</legend>
		
		{include file="devblocks:cerberusweb.core::portals/builder/pages/_common/config/layout.tpl"}
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $frm = $('#page{$model->id}Config');
});
</script>