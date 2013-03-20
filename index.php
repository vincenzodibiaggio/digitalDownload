<?php

include(__DIR__.'/digitalDownload.php');
use DigitalDownload\DigitalDownload;

$dd = new DigitalDownload();
$dd->install = 0;
$dd->limitByHour = 2;
$dd->limitNumDownload = 5;

if (1 == $dd->install)
{
	$dd->installDigitalDownload();
}
else {
	
	$random = sha1(rand(1,64654654));
	session_start();
	$_SESSION['abracadabra'] = $random;
?>
<html>
	<head>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
		<link rel="stylesheet" type="text/css" href="htmlSrc/style.css">
		<title>Digital Download</title>
		
	</head>
	<body>
		<h2>Insert your code</h2>
		<form action="#" name="dd_form" id="dd_form">
			<input type="hidden" name="code" value="<?php echo $random;?>" />
			<input type="text" name="inputCode" value="...insert your code" />
			<input type="submit" value="Give me contents!" />	
		</form>
		<img id="loader" src="htmlSrc/ajax-loader.gif" alt="ajax loader" class="noDisplay"/>
		<div id="code_result"></div>
		<iframe id="frame" class="noDisplay"></iframe>
	</body>
	<script type="text/javascript">
		$('#dd_form').submit(function(e) {
			e.preventDefault();
			$('#loader').removeClass('noDisplay');
			$('#code_result').html('');
			$.ajax({
				data: $('#dd_form').serialize(),
				url: 'download.php',
				method:'post',
				success: function(data, msg, res){
					var obj = $.parseJSON(data);
					switch (res.status){
						case 200:
							$('#loader').addClass('noDisplay');
							$('#code_result').html('Yeah :)');
							$('#frame').attr('src', obj.message);
							break;
						default:
							$('#loader').addClass('noDisplay');
							$('#code_result').html(obj.message);
							break;
					}
				}
			});

		});
	</script>
</html>

<?php 
}
?>