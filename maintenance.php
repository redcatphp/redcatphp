<?php
header($_SERVER['SERVER_PROTOCOL'].' 503 Service Unavailable', true, 503);
header('Status: 503 Service Temporarily Unavailable');
header('Retry-After: 3600');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Site Maintenance</title>
</head>
<body>	
	<article>
		<h1>Server is actually in maintenance ...</h1>
		<div>
			<h2>We'll be back soon!</h2>
			<img src="data:image/png;base64,<?php echo base64_encode(file_get_contents('img/276px-Gnu_meditate_levitate.png'));?>" style="margin:0 auto;display:block;">
			<div class="error-details">
				Sorry for the inconvenience but we're performing some maintenance at the moment. We'll be back online shortly!
			</div>
			<input action="action" type="button" value="Retry" onclick="history.go(-1);" />
		</div>
	</article>
</body>
</html>