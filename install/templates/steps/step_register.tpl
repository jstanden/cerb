<h2>Register now for a *free* Community Edition license and 90 days of product updates.</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_REGISTER}">
<input type="hidden" name="form_submit" value="1">
<input type="hidden" name="skip" value="0">

<H3>You are now running the Evaluation Edition</H3>

Each new Cerberus Helpdesk installation defaults to the Evaluation Edition,
which provides full functionality for this version with no expiration. Without a license you are currently limited to 1
simultaneous worker (a "seat").<br>
<br>

<b>If you answer a few questions below to introduce yourself, we'll send you a *free* Community Edition license, which provides 
full functionality for 3 seats with 90 days of product updates.  It's the same thing you'd receive for buying 3 seats from our online shop.  It will never expire for this version.  There's no catch.</b><br>
<br>

We hope that you find the software useful, and we're looking forward to growing along with you.<br>
<br>

For organizations that require more than 3 seats, <a href="http://www.cerberusweb.com/buy/" target="_blank">we offer affordable and flexible licensing
options</a>.<br>

<H3>How can we help improve your e-mail management?</H3>

<p style="margin-bottom:0px;">
	We respect the fact that you likely have
	a pile of things to do, and we'd like to make a small part of your day
	easier by helping you quickly find useful information during your
	Cerberus Helpdesk evaluation.<br>
	<br>
	If you can take a couple minutes to answer these quick
	questions we'll be able to focus our conversation on your most important
	goals. If you aren't concerned about particular aspects of our software
	or company then we won't waste your time talking about them!<br>
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

<b>Your e-mail address:</b> (this is where we'll send your discount coupon)<br>
<input size="64" name="contact_email"><br>
<br>

<b>How did you hear about Cerberus Helpdesk?</b><br>
<input size="64" name="contact_refer"><br>
<br>

<b>Your phone number:</b><br>
<input size="24" name="contact_phone">(optional)<br>
<br>


<h3>#1: Briefly, what does your organization do?</h3>
<textarea cols="65" rows="3" name="q1"></textarea><br>

<h3>#2: How is your team currently handling e-mail management?</h3>
<textarea cols="65" rows="3" name="q2"></textarea><br>

<h3>#3: Are you considering both free and commercial solutions?</h3>
<p style="margin-bottom: 0in;">
	<label><input name="q3" value="Free or Paid" checked="checked" type="radio">Yes, both</label> 
	<label><input name="q3" value="Only Free" type="radio">No, only free solutions</label>
	<label><input name="q3" value="Only Paid" type="radio"> No, only commercial solutions</label><br>
</p>

<h3>#4: What will be your first important milestone to measure the success of your new helpdesk implementation?</h3>
<textarea cols="65" rows="3" name="q4"></textarea><br>

<h3>#5: How important are the following benefits in making your decision?</h3>
<table border="0" cellpadding="2" cellspacing="2" width="100">
	<tbody>
		<tr>
			<td></td>
			<td colspan="2" rowspan="1" nowrap="nowrap"><b>&laquo; Low</b></td>
			<td colspan="1"></td>
			<td rowspan="1" colspan="2" align="right" nowrap="nowrap"><b>High
			&raquo;</b></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Near-Instant Support</td>
			<td align="center" nowrap="nowrap"><input name="q5_support" value="1"
				type="radio"></td>
			<td align="center"><input name="q5_support" value="2" type="radio"></td>
			<td align="center"><input name="q5_support" value="3"
				checked="checked" type="radio"></td>
			<td align="center"><input name="q5_support" value="4" type="radio"></td>
			<td align="center" nowrap="nowrap"><input name="q5_support" value="5"
				type="radio"></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Available Source Code</td>
			<td align="center" nowrap="nowrap"><input name="q5_opensource"
				value="1" type="radio"></td>
			<td align="center"><input name="q5_opensource" value="2" type="radio"></td>
			<td align="center"><input name="q5_opensource" value="3"
				checked="checked" type="radio"></td>
			<td align="center"><input name="q5_opensource" value="4" type="radio"></td>
			<td align="center" nowrap="nowrap"><input name="q5_opensource"
				value="5" type="radio"></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Competitive Purchase Price</td>
			<td align="center" nowrap="nowrap"><input name="q5_price" value="1"
				type="radio"></td>
			<td align="center"><input name="q5_price" value="2" type="radio"></td>
			<td align="center"><input name="q5_price" value="3" checked="checked"
				type="radio"></td>
			<td align="center"><input name="q5_price" value="4" type="radio"></td>
			<td align="center" nowrap="nowrap"><input name="q5_price" value="5"
				type="radio"></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Frequent Product Updates</td>
			<td align="center" nowrap="nowrap"><input name="q5_updates" value="1"
				type="radio"></td>
			<td align="center"><input name="q5_updates" value="2" type="radio"></td>
			<td align="center"><input name="q5_updates" value="3"
				checked="checked" type="radio"></td>
			<td align="center"><input name="q5_updates" value="4" type="radio"></td>
			<td align="center" nowrap="nowrap"><input name="q5_updates" value="5"
				type="radio"></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Access to Developers</td>
			<td align="center" nowrap="nowrap"><input name="q5_developers"
				value="1" type="radio"></td>
			<td align="center"><input name="q5_developers" value="2" type="radio"></td>
			<td align="center"><input name="q5_developers" value="3"
				checked="checked" type="radio"></td>
			<td align="center"><input name="q5_developers" value="4" type="radio"></td>
			<td align="center" nowrap="nowrap"><input name="q5_developers"
				value="5" type="radio"></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Large User Community</td>
			<td align="center" nowrap="nowrap"><input name="q5_community"
				value="1" type="radio"></td>
			<td align="center"><input name="q5_community" value="2" type="radio"></td>
			<td align="center"><input name="q5_community" value="3"
				checked="checked" type="radio"></td>
			<td align="center"><input name="q5_community" value="4" type="radio"></td>
			<td align="center" nowrap="nowrap"><input name="q5_community"
				value="5" type="radio"></td>
		</tr>
	</tbody>
</table>
<br>

<h3>Is there anything you would like to add?</h3>
<textarea cols="65" rows="3" name="comments"></textarea>
<br>

<i>Privacy Notice: Being in the e-mail business, we hate spam even more
than most people! We will use the information you provide to personally 
contact you about Cerberus Helpdesk. We will not share or sell your 
personal information, or otherwise abuse your trust.</i> <br>
<br>

<button type="button" onclick="this.form.skip.value='1';this.form.submit();">Skip Registration</button>
<input type="submit" value="Register &gt;&gt;">
<br>
</form>
<br>
