<?php


?>
<!-- The data encoding type, enctype, MUST be specified as below -->
<form enctype="multipart/form-data" action="dwcaingesthandler.php" method="POST">
	<input type="hidden" name="MAX_FILE_SIZE" value="50000000" />
    <b>Send this file:</b> <input name="uploadfile" type="file" /><br/>

	<b>Key:</b> <input type="text" name="key" value="" style="width:400px" /><br>

	<input type="hidden" name="uploadtype" value="6" />
	<b>Include identification extension:</b> <input type="radio" name="importident" value="1" checked /> Yes <input type="radio" name="importident" value="0" /> No <br>
	<b>Include media extension:</b> <input type="radio" name="importimage" value="1" checked /> Yes <input type="radio" name="importimage" value="0" /> No<br>

	<input type="submit" value="Send File" />
</form>