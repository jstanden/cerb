{$div_id = uniqid()}
<fieldset class="peek black" id="{$div_id}">
	<div class="oauth-button" style="padding:10px;background-color:rgb(69,100,189);color:white;display:inline-block;cursor:pointer;">
		{if $params.oauth_token}
		<b style="color:white;text-decoration:none;">Linked to {$params.screen_name|default:'a Twitter account'}</b>
		{else}
		<b style="color:white;text-decoration:none;">Link to a Twitter account</b>
		{/if}
	</div>
	
	<div class="oauth-params"></div>
</fieldset>

<script type="text/javascript">
$(function() {
	var $container = $('#{$div_id}');
	
	$container.find('div.oauth-button').click(function(e) {
		e.stopPropagation();
		
		window.open('{devblocks_url}ajax.php?c=profiles&a=handleSectionAction&section=connected_account&action=auth&extension_id={$account->extension_id|escape:"url"}{/devblocks_url}&form_id={$div_id}', 'auth', 'width=1024,height=768');
	});
	
	$container.on('oauth-saved', function(e) {
		e.stopPropagation();
		
		$container.find('div.oauth-button b').text('Linked to ' + e.label);
		
		var $hidden = $('<input/>')
			.attr('type', 'hidden')
			.attr('name', 'params[params_json]')
			.val(e.params)
		;
		
		$container.find('div.oauth-params').html('').append($hidden);
	});
});
</script>