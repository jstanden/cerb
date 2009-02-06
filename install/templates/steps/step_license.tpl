<h2>Software License</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_LICENSE}">
<input type="hidden" name="form_submit" value="1">

<H3>Plain English License</H3>

<ul style="line-height:175%;margin:5px;">
	<li>This software is the exclusive property and copyright of <b>WebGroup Media LLC ("WGM")</b></li>
	<li>You may not resell, rebrand or sub-license this software without written permission from WGM.</li>
	<li>You may not remove or alter any copyright notices found in the source code.</li>
	<li>This software is provided to you "as is".</li>
	<li>Under no condition may you hold WGM liable for more than the total payments received from you.</li>
	<li>
		You may create, adapt, sell and distribute improvements to this software pursuant to these conditions:
		<ul>
			<li>WebGroup Media LLC retains full ownership rights to this software.</li>
			<li>The use of this licensed source code is only permitted within the scope of this license.</li>
			<li>Your source code may not be obfuscated, encoded or otherwise not human-readable.</li>
			<li>WebGroup Media LLC disclaims all liability for third-party modifications.</li>
		</ul> 
	</li>
</ul>

<H3>Legal</H3>

<div style="width:550px;height:200px;border:1px solid rgb(160,160,160);overflow:auto;margin:5px;padding:5px;">

<B>LIMITATION OF LIABILITY</B><br>
<br>
THE LICENSED SOFTWARE AND THE DOCUMENTATION ARE PROVIDED TO THE CLIENT AS IS. EXCEPT AS EXPRESSLY STIPULATED HEREIN, WGM MAKES NO REPRESENTATIONS OR WARRANTIES WHATSOEVER, EXPRESS OR IMPLIED, RELATING TO THE USE, PERFORMANCE OR RESULTS WHICH MAY BE OBTAINED THROUGH THE USE OF THE LICENSED SOFTWARE, DOCUMENTATION, TECHNICAL SUPPORT AND UPGRADES. WGM EXPRESSLY DISCLAIMS ALL WARRANTIES, INCLUDING BUT NOT LIMITED TO MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE. WGM SHALL IN NO EVENT BE LIABLE TO THE CLIENT UNDER ANY CIRCUMSTANCES FOR ANY LOST PROFITS OR ANY INDIRECT, SPECIAL, CONSEQUENTIAL OR PUNITIVE DAMAGES INCLUDING, WITHOUT LIMITATION, LOSS OR ALTERATION OF DATA, INTERRUPTION OF BUSINESS AND/OR LOSS OF EMPLOYEE WORK TIME. IN ANY EVENT, THE TOTAL LIABILITY OF WGM SHALL NOT EXCEED THE AGGREGATE PAYMENTS RECEIVED BY WGM HEREUNDER.<br>
<br>
This disclaimer applies without limitation regardless of the form of action, whether in contract, tort (including negligence), strict liability, or otherwise, and regardless of whether such damages are or were foreseeable.<br>
<br>
</div>
<br>
<i>By using this software, you acknowledge having read this license and agree to be bound thereby.</i><br>
<input type="hidden" name="accept" value="0">
<button type="button" onclick="this.form.accept.value='1';this.form.submit();">I Accept</button>
<button type="button">I Decline</button>
<br>
<br>
</form>