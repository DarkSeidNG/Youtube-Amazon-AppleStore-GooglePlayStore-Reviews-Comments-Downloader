<?php
error_reporting( E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED );
require_once 'api_model.php';
$api = new Controller();

if ( isset( $_GET[ 'action' ] ) ) {
	if ( $_GET[ 'action' ] == "loadYoutube" ) {
		load_youtube_comments( $api );
	}
}


function load_youtube_comments( $_api ) {
	$video_id = $_GET[ 'vid' ]; //Video Id extracted from the link entered by the user
	$json = @file_get_contents( 'https://www.googleapis.com/youtube/v3/commentThreads?part=snippet&videoId=' . $video_id . '&maxResults=100&key=AIzaSyC7k7Hpjx6NcF2jaBJ6zQyI5asuTTfwWsk' );

	//check if the url returns data - if it doesnt its most probably because the videoID is wrong
	if ( $json === false ) {
		$retunData = json_encode(array("msg" => "failed", "rurl" => " "));
		//header('Content-Type:application/json;charset=utf-8');
		echo $returnData;
	} else {
		$filepath = "../generated/youtube/"; //the location we want to store our csv file in the server 
		$csv_file = "Youtube_" . date( "Y-m-d_H-i", time() ) . ".csv";//create a unique filename for each csv
		$csv_filename = $filepath . $csv_file; 
		$fd = fopen( $csv_filename, "w" );
		fputcsv( $fd, array( 'UserName', 'Date', 'Star rating', 'Review or Comment', 'Link' ) ); //set the header items before populating the csv

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

		$retunData = json_encode(array("msg" => "success", "rurl" => "/generated/youtube/".$csv_file));
		//header('Content-Type:application/json;charset=utf-8');
		echo $retunData;
	}
}

//This function loads 100 more comments from the youtube link and runs in a loop till there is no nextPageToken rceived
function load_more_comments( $dat, $fd, $v_id ) {
	$json2 = @file_get_contents( 'https://www.googleapis.com/youtube/v3/commentThreads?part=snippet&videoId=' . $v_id . '&maxResults=100&pageToken=' . $dat . '&key=AIzaSyC7k7Hpjx6NcF2jaBJ6zQyI5asuTTfwWsk' );

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
	if ( $datat2[ 'nextPageToken' ] != "" ) {
		load_more_comments( $datat2[ 'nextPageToken' ], $fd, $v_id );
	} else {
		fclose( $fd );
	}
}



?>