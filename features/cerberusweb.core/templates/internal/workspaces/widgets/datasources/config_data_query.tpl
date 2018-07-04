{$div_id = uniqid()}
<div id="{$div_id}">
	Run this <b>data query</b>:
	<div>
		<textarea name="params[data_query]" placeholder="" class="placeholders">{$widget->params.data_query}</textarea>
	</div>
	
	<br>
	
	Return the value from the <b>{literal}{{json}}{/literal}</b> result with this <b>template</b>:
	<div>
		<textarea name="params[result_template]" placeholder="" class="placeholders">{$widget->params.result_template}</textarea>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	$div.find('textarea').cerbCodeEditor();
});
</script>