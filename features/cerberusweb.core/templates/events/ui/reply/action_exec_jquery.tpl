<b>jQuery Script:</b>
<div>
<textarea name="{$namePrefix}[jquery_script]" rows="3" cols="45" style="width:100%;" class="placeholders" data-editor-mode="ace/mode/twig_javascript" wrap="off" spellcheck="false">{if !empty($params.jquery_script)}{$params.jquery_script}{else}
{if !empty($default_jquery)}{$default_jquery}{else}var $reply = $(this);
var $form = $reply.find('form');

/*
var $input_is_forward = $form.find('input[name=is_forward]');
var $input_draft_id = $form.find('input[name=draft_id]');
*/

/*
var $input_to = $form.find('input:text[name=to]');
$input_to.val('to');
*/

/*
var $input_cc = $form.find('input:text[name=cc]');
$input_cc.val('cc');
*/

/*
var $input_bcc = $form.find('input:text[name=bcc]');
$input_bcc.val('bcc');
*/

/*
var $input_subject = $form.find('input:text[name=subject]');
$input_subject.val('subject');
*/

/*
var $textarea_reply = $form.find('textarea[name=content]');
$textarea_reply.insertAtCursor('content');

// Move cursor to first blank line
//var txt = $textarea_reply.val();
//var pos = txt.indexOf("\n\n")+2;
//$textarea_reply.setCursorLocation(pos);
*/

/*
var $radio_status = $form.find('input:radio[name=status_id]');
// Change the status (0=open, 1=waiting, 2=closed)
$radio_status.filter(':nth(2)').click();
*/

/*
var $select_bucket = $form.find('select[name=bucket_id]');
$select_bucket.val('t1'); // move to a specific group or bucket
*/

/*
var $select_owner = $form.find('select[name=owner_id]');
$select_owner.val('1'); // set owner to a specific worker ID
$select_owner.nextAll('button:first').click(); // set owner to me
*/

/*
var $input_wait_until = $form.find('input:text[name=ticket_reopen]');
$input_wait_until.val('+12 hours'); // set reopen time
*/

/*
var $btn_watcher = $form.find('button.split-left:first');
// Watch every ticket we reply to (that isn't watched already)
if($btn_watcher.hasClass('green'))
	$btn_watcher.click();
*/

/*
var $fieldset_cfields = $form.find('div.reply-custom-fields');
// Show or hide custom fields entry
//$fieldset_cfields.hide();
//$fieldset_cfields.show();
*/

{/if}{/if}</textarea>
</div>
