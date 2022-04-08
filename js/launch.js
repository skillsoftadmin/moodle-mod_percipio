function callajax(Y,launchurl,sesskey) {
	$('.launch_course').on("click", function(e){
		e.preventDefault();
		$('.loader_background').show();
		$.ajax({type: "POST", url: "getlaunchurl.php",  data: { "url":launchurl, "sesskey":sesskey }}).done(function( result ) {
			$('.loader_background').hide();
			if(result != '') {
				window.open(result);
			} else {
				$('.show_error').show();
			}
		});
	});
}