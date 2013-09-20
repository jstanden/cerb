<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmPageWizard" name="frmPageWizard" onsubmit="return false;">
<input type="hidden" name="c" value="pages">
<input type="hidden" name="a" value="savePageWizardPopup">
<input type="hidden" name="view_id" value="{$view_id}">

<fieldset class="peek">
	<legend style="color:rgb(80,80,80);">What kind of page would you like to create?</legend>
	
	<div>
		<label>
			<input type="radio" name="" value="" checked="checked"> 
			<h2 style="display:inline;">Mail</h2>
		</label>
		<div style="margin-left:20px;">
			<div class="tabs">
				<ul>
					<li><a href="#mail_tab1">Inbox</a></li>
					<li><a href="#mail_tab2">Drafts</a></li>
					<li><a href="#mail_tab3">Sent</a></li>
				</ul>
				
				<div id="mail_tab1">
					<div style="margin-bottom:5px;font-weight:bold;color:white;background-color:rgb(100,135,225);padding:5px 10px;border-radius:5px 5px 0px 0px;-moz-border-radius:5px 5px 0px 0px;-webkit-border-radius:5px 5px 0px 0px;">Needs my attention</div>
					<div style="margin-bottom:5px;font-weight:bold;color:white;background-color:rgb(100,135,225);padding:5px 10px;border-radius:5px 5px 0px 0px;-moz-border-radius:5px 5px 0px 0px;-webkit-border-radius:5px 5px 0px 0px;">Needs attention from anyone</div>
				</div>
				<div id="mail_tab2">
					<div style="margin-bottom:5px;font-weight:bold;color:white;background-color:rgb(100,135,225);padding:5px 10px;border-radius:5px 5px 0px 0px;-moz-border-radius:5px 5px 0px 0px;-webkit-border-radius:5px 5px 0px 0px;">My drafts</div>
				</div>
				<div id="mail_tab3">
					<div style="margin-bottom:5px;font-weight:bold;color:white;background-color:rgb(100,135,225);padding:5px 10px;border-radius:5px 5px 0px 0px;-moz-border-radius:5px 5px 0px 0px;-webkit-border-radius:5px 5px 0px 0px;">My sent messages</div>
				</div>
			</div>
		</div>
	</div>
</fieldset>

<div>
	<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmPageWizard','{$view_id}',false,'page_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate}</button>
</div>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title',"Let's make a page...");
		$('#frmPageWizard :input:text:first').focus().select();
		
		$(this).find('div.tabs').tabs({
			selected:0
		});
	});
</script>
