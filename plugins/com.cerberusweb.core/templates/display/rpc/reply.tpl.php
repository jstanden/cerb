<table cellpadding="2" cellspacing="0" border="0" width="100%" class="displayReplyTable">
	<tr>
		<th>Reply</th>
	</tr>
	<tr>
		<td>
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>From:</b></td>
					<td width="100%">[[ agent from ]]</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>To:</b></td>
					<td width="100%">[[ requesters ]]</td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Cc:</b></td>
					<td width="100%"><textarea rows="2" cols="80" class="cc"></textarea></td>
				</tr>
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Bcc:</b></td>
					<td width="100%"><textarea rows="2" cols="80" class="cc"></textarea></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td><textarea rows="10" cols="80" class="reply">{$message->getContent()}</textarea></td>
	</tr>
	<tr>
		<td>
			<table cellpadding="2" cellspacing="0" border="0" width="100%">
				<tr>
					<td width="0%" nowrap="nowrap" valign="top"><b>Actions:</b></td>
					<td width="100%" valign="top">
						Set priority: 
						<label><input type="radio" name="priority" value=""><img src="images/star_alpha.gif"></label>
						<label><input type="radio" name="priority" value=""><img src="images/star_grey.gif"></label>
						<label><input type="radio" name="priority" value=""><img src="images/star_blue.gif"></label>
						<label><input type="radio" name="priority" value=""><img src="images/star_green.gif"></label>
						<label><input type="radio" name="priority" value=""><img src="images/star_yellow.gif"></label>
						<label><input type="radio" name="priority" value=""><img src="images/star_red.gif"></label>
						<br>
						Set status: 
						<label><input type="radio" name="status" value="">open</label>
						<label><input type="radio" name="status" value="">waiting for reply</label>
						<label><input type="radio" name="status" value="">closed</label>
						<label><input type="radio" name="status" value="">deleted</label>
						<br>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<input type="button" value="Send">
			<input type="button" value="Send &amp; Release">
			<input type="button" value="Discard">
		</td>
	</tr>
</table>