var $content = $popup.find('textarea[name=broadcast_message]');

// Broadcast

var $placeholder_menu_trigger = $popup.find('button.cerb-popupmenu-trigger');
var $placeholder_menu = $popup.find('ul.menu').hide();

$placeholder_menu.menu({
	select: function(event, ui) {
		var token = ui.item.attr('data-token');
		var label = ui.item.attr('data-label');
		
		if(undefined == token || undefined == label)
			return;
		
		$content.focus().insertAtCursor('{literal}{{{/literal}' + token + '{literal}}}{/literal}');
	}
});

$placeholder_menu_trigger
	.click(
		function(e) {
			$placeholder_menu.toggle();
		}
	)
;

$popup.find('button.chooser_file').each(function() {
	ajax.chooserFile(this,'broadcast_file_ids');
});

// Text editor

var markitupPlaintextSettings = $.extend(true, { }, markitupPlaintextDefaults);
var markitupParsedownSettings = $.extend(true, { }, markitupParsedownDefaults);

var markitupBroadcastFunctions = {
	switchToMarkdown: function(markItUp) { 
		$content.markItUpRemove().markItUp(markitupParsedownSettings);
		$content.closest('form').find('input:hidden[name=broadcast_format]').val('parsedown');
		
		// Template chooser
		
		var $ul = $content.closest('.markItUpContainer').find('.markItUpHeader UL');
		var $li = $('<li style="margin-left:10px;"></li>');
		
		var $select = $('<select name="broadcast_html_template_id"></select>');
		$select.append($('<option value="0"> - {'common.default'|devblocks_translate|lower|escape:'javascript'} -</option>'));
		
		{foreach from=$html_templates item=html_template}
		var $option = $('<option/>').attr('value','{$html_template->id}').text('{$html_template->name|escape:'javascript'}');
		$select.append($option);
		{/foreach}
		
		$li.append($select);
		$ul.append($li);
	},
	
	switchToPlaintext: function(markItUp) {
		$content.markItUpRemove().markItUp(markitupPlaintextSettings);
		$content.closest('form').find('input:hidden[name=broadcast_format]').val('');
	}
};

markitupPlaintextSettings.markupSet.unshift(
	{ name:'Switch to Markdown', openWith: markitupBroadcastFunctions.switchToMarkdown, className:'parsedown' }
);

markitupPlaintextSettings.markupSet.push(
	{ separator:' ' },
	{ name:'Preview', key: 'P', call:'preview', className:"preview" }
);

var previewParser = function(content) {
	genericAjaxPost(
		'formBatchUpdate',
		'',
		'c=internal&a=viewBroadcastTest',
		function(o) {
			content = o;
		},
		{
			async: false
		}
	);
	
	return content;
};

markitupPlaintextSettings.previewParser = previewParser;
markitupPlaintextSettings.previewAutoRefresh = false;

markitupParsedownSettings.previewParser = previewParser;
markitupParsedownSettings.previewAutoRefresh = false;
delete markitupParsedownSettings.previewInWindow;

markitupParsedownSettings.markupSet.unshift(
	{ name:'Switch to Plaintext', openWith: markitupBroadcastFunctions.switchToPlaintext, className:'plaintext' },
	{ separator:' ' }
);

markitupParsedownSettings.markupSet.splice(
	6,
	0,
	{ name:'Upload an Image', openWith: 
		function(markItUp) {
			$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenFile&single=1',null,true,'750');
			
			$chooser.one('chooser_save', function(event) {
				if(!event.response || 0 == event.response)
					return;
				
				$content.insertAtCursor("![inline-image](" + event.response[0].url + ")");
			});
		},
		key: 'U',
		className:'image-inline'
	}
);

try {
	$content.markItUp(markitupPlaintextSettings);
	
} catch(e) {
	if(window.console)
		console.log(e);
}