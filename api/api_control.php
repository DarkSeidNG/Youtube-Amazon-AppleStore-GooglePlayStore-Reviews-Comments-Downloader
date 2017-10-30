<?php
error_reporting( E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED );
include_once "../includes/simple_html_dom.php";

if ( isset( $_GET[ 'action' ] ) ) {
	if ( $_GET[ 'action' ] == "loadYoutube" ) {
		load_youtube_comments();
	}
	if ( $_GET[ 'action' ] == "loadAmazon" ) {
		load_amazon_reviews();
	}
	if ( $_GET[ 'action' ] == "loadApple" ) {
		load_apple_reviews();
	}
	if ( $_GET[ 'action' ] == "loadPlay" ) {
		load_play_reviews();
	}
}

//loads the first 100 comments
function load_youtube_comments() {
	$video_id = $_GET[ 'vid' ]; //Video Id extracted from the link entered by the user
	if ( $video_id != null ) {
		$json = @file_get_contents( 'https://www.googleapis.com/youtube/v3/commentThreads?part=snippet&videoId=' . $video_id . '&maxResults=100&key=YOUR_API_KEY' );

		//check if the url returns data - if it doesnt its most probably because the videoID is wrong
		if ( $json === false ) {
			$retunData = json_encode( array( "msg" => "failed", "rurl" => " " ) );
			//header('Content-Type:application/json;charset=utf-8');
			echo $retunData;
		} else {
			$filepath = "../generated/youtube/"; //the location we want to store our csv file in the server 
			$csv_file = "Youtube_" . date( "Y-m-d_H-i", time() ) . ".csv"; //create a unique filename for each csv
			$csv_filename = $filepath . $csv_file;
			$fd = fopen( $csv_filename, "w" );
			fputcsv( $fd, array( 'UserName', 'Date', 'Star rating', 'Review Comment', 'Link' ) ); //set the header items before populating the csv

			$datat = json_decode( $json, true );

			foreach ( $datat[ 'items' ] as $items ) {
				$rep_strings = array( "\r", "\n", "\r\n" ); //this helps remove new lines to enable us format our csv data properly
				$name = str_replace( $rep_strings, " ", $items[ 'snippet' ][ 'topLevelComment' ][ 'snippet' ][ 'authorDisplayName' ] );
				$date_pub = date_format( date_create( $items[ 'snippet' ][ 'topLevelComment' ][ 'snippet' ][ 'publishedAt' ] ), "F d, Y" );
				$rating = str_replace( $rep_strings, " ", $items[ 'snippet' ][ 'topLevelComment' ][ 'snippet' ][ 'viewerRating' ] );
				$review = str_replace( $rep_strings, " ", $items[ 'snippet' ][ 'topLevelComment' ][ 'snippet' ][ 'textOriginal' ] );
				if ( $rating === "none" ) {
					$rating = "";
				} //check if the rating value is valid

				fputcsv( $fd, array( $name, $date_pub, $rating, $review, '' ) ); //add new csv column
			}
			//check if there is more data to be loaded and if there is load more
			if ( $datat[ 'nextPageToken' ] != "" ) {
				load_more_comments( $datat[ 'nextPageToken' ], $fd, $video_id );
			} else {
				fclose( $fd );
			}

			$retunData = json_encode( array( "msg" => "success", "rurl" => "/generated/youtube/" . $csv_file ) );
			//header('Content-Type:application/json;charset=utf-8');
			echo $retunData;
		}
	}
}

//This function loads 100 more comments from the youtube link and runs in a loop till there is no nextPageToken rceived
function load_more_comments( $dat, $fd, $v_id ) {
	set_time_limit(120);
	$json2 = @file_get_contents( 'https://www.googleapis.com/youtube/v3/commentThreads?part=snippet&videoId=' . $v_id . '&maxResults=100&pageToken=' . $dat . '&key=YOUR_API_KEY' );

	$datat2 = json_decode( $json2, true );
	foreach ( $datat2[ 'items' ] as $item ) {
		$rep_strings = array( "\r", "\n", "\r\n" );
		$name = str_replace( $rep_strings, " ", $item[ 'snippet' ][ 'topLevelComment' ][ 'snippet' ][ 'authorDisplayName' ] );
		$date_pub = date_format( date_create( $item[ 'snippet' ][ 'topLevelComment' ][ 'snippet' ][ 'publishedAt' ] ), "F d, Y" );
		$rating = str_replace( $rep_strings, " ", $item[ 'snippet' ][ 'topLevelComment' ][ 'snippet' ][ 'viewerRating' ] );
		$review = str_replace( $rep_strings, " ", $item[ 'snippet' ][ 'topLevelComment' ][ 'snippet' ][ 'textOriginal' ] );
		if ( $rating === "none" ) {
			$rating = "";
		}
		fputcsv( $fd, array( $name, $date_pub, $rating, $review, '' ) );

	}
	//this will ensure that we keep getting more data till there is no more data to be gotten
	if ( isset($datat2[ 'nextPageToken' ])){
		if ( $datat2[ 'nextPageToken' ] != "" ) {
			load_more_comments( $datat2[ 'nextPageToken' ], $fd, $v_id );
		}
	}else {
		fclose( $fd );
	}
}

//load the reviews on the first page
function load_amazon_reviews() {
	$product_id = $_GET[ 'vid' ]; //Product Id extracted from the link entered by the user
	if ( $product_id != null ) {
		//set a header so that amazon thinks the call is being made by a regular browser
		$opts = stream_context_create( array(
			'http' => array(
				'method' => "GET",
				'header' => "Accept-language: en\r\n".
				"Cookie: foo=bar\r\n".
				"User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" // i.e. An iPad
			)
		) );
		$url = 'https://www.amazon.com/product-reviews/' . $product_id;
		$html = file_get_html( $url, false, $opts );

		if($html->find( '#cm_cr-review_list' ) != null ){
			$authors = [];
			$dates = [];
			$ratings = [];
			$reviews = [];

			//get the innerHtml texts of all tags with the following classes and add them to an array
			foreach ( $html->find( '.author' ) as $au ) {
				$authors[] = [ 'username' => $au->plaintext ];
			}
			foreach ( $html->find( '.review-date' ) as $da ) {
				$dates[] = [ 'date' => $da->plaintext ];
			}
			foreach ( $html->find( '.review-rating' ) as $ra ) {
				$ratings[] = [ 'rating' => $ra->plaintext ];
			}
			foreach ( $html->find( '.review-text' ) as $te ) {
				$reviews[] = [ 'review' => $te->plaintext ];
			}

			$filepath = "../generated/amazon/"; //the location we want to store our csv file in the server 
			$csv_file = "Amazon_" . date( "Y-m-d_H-i", time() ) . ".csv"; //create a unique filename for each csv
			$csv_filename = $filepath . $csv_file;
			$fd = fopen( $csv_filename, "w" );
			fputcsv( $fd, array( 'UserName', 'Date', 'Star rating', 'Review Comment', 'Link' ) ); //set the header items before populating the csv

			//loop through each array and add the data according to its position to the csv file
			$arr_length = count( $authors );
			for ( $i = 0; $i < $arr_length; $i++ ) {
				$rep_strings = array( "\r", "\n", "\r\n" ); //this helps remove new lines to enable us format our csv data properly
				$name = str_replace( $rep_strings, " ", $authors[ $i ][ 'username' ] );
				$date_pub = str_replace( "on ", "", str_replace( $rep_strings, " ", $dates[ $i ][ 'date' ] ) );
				$rating = substr( str_replace( $rep_strings, " ", $ratings[ $i ][ 'rating' ] ), 0, 3 );
				$review = str_replace( $rep_strings, " ", $reviews[ $i ][ 'review' ] );

				fputcsv( $fd, array( $name, $date_pub, $rating, $review, '' ) ); //add new csv column
			}
			if ( $html->find( '.a-last' ) != null ) {
				$html->clear(); //clear the html data
				unset( $html ); //unset the variable
				sleep( rand( 7, 15 ) ); //timeout before retry
				load_more_reviews( $product_id, $fd, 2 );
			} else {
				fclose( $fd );
				//send downloaded file to the user
				$protocol = $_SERVER['HTTPS'] == '' ? 'http://' : 'https://';
				$folder = $protocol . $_SERVER['HTTP_HOST'];
				$file_link_m = $folder."/generated/amazon/" . $csv_file;
				sendMail($_GET['e'],$file_link_m);
				$retunData = json_encode( array( "msg" => "success", "rurl" => "/generated/amazon/" . $csv_file ) );
				//header('Content-Type:application/json;charset=utf-8');
				echo $retunData;
			}
		}
		else{
			$retunDatas = json_encode( array( "msg" => "failed", "rurl" => " " ) );
			//header('Content-Type:application/json;charset=utf-8');
			echo $retunDatas;
		}
	} else {
		$retunDatae = json_encode( array( "msg" => "failed", "rurl" => " " ) );
		//header('Content-Type:application/json;charset=utf-8');
		echo $returnDatae;
	}

}

//loop through the other pages and load more reviews
function load_more_reviews( $product_id, $fd, $_pageNum ) {
	set_time_limit(120);
	$pageNum = $_pageNum;

	$product_id = $_GET[ 'vid' ]; //Product Id extracted from the link entered by the user
	$opts = stream_context_create( array(
		'http' => array(
			'method' => "GET",
			'header' => "Accept-language: en\r\n" .
			"Cookie: foo=bar\r\n" .
			"User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" // i.e. An iPad
		)
	) );
	$url = 'https://www.amazon.com/product-reviews/' . $product_id . '/?pageNumber=' . $pageNum;
	$html = file_get_html( $url, false, $opts );

	if($html->find( '#cm_cr-review_list' ) != null ){
		$authors = [];
		$dates = [];
		$ratings = [];
		$reviews = [];

		foreach ( $html->find( '.author' ) as $au ) {
			$authors[] = [ 'username' => $au->plaintext ];
		}
		foreach ( $html->find( '.review-date' ) as $da ) {
			$dates[] = [ 'date' => $da->plaintext ];
		}
		foreach ( $html->find( '.review-rating' ) as $ra ) {
			$ratings[] = [ 'rating' => $ra->plaintext ];
		}
		foreach ( $html->find( '.review-text' ) as $te ) {
			$reviews[] = [ 'review' => $te->plaintext ];
		}


		$arr_length = count( $authors );
		for ( $i = 0; $i < $arr_length; $i++ ) {
			$rep_strings = array( "\r", "\n", "\r\n" ); //this helps remove new lines to enable us format our csv data properly
			$name = str_replace( $rep_strings, " ", $authors[ $i ][ 'username' ] );
			$date_pub = str_replace( "on ", "", str_replace( $rep_strings, " ", $dates[ $i ][ 'date' ] ) );
			$rating = substr( str_replace( $rep_strings, " ", $ratings[ $i ][ 'rating' ] ), 0, 3 );
			$review = str_replace( $rep_strings, " ", $reviews[ $i ][ 'review' ] );

			fputcsv( $fd, array( $name, $date_pub, $rating, $review, '' ) ); //add new csv column
		}
		if ( $html->find( '.a-last' ) != null ) {
			$html->clear();
			unset( $html );
			sleep( rand( 7, 15 ) ); //this makes the request more random so amazon doesnt flag it as a bot
			load_more_reviews( $product_id, $fd, $pageNum + 1 );
		} else {
			fclose( $fd );
				//send downloaded file to the user
				$protocol = $_SERVER['HTTPS'] == '' ? 'http://' : 'https://';
				$folder = $protocol . $_SERVER['HTTP_HOST'];
				$file_link_m = $folder."/generated/amazon/" . $csv_file;
				sendMail($_GET['e'],$file_link_m);
				$retunData = json_encode( array( "msg" => "success", "rurl" => "/generated/amazon/" . $csv_file ) );
				//header('Content-Type:application/json;charset=utf-8');
				echo $retunData;
		}
		}
		else{
			$retunDatas = json_encode( array( "msg" => "failed", "rurl" => " " ) );
			//header('Content-Type:application/json;charset=utf-8');
			echo $retunDatas;
		}

}

//loads the first set of reviews
function load_apple_reviews() {
	$id = $_GET[ 'vid' ]; //Video Id extracted from the link entered by the user
	if ( $id != null ) {
		$json = @file_get_contents( 'https://itunes.apple.com/rss/customerreviews/id='.$id.'/json' );

		
		//check if the url returns data - if it doesnt its most probably because the videoID is wrong
		if ( $json === false ) {
			$retunData = json_encode( array( "msg" => "failed", "rurl" => " " ) );
			//header('Content-Type:application/json;charset=utf-8');
			echo $retunData;
		} else {
			$filepath = "../generated/apple/"; //the location we want to store our csv file in the server 
			$csv_file = "Apple_" . date( "Y-m-d_H-i", time() ) . ".csv"; //create a unique filename for each csv
			$csv_filename = $filepath . $csv_file;
			$fd = fopen( $csv_filename, "w" );
			fputcsv( $fd, array( 'UserName', 'Date', 'Star rating', 'Review Comment', 'Link' ) ); //set the header items before populating the csv

			$datat2 = json_decode($json,true);

			foreach ( $datat2['feed']['entry'] as $items) {
				if(array_key_exists('author', $items)){
				$rep_strings = array( "\r", "\n", "\r\n" ); //this helps remove new lines to enable us format our csv data properly
				$name = str_replace( $rep_strings, " ", $items['author']['name']['label'] );
				$date_pub = "-";
				$rating = str_replace( $rep_strings, " ", $items['im:rating']['label'] );
				$review = str_replace( $rep_strings, " ", $items['content']['label'] );
				$link = $items['author']['uri']['label'];

				fputcsv( $fd, array( $name, $date_pub, $rating, $review, '' ) ); //add new csv column
					
				}
			}
			//check if there is more data to be loaded and if there is load more
			$next = "";
			foreach ( $datat2['feed'][ 'link' ] as $links){
				if($links['attributes']['rel'] == "next" && $links['attributes']['href'] != ""){
					$next =  $links['attributes']['href'];
				}
			}
			if ( $next != "" ) {
				load_more_apples( 2 , $fd, $id );
			} else {
				fclose( $fd );
			}

			$retunData = json_encode( array( "msg" => "success", "rurl" => "/generated/apple/" . $csv_file ) );
			//header('Content-Type:application/json;charset=utf-8');
			echo $retunData;
		}
	}
	else {
		$retunDatae = json_encode( array( "msg" => "failed", "rurl" => " " ) );
		//header('Content-Type:application/json;charset=utf-8');
		echo $returnDatae;
	}
}

//This function loads more reviews from the apple link and runs in a loop till there is no nextPageToken rceived
function load_more_apples( $dat, $fd, $id ) {
	$i = $dat;
	//set_time_limit(20);
	$json2 = @file_get_contents( 'https://itunes.apple.com/rss/customerreviews/page='.$dat.'/id='.$id.'/json' );

	$datat2 = json_decode( $json2, true );
	
	if ( $json2 === false ) {
	foreach ( $datat2['feed']['entry'] as $items) {
				if(array_key_exists('author', $items)){
				$rep_strings = array( "\r", "\n", "\r\n" ); //this helps remove new lines to enable us format our csv data properly
				$name = str_replace( $rep_strings, " ", $items['author']['name']['label'] );
				$date_pub = "-";
				$rating = str_replace( $rep_strings, " ", $items['im:rating']['label'] );
				$review = str_replace( $rep_strings, " ", $items['content']['label'] );
				$link = $items['author']['uri']['label'];

				fputcsv( $fd, array( $name, $date_pub, $rating, $review, '' ) ); //add new csv column
					
				}
			}
	//this will ensure that we keep getting more data till there is no more data to be gotten
			$next = "";
			foreach ( $datat2['feed'][ 'link' ] as $links){
				if($links['attributes']['rel'] == "next" && $links['attributes']['href'] != ""){
					$next =  $links['attributes']['href'];
				}
			}
			if ( $next != "" ) {
				load_more_apples( $i + 1 , $fd, $id );
			}else {
				fclose( $fd );
	}
	}
}

//load the reviews on the first page
function load_play_reviews() {
	$product_id = $_GET[ 'vid' ]; //Product Id extracted from the link entered by the user
	if ( $product_id != null ) {
		//set a header so that amazon thinks the call is being made by a regular browser
		$opts = stream_context_create( array(
			'http' => array(
				'method' => "GET",
				'header' => "Accept-language: en\r\n".
				"Cookie: foo=bar\r\n".
				"User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" // i.e. An iPad
			)
		) );
		$url = 'https://play.google.com/store/apps/details?id=' . $product_id;
		$html = file_get_html( $url, false, $opts );

		if($html->find( '.details-section-body' ) != null ){
			$authors = [];
			$dates = [];
			$ratings = [];
			$reviews = [];
			$links = [];

			//get the innerHtml texts of all tags with the following classes and add them to an array
			foreach ( $html->find( '.author-name' ) as $au ) {
				$authors[] = [ 'username' => $au->plaintext ];
			}
			foreach ( $html->find( '.review-date' ) as $da ) {
				$dates[] = [ 'date' => $da->plaintext ];
			}
			foreach ( $html->find( '.star-rating-non-editable-container' ) as $ra ) {
				if(isset($ra->attr['aria-label'])){
					$ratings[] = [ 'rating' => $ra->attr['aria-label'] ];
				}
				else{
					$ratings[] = [ 'rating' => "" ];
				}
				
			}
			foreach ( $html->find( '.review-body' ) as $te ) {
				
				$reviews[] = [ 'review' => $te->plaintext ];
			}
			foreach ( $html->find( '.reviews-permalink' ) as $tp ) {
				$links[] = [ 'link' => $tp->href ];
			}

			$filepath = "../generated/play/"; //the location we want to store our csv file in the server 
			$csv_file = "Play_" . date( "Y-m-d_H-i", time() ) . ".csv"; //create a unique filename for each csv
			$csv_filename = $filepath . $csv_file;
			$fd = fopen( $csv_filename, "w" );
			fputcsv( $fd, array( 'UserName', 'Date', 'Star rating', 'Review Comment', 'Link' ) ); //set the header items before populating the csv

			//loop through each array and add the data according to its position to the csv file
			$arr_length = count( $authors );
			for ( $i = 0; $i < $arr_length; $i++ ) {
				$rep_strings = array( "\r", "\n", "\r\n" ); //this helps remove new lines to enable us format our csv data properly
				$name = str_replace( $rep_strings, " ", $authors[ $i ][ 'username' ] );
				$date_pub = str_replace( "on ", "", str_replace( $rep_strings, " ", $dates[ $i ][ 'date' ] ) );
				$rating = $ratings[ $i ][ 'rating' ];
				if($reviews[ $i ]){
					$review = str_replace( $rep_strings, " ", $reviews[ $i ][ 'review' ] );
				}
				else{
					$review = "-";
				}
				if($links[ $i ]){
					$link = $links[ $i ][ 'link' ];
				}
				else{
					$link = "-";
				}
				
				

				fputcsv( $fd, array( $name, $date_pub, $rating, $review, $link ) ); //add new csv column
			}
				fclose( $fd );
				$retunData = json_encode( array( "msg" => "success", "rurl" => "/generated/play/" . $csv_file ) );
				//header('Content-Type:application/json;charset=utf-8');
				echo $retunData;
		}
		else{
			$retunDatas = json_encode( array( "msg" => "faileddd", "rurl" => " " ) );
			//header('Content-Type:application/json;charset=utf-8');
			echo $retunDatas;
		}
	} else {
		$retunDatae = json_encode( array( "msg" => "faileds", "rurl" => " " ) );
		//header('Content-Type:application/json;charset=utf-8');
		echo $returnDatae;
	}

}





function sendMail($email,$link){
				$to = $email;
				$subject = "Your Amazon Reviews Are ready!";
				$message = "Download reviews <a href='".$link."'>Review link</a>";
				$headers = "MIME-Version: 1.0" . "\r\n";
				$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
				$headers .= 'From: <no-reply@iFwAxTeL.com>' . "\r\n";
				$mail = mail( $to, $subject, $message, $headers );
}


?>
