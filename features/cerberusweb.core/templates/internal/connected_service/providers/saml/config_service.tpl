{$fieldset_id = uniqid()}
<fieldset id="{$fieldset_id}" class="black peek">
	<div>
		<b>Entity ID:</b><br>
		<input type="text" name="params[entity_id]" value="{$params.entity_id}" size="64" spellcheck="false"><br>
	</div>
	
	<div>
		<b>SSO URL:</b><br>
		<input type="text" name="params[url_sso]" value="{$params.url_sso}" size="64" spellcheck="false"><br>
	</div>
	
	<div>
		<b>SLO URL:</b> (optional)<br>
		<input type="text" name="params[url_slo]" value="{$params.url_slo}" size="64" spellcheck="false"><br>
	</div>
	
	<div>
		<b>X.509 Certificate:</b><br>
		<textarea name="params[cert]" cols="64" rows="10" style="" spellcheck="false" data-editor-mode="">{$params.cert}</textarea>
	</div>
</fieldset>

<script type="text/javascript">
$(function() {
	var $fieldset = $('#{$fieldset_id}');
	
	$fieldset.find('input:text,textarea').css('width','100%');
	
	//$fieldset.find('textarea').cerbCodeEditor();
})
</script>