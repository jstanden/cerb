{assign var=headers value=$message->getHeaders()}
<b>Subject:</b> {$headers.subject|escape:"htmlall"}<br>
<b>To:</b> {$headers.to|escape:"htmlall"}<br>
<b>From:</b> {$headers.from|escape:"htmlall"}<br>
<div style="width:98%;height:300px;overflow:auto;border:1px solid rgb(180,180,180);margin:2px;padding:3px;background-color:rgb(255,255,255);" ondblclick="if(null != genericPanel) genericPanel.hide();">
{$content|escape:"htmlall"|nl2br}
</div>
