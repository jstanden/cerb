<style type="text/css">
#bot-chat-button {
	z-index:3;
	position:fixed;
	width:60px;
	height:60px;
	bottom:32px;
	right:32px;
}

#bot-chat-button > div {
	width:60px;
	height:60px;
	background-color:rgb(250,250,250);
	box-shadow:0 0 10px 0px rgb(150,150,150);
	border-radius:60px;
	cursor:pointer;
}

#bot-chat-button > div > img {
	width:50px;
	height:50px;
	position:relative;
	top:5px;
	left:7px;
	border:0;
}
</style>

<div id="bot-chat-button">
	<div>
		<img src="{devblocks_url}c=avatars&context=app&id=0{/devblocks_url}">
	</div>
	{function menu level=0}
		{foreach from=$keys item=data key=idx}
			{if is_array($data->children) && !empty($data->children)}
				<li>
					<div style="font-weight:bold;">
						{if $data->image}
						<img class="cerb-avatar" src="{$data->image}">
						{/if}
						{$data->label}
					</div>
					<ul style="width:300px;">
						{menu keys=$data->children level=$level+1}
					</ul>
				</li>
			{elseif !is_null($data->key)}
				<li class="cerb-bot-trigger" data-interaction="{$data->interaction}" data-behavior-id="{$data->key}"{foreach from=$data->params item=param_value key=param_key} data-interaction-param-{$param_key}="{$param_value}"{/foreach}>
					<div style="font-weight:bold;">
						{$data->label}
					</div>
				</li>
			{/if}
		{/foreach}
	{/function}
	
	<ul class="cerb-bot-interactions-menu cerb-float" style="display:none; width:250px;">
	{menu keys=$global_interactions_menu}
	</ul>
</div>

<script type="text/javascript">
$(function() {
	var $interaction_container = $('#bot-chat-button');
	var $interaction_button = $interaction_container.find('> div');
	var $interaction_menu = $interaction_container.find('> ul').hide();
	
	$interaction_button.on('click', function(e) {
		Devblocks.playAudioUrl('');
		$interaction_menu.toggle();
	});
	
	$interaction_menu
		.menu({
			//icons: { submenu: "ui-icon-circle-triangle-e" },
			position: { my: "right middle", at: "left middle" }
		})
		.css('position', 'absolute')
		.css('right', '0')
		.css('bottom', '50px')
	;
	
	$interaction_menu.find('li.cerb-bot-trigger')
		.cerbBotTrigger()
		.on('click', function(e) {
			e.stopPropagation();
			$interaction_menu.menu( "collapse");
		});
});
</script>