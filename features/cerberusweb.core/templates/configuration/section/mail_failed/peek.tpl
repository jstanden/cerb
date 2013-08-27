<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupMailFailed" onsubmit="return false;" enctype="multipart/form-data">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="mail_failed">
<input type="hidden" name="action" value="savePeekPopup">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<b>storage/mail/fail/{$filename}:</b>
<div>
	<input type="hidden" name="file" value="{$filename}">
	<iframe src="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=mail_failed&action=getRawMessageSource&file={$filename}{/devblocks_url}" style="width:100%;height:250px;margin:0;padding:0;border:1px solid rgb(150,150,150);"></iframe>
</div>

<div class="output" style="display:none;"></div>

<fieldset class="delete" style="display:none;margin-top:5px;">
	<legend>Delete this message?</legend>
	<p>Are you sure you want to permanently delete this message source?</p>
	
	<div>
		<button type="button" class="green delete"> {'common.yes'|devblocks_translate|capitalize}</button>
		<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('div.toolbar').show();"> {'common.no'|devblocks_translate|capitalize}</button>
	</div>
</fieldset>

<div style="margin:10px 0px;" class="toolbar">
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.retry'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('div.toolbar').hide().prev('fieldset.delete').show();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>
</div>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		var $this = $(this);
		
		$this.dialog('option','title',"{'Failed Message'}");
		
		$this.find('iframe').load(function() {
			$(this).contents().find('pre').css('white-space','').css('word-wrap','');
		});
		
		$this.find('textarea:first').focus();
		
		$this.find('button.submit').click(function(e) {
			var $frm = $(this).closest('form');
			
			$frm.find('input:hidden[name=section]').val('mail_failed');
			$frm.find('input:hidden[name=action]').val('parseMessageJson');
			
			genericAjaxPost($frm, '', '', function(json) {
				var $frm = $('#frmSetupMailFailed');
				var $output = $frm.find('div.output');
				
				// If successful, reload worklist
				if(undefined != json.status && true == json.status) {
					genericAjaxGet('view{$view_id}', 'c=internal&a=viewRefresh&id={$view_id}');
					genericAjaxPopupClose('peek');
					
				// If an error, display it
				} else if(undefined != json.status && false == json.status) {
					var message = (undefined != json.log && json.log.length > 0) ? json.log : json.message;
					Devblocks.showError($output, message, false, true);
					
				}
			});
		});
		
		$this.find('button.delete').click(function(e) {
			var $frm = $(this).closest('form');

			$frm.find('input:hidden[name=section]').val('mail_failed');
			$frm.find('input:hidden[name=action]').val('deleteMessageJson');
			$frm.find('textarea[name=message_content]').val('');

			genericAjaxPost($frm, '', '', function(json) {
				var $frm = $('#frmSetupMailFailed');
				var $output = $frm.find('div.output');
				
				// If successful, reload worklist
				if(undefined != json.status && true == json.status) {
					genericAjaxGet('view{$view_id}', 'c=internal&a=viewRefresh&id={$view_id}');
					genericAjaxPopupClose('peek');
					
				// If an error, display it
				} else if(undefined != json.status && false == json.status) {
					var message = (undefined != json.log && json.log.length > 0) ? json.log : json.message;
					Devblocks.showError($output, message, false, true);
					
				}
			});
		});
	} );
</script>