<div id="page{$model->id}Config" style="margin-top:10px;">
	{*
	<fieldset class="peek">
		<legend>{'common.layout'|devblocks_translate|capitalize}</legend>
		{include file="devblocks:cerberusweb.core::portals/builder/pages/_common/config/layout.tpl"}
	</fieldset>
	*}

	<fieldset class="peek">
		<legend>{'common.layout'|devblocks_translate|capitalize}</legend>

		<div class="cerb-code-editor-toolbar">

		</div>

		<textarea name="params[layout_kata]" data-editor-mode="ace/mode/cerb_kata">{$model->params.layout_kata}</textarea>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $frm = $('#page{$model->id}Config');
	$frm.find('[data-editor-mode]').cerbCodeEditor();
});
</script>