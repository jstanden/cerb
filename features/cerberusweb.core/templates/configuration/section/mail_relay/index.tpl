<h2>External Mail Relay</h2>

<form id="frmSetupMailRelay" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="mail_relay">
<input type="hidden" name="action" value="saveJson">

<p>
The email relay enables workers to respond to messages from external mail applications (e.g. Gmail, mobile phones, Outlook, etc) instead of always requiring them to use Cerb in the web browser.  
Relayed responses are received from a worker's personal email address and rewritten so they appear to be from Cerb before being sent to a conversation's recipients.  
This process protects the privacy of personal worker email addresses, while still providing the benefits of Cerb (e.g. shared history, assignments, etc).
</p>

<fieldset>
	<legend>Authentication</legend>
	<p>
	By default, relayed messages are authenticated by checking the mail headers.  
	Copies of mail that are relayed to workers outside of Cerb using Virtual Attendant behavior are "signed" with a secret key in the <tt>Message-Id:</tt> header.  
	According to the RFC-2822 standard, this <tt>Message-Id:</tt> should be referenced in the <tt>In-Reply-To:</tt> header of any reply.
	</p>
	
	<p>
	Unfortunately, some email applications "break the Internet" by ignoring these many decade old conventions.  
	Common culprits include Microsoft Exchange and some Android and Blackberry mobile devices.  
	</p>

	<p>	
	In the event that the worker relay doesn't function properly in your environment, you may disable the built-in authentication.
	<b>Be careful when doing this!</b>  
	When authentication is disabled, anyone can forge a message <tt>From:</tt> one of your workers and have it relayed to arbitrary conversations.  
	It is very important that you set up alternative authentication using Virtual Attendants in Mail Filtering to approve or deny inbound worker replies through the relay.  
	</p>
	
	<p>
	See the <a href="http://cerbweb.com/book/latest/cookbook" target="_blank">cookbook</a> for more information.
	</p>
	<br>
	
	<b>Built-in relay authentication:</b>
	{$relay_disable_auth = $settings->get('cerberusweb.core','relay_disable_auth',CerberusSettingsDefaults::RELAY_DISABLE_AUTH)}
	<p>
		<label><input type="radio" name="relay_disable_auth" value="0" {if empty($relay_disable_auth)}checked="checked"{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="relay_disable_auth" value="1" {if $relay_disable_auth}checked="checked"{/if}> {'common.disabled'|devblocks_translate|capitalize}</label>
	</p>
</fieldset>

<div class="status"></div>

</form>

<script type="text/javascript">
	$('#frmSetupMailRelay INPUT:radio')
		.click(function(e) {
			genericAjaxPost('frmSetupMailRelay','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmSetupMailRelay div.status',$o.error);
				}
			});
		})
	;
	;	
</script>