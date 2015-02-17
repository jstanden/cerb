<h2>Register now to receive your first 3 seats for FREE.</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_REGISTER}">
<input type="hidden" name="form_submit" value="1">
<input type="hidden" name="skip" value="0">

<h3>You are now running in Evaluation mode</h3>

<div style="margin-left:20px;">
	Each new Cerb installation defaults to Evaluation mode,
	which provides full functionality for this version with no expiration. Without a license you are currently limited to one 
	simultaneous worker (a "seat").<br>
	<br>
	
	<b>If you answer a few questions below to introduce yourself, we'll send you a free license for 3 seats to help you get started.  
	Your license will never expire for <u>this version</u> of the software.</b><br>
	<br>
	
	We hope that you find the software useful, and we're looking forward to growing along with you.<br>
	<br>
	
	For organizations that require more than 3 seats, <a href="http://www.cerberusweb.com/buy/" target="_blank">we offer affordable and flexible licensing
	options</a>.<br>
</div>

<h3>How can we help improve your e-mail management?</h3>

<div style="margin-left:20px;">

	<p style="margin-bottom:0px;">
		We respect the fact that you likely have
		a pile of things to do, and we'd like to make a small part of your day
		easier by helping you quickly find useful information during your
		Cerb evaluation.<br>
		<br>
		If you can take a couple minutes to answer these quick
		questions, we'll be able to focus our conversation on your most important
		goals. If you aren't concerned about particular aspects of our software
		or company, then we won't waste your time talking about them!<br>
		<br>
		So, let's hear about you...<br>
		<br>
	</p>
	
	<b>Your name:</b><br>
	<input size="64" name="contact_name"><br>
	<br>
	
	<b>Your organization:</b><br>
	<input size="64" name="contact_company"><br>
	<br>
	
	<b>Your email address:</b> (this is where we'll send your free 3-seat license)<br>
	<input size="64" name="contact_email"><br>
	<br>
	
	<b>How did you hear about Cerb?</b><br>
	<input size="64" name="contact_refer"><br>
	<br>
	
	<b>Your phone number:</b> (optional)<br>
	<input size="24" name="contact_phone"><br>
	<br>
	
</div>
	
	
<h3>#1: Briefly, what does your organization do?</h3>

<div style="margin-left:20px;">
	<textarea cols="75" rows="3" name="q1"></textarea><br>
</div>
	
<h3>#2: How is your team currently handling shared email, collaboration, and automation?</h3>

<div style="margin-left:20px;">
	<textarea cols="75" rows="3" name="q2"></textarea><br>
</div>
	
<h3>#3: Are you considering both free and commercial solutions?</h3>
<div style="margin-left:20px;">
	<p style="margin-bottom: 0in;">
		<label><input name="q3" value="Free or Paid" checked="checked" type="radio">Yes, both</label> 
		<label><input name="q3" value="Only Free" type="radio">No, only free solutions</label>
		<label><input name="q3" value="Only Paid" type="radio"> No, only commercial solutions</label><br>
	</p>
</div>
	
<h3>#4: What will be your first important milestone to measure the success of your Cerb implementation?</h3>
<div style="margin-left:20px;">
	<textarea cols="75" rows="3" name="q4"></textarea>
</div>
	
<h3>#5: How many workers do you expect to use Cerb simultaneously?</h3>
<div style="margin-left:20px;">
	<label><input type="radio" name="q5" value="1-3" checked="checked"> 1-3</label><br>
	<label><input type="radio" name="q5" value="4-7"> 4-7</label><br>
	<label><input type="radio" name="q5" value="8-15"> 8-15</label><br>
	<label><input type="radio" name="q5" value="16-24"> 16-24</label><br>
	<label><input type="radio" name="q5" value="25+"> 25+</label><br>
</div>

<h3>Is there anything you would like to add?</h3>
<div style="margin-left:20px;">
	<textarea cols="75" rows="3" name="comments"></textarea>
</div>
<br>

<i>Privacy Notice: Being in the email business, we hate spam even more
than most people! We will use the information you provide to personally 
contact you about Cerb. We will not share or sell your 
personal information, or otherwise abuse your trust.</i> <br>
<br>

<button type="button" onclick="this.form.skip.value='1';this.form.submit();">Skip Registration</button>
<input type="submit" value="Register &gt;&gt;">
<br>
<br>

</form>