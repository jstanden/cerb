{$div_id = uniqid()}
<div id="{$div_id}">
	Run this <b>data query</b>:
	<div>
		<textarea name="params[data_query]" placeholder="" class="placeholders">{$widget->params.data_query}</textarea>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	$div.find('textarea').cerbCodeEditor();
});
</script>