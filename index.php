<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Bootstrap contact form with PHP example by BootstrapBay.com.">
	<meta name="author" content="BootstrapBay.com">
	<title>C &amp; R Downloader</title>
	<link href="css/style.css" rel="stylesheet" type="text/css">
	<link href="css/bootstrap-3.3.7.css" rel="stylesheet" type="text/css">
</head>

<body>
	<div class="container">
		<div class="row">
			<div class="Absolute-Center is-Responsive">
				<div class="col-sm-12 col-md-10 col-md-offset-1">
					<form id="download_form" class="download_form">
						<div class="form-group input-group">

							<input class="form-control type_selector" type="radio" checked value="youtube" name="content_type" id="youtube_radio" onChange="radioListener(this)"/>
							<label for='youtube_radio'><img height="28" src="images/icons/youtube.png"/>Youtube Comments</label>

							<input class="form-control type_selector" type="radio" value="amazon" name="content_type" id="amazon_radio" onChange="radioListener(this)"/>
							<label for='amazon_radio'><img height="28" src="images/icons/amazon.png"/>Amazon Reviews</label>

						</div>
						<div class="form-group input-group">
							<span class="input-group-addon"><i class="glyphicon glyphicon-globe"></i></span>
							<input class="form-control url_input" type="text" name='web_url' placeholder="Enter Url..."/>
						</div>
						<div class="form-group">
							<button type="submit" class="btn btn-def btn-block submit_but"><img class="loader_image" height="17" src="images/icons/Preloader_3.gif"/> <span class="submit_text">Submit</span></button>
						</div>
					</form>
					<div class="message_alert"><span class="msg_text">Download completed successfully</span> <a class="action_button">Download</a>
					</div>

				</div>
			</div>
		</div>
	</div>


	<script src="js/jquery-1.11.1.min.js"></script>
	<script src="js/bootstrap-3.3.7.js"></script>

	<script type="text/javascript">
		$( '.download_form' ).submit( function () {

			if ( $( "input[type='radio'][name='content_type']:checked" ).val() == 'youtube' ) {
				var urlCheck = checkUrl( $( '.url_input' ).val() );
				if ( urlCheck != "error" ) {
					getYouTubeComments( urlCheck );
				} else {
					alert( 'The url you entered is invalid' );
				}
			} else if ( $( "input[type='radio'][name='content_type']:checked" ).val() == 'amazon' ) {

			}
			return false;
		});



		function radioListener( _this ) {
			if ( $( _this ).val() == "youtube" ) {
				$( '.url_input' ).attr( "placeholder", "Enter Video Url..." );
			} else if ( $( _this ).val() == "amazon" ) {
				$( '.url_input' ).attr( "placeholder", "Enter Product Url..." );
			}
		}

		function checkUrl( _url ) {
			var regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]{11,11}).*/;
			var match = _url.match( regExp );
			if ( match ) {
				if ( match.length >= 2 ) {
					return match[ 2 ];
				} else {
					return "error";
				}
			} else {
				return "error";
			}
		}

		function getYouTubeComments( _vid ) {
			$( '.loader_image' ).show();
			$( '.submit_text' ).html( 'Fetching.....' );
			$.ajax( {
				url: './api/api_control.php?action=loadYoutube&vid=' + _vid,
				async: true,
				dataType: 'json',
				success: function ( data ) {
					var jdata = JSON.parse(data);
					
					if ( jdata[0].msg == 'success' ) {
						$( '.loader_image' ).hide();
						$( '.submit_text' ).html( 'Submit' );
						$( '.msg_text' ).html( "Data retrieved successfully" );
						$( '.message_alert' ).slideDown();
						$( '.action_button' ).show();
						$( '.action_button' ).attr( 'href', jdata[0].data );
					} else if( jdata[0].msg == 'failed' ) {
						$( '.loader_image' ).hide();
						$( '.submit_text' ).html( 'Submit' );
						$( '.msg_text' ).html( "Data retrieval failed" );
						$( '.message_alert' ).slideDown();
					}
				},
				error: function ( result ) {
					var edata = JSON.parse(result);
					alert(edata);
					$( '.loader_image' ).hide();
					$( '.submit_text' ).html( 'Submit' );
					$( '.msg_text' ).html( "An error occured while processing your request please check your connection and try again" );
					$( '.message_alert' ).slideDown();
					$( '.action_button' ).hide();
				}
			} );

		}
	</script>
</body>

</html>