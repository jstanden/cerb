<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmPageWizard" name="frmPageWizard" onsubmit="return false;">
<input type="hidden" name="c" value="pages">
<input type="hidden" name="a" value="savePageWizardPopup">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend style="color:rgb(80,80,80);">What kind of page would you like to create?</legend>
	
	<div style="margin-bottom:10px;">
		<label>
			<input type="radio" name="page_type" value="home" checked="checked"> 
			<h2 style="display:inline;">Home</h2>
		</label>
		<div style="margin-left:20px;">
			<div class="tabs">
				<ul>
					<li><a href="#home_tab1">Overview</a></li>
				</ul>
				
				<div id="home_tab1">
					<div style="display:inline-block;margin-bottom:5px;font-weight:bold;color:white;background-color:rgb(100,135,225);padding:5px 10px;border-radius:5px 5px 0px 0px;-moz-border-radius:5px 5px 0px 0px;-webkit-border-radius:5px 5px 0px 0px;">My tickets</div>
					<div style="display:inline-block;margin-bottom:5px;font-weight:bold;color:white;background-color:rgb(100,135,225);padding:5px 10px;border-radius:5px 5px 0px 0px;-moz-border-radius:5px 5px 0px 0px;-webkit-border-radius:5px 5px 0px 0px;">My tasks</div>
					<div style="display:inline-block;margin-bottom:5px;font-weight:bold;color:white;background-color:rgb(100,135,225);padding:5px 10px;border-radius:5px 5px 0px 0px;-moz-border-radius:5px 5px 0px 0px;-webkit-border-radius:5px 5px 0px 0px;">My calendar</div>
					<div style="display:inline-block;margin-bottom:5px;font-weight:bold;color:white;background-color:rgb(100,135,225);padding:5px 10px;border-radius:5px 5px 0px 0px;-moz-border-radius:5px 5px 0px 0px;-webkit-border-radius:5px 5px 0px 0px;">My reminders</div>
				</div>
			</div>
		</div>
	</div>
	
	<div style="margin-bottom:10px;">
		<label>
			<input type="radio" name="page_type" value="mail"> 
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
					<div style="display:inline-block;margin-bottom:5px;font-weight:bold;color:white;background-color:rgb(100,135,225);padding:5px 10px;border-radius:5px 5px 0px 0px;-moz-border-radius:5px 5px 0px 0px;-webkit-border-radius:5px 5px 0px 0px;">Needs attention</div>
				</div>
				<div id="mail_tab2">
					<div style="display:inline-block;margin-bottom:5px;font-weight:bold;color:white;background-color:rgb(100,135,225);padding:5px 10px;border-radius:5px 5px 0px 0px;-moz-border-radius:5px 5px 0px 0px;-webkit-border-radius:5px 5px 0px 0px;">My drafts</div>
				</div>
				<div id="mail_tab3">
					<div style="display:inline-block;margin-bottom:5px;font-weight:bold;color:white;background-color:rgb(100,135,225);padding:5px 10px;border-radius:5px 5px 0px 0px;-moz-border-radius:5px 5px 0px 0px;-webkit-border-radius:5px 5px 0px 0px;">My sent messages</div>
				</div>
			</div>
		</div>
	</div>
	
	{if DevblocksPlatform::isPluginEnabled('cerberusweb.kb')}
	<div style="margin-bottom:10px;">
		<label>
			<input type="radio" name="page_type" value="kb"> 
			<h2 style="display:inline;">Knowledgebase</h2>
		</label>
		<div style="margin-left:20px;">
			<div class="tabs">
				<ul>
					<li><a href="#reports_tab">Knowledgebase</a></li>
				</ul>
				
				<div id="reports_tab"></div>
			</div>
		</div>
	</div>
	{/if}
	
	{if DevblocksPlatform::isPluginEnabled('cerberusweb.reports')}
	<div style="margin-bottom:10px;">
		<label>
			<input type="radio" name="page_type" value="reports"> 
			<h2 style="display:inline;">Reports</h2>
		</label>
		<div style="margin-left:20px;">
			<div class="tabs">
				<ul>
					<li><a href="#reports_tab">Reports</a></li>
				</ul>
				
				<div id="reports_tab"></div>
			</div>
		</div>
	</div>
	{/if}
	
</fieldset>

<div>
	<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmPageWizard','{$view_id}',false,'page_save');"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
</div>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title',"Let's make a page...");
		$('#frmPageWizard :input:text:first').focus().select();
		
		$(this).find('div.tabs').tabs({
			active:0
		});
	});
});
</script>
