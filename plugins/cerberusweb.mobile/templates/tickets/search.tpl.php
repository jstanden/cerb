<h2 style="color: rgb(102,102,102);">Search</h2>

<span>
	<form action="/cerberus4/" method="post">
	<input type="hidden" name="c" value="mobile">
	<input type="hidden" name="a" value="tickets">
	<input type="hidden" name="a2" value="search">
	<select name="type">
		<option value="sender">Sender</option>

		<option value="mask">Ticket ID</option>
		<option value="subject">Subject</option>
		<option value="content">Content</option>
	</select><br />
	<input type="text" name="query"><input type="submit" value="go!">
	</form>
</span>