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

#bot-chat-button img {
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
</div>

<script type="text/javascript">
$(function() {
	var $console_button = $('#bot-chat-button');
	
	var position = {
		my: "right bottom",
		at: "right-25 bottom-350"
	};
	
	$console_button.click(function() {
		var $popup = genericAjaxPopup('va','c=internal&a=openBotChatChannel', position, false, '300');
		Devblocks.playAudioUrl('');
	});
});
</script>