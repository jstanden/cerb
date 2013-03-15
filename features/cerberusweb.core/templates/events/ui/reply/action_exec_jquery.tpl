<b>jQuery Script:</b>
<div>
<textarea name="{$namePrefix}[jquery_script]" rows="3" cols="45" style="width:100%;" class="placeholders">{if !empty($params.jquery_script)}{$params.jquery_script}{else}
var $reply = $(this);
var $form = {
	fields: $reply.find('form:nth(0)'),
	actions: $reply.find('form:nth(1)')
};

/*
var $input_is_forward = $form.actions.find('input[name=is_forward]');
var $input_draft_id = $form.actions.find('input[name=draft_id]');
*/

/*
var $input_to = $form.fields.find('input:text[name=to]');
$input_to.val('to');
*/

/*
var $input_cc = $form.fields.find('input:text[name=cc]');
$input_cc.val('cc');
*/

/*
var $input_bcc = $form.fields.find('input:text[name=bcc]');
$input_bcc.val('bcc');
*/

/*
var $input_subject = $form.fields.find('input:text[name=subject]');
$input_subject.val('subject');
*/

/*
var $textarea_reply = $form.actions.find('textarea[name=content]');
$textarea_reply.insertAtCursor('content');

// Move cursor to first blank line
//var txt = $textarea_reply.val();
//var pos = txt.indexOf("\n\n")+2;
//$textarea_reply.setCursorLocation(pos);
*/

/*
var $radio_status = $form.actions.find('input:radio[name=closed]');
// Change the status (0=open, 1=waiting, 2=closed)
$radio_status.filter(':nth(2)').click();
*/

/*
var $select_bucket = $form.actions.find('select[name=bucket_id]');
$select_bucket.val('t1'); // move to a specific group or bucket
*/

/*
var $select_owner = $form.actions.find('select[name=owner_id]');
$select_owner.val('1'); // set owner to a specific worker ID
$select_owner.nextAll('button:first').click(); // set owner to me
*/

/*
var $input_wait_until = $form.actions.find('input:text[name=ticket_reopen]');
$input_wait_until.val('+12 hours'); // set reopen time
*/

/*
var $btn_watcher = $form.actions.find('button.split-left:first');
// Watch every ticket we reply to (that isn't watched already)
if($btn_watcher.hasClass('green'))
	$btn_watcher.click();
*/

/*
var $fieldset_cfields = $form.actions.find('fieldset:nth(2)');
// Hide the custom fields section
$fieldset_cfields.hide();
*/

{/if}</textarea>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>