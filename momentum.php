<?php
/*
Plugin Name: momentum
Plugin URI: http://www.cloudinternetsolutions.co.za/momentum/
Version: 1.0.1
Description: Adds new content to any page every day automatically and Submits the sitemap to Google and Bing dialy, and updates the post timestamp on every page visit. The Settings are under Wordpress Tools
Author: Gary Erskine
Author URI: http://www.cloudinternetsolutions.co.za
*/

/*  Copyright 2016  Gary Erskine  

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function addm2p_menu() {
	if ( function_exists('add_management_page') ) {
		add_management_page("addm2p", "Momentum to Pages", 'read', __FILE__, 'addm2p_menu_options');
	}
}

function addm2p_menu_options() {
	echo '<div class="wrap">'."\n";
	echo '<h2>Add Momentum to Page</h2>'."\n";
	
		echo '<p>To use this plugin, add this short code to any post. </p>'."\n\n\n";
		echo '<p><h2>[addm2p url=""] </h2></p>'."\n\n\n";
		echo '<p>the Plugin will extract the latest news every day and put a snipit on your page. </p>'."\n\n\n";
		echo '<p>the Plugin will update your posts timestamp on every page view.</p>'."\n\n\n";
		echo '<p>the plugin will submit your sitemap daily to Google and Bing</p>'."\n\n\n";
		echo '<p>There is no other widget settings.</p>'."\n\n\n";
		echo "</div>\n";
		return;
	}
	

function addm2p_css() {
?>
<style type="text/css">
	.addm2p {
		list-style-type: none;
		list-style-image: none;
	}
</style>
<?php
}
function addm2p_post($atts) { //put widgets in enteries.
extract(shortcode_atts(array("url" => ''), $atts));
// get FB page to scrape
$curUrl = $url;
// get wp page id
$postid = get_the_ID();
//here you need to specify the post id in-order to get the post to edit


//$cis_post = $wpdb->get_row("SELECT post_content,post_title FROM $wpdb->posts WHERE ID = $postid");
$cis_post = get_post( $postid ); 
//get the post title and content 

$cis_post_title = get_the_title( $postid );
$cis_post_title = $cis_post->post_title; 


$cis_post_content =get_post( $postid ); 
$cis_post_content = $cis_post->post_content;
$contentfilename = plugin_dir_path( __FILE__ ).'/content.txt';
$savedcontent = file_get_contents($contentfilename);

$cis_edited_post = array(
      'ID'           => $postid,
      'post_title' => $cis_post_title, 
      'post_content' => $cis_post_content
  );

  wp_update_post( $cis_edited_post);
// save or overwrite wp_page_ID.txt in plugin folder

 return $savedcontent;
}



add_action('wp_head','addm2p_css');
add_shortcode('addm2p','addm2p_post');

if ( is_admin() ) add_action('admin_menu','addm2p_menu');

//********************************************************
//					CRON JOB
register_activation_hook(__FILE__, 'cis_activation');

function cis_activation() {
    if (! wp_next_scheduled ( 'cis_daily_event' )) {
	wp_schedule_event(time(), 'daily', 'cis_daily_event');//hourly twicedaily daily
    }
}

add_action('cis_daily_event', 'do_this_daily');

function do_this_daily() {
	// Cron Job Code every day
	// *****************************************************************************
	$url="http://us.cnn.com/?hpt=header_edition-picker";
			$Contentfind ='Top stories';
			$contentstart='headline",":';
			$contentend='","';
date_default_timezone_set('Africa/Johannesburg');//or change to whatever timezone you want
set_time_limit(0);
$timenow=date("Y-m-d H:i:s", time());
    // Defining the basic cURL function
    function curl($url) {		
		//$userAgent='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.94 Safari/537.36';
		$userAgent = 'Googlebot/2.1 (http://www.googlebot.com/bot.html)';
        $ch = curl_init();  // Initialising cURL
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	    curl_setopt($ch, CURLOPT_URL, $url);    // Setting cURL's URL option with the $url variable passed into the function
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); // Setting cURL's option to return the webpage data
        $data = curl_exec($ch); // Executing the cURL request and assigning the returned data to the $data variable
        curl_close($ch);    // Closing cURL
        return $data;   // Returning the data from the function
    }
			$var = curl($url);
			$fbcontent="<p>Breaking Internetional News: ".$timenow."</p><ul>";
				for($i=0;$i<10;$i++)
				{					
					$startpos = strpos($var, $Contentfind);
					if($startpos > 0)
					{
					$var1 =  substr($var , $startpos );  //cut front off
					$startpos = strpos($var1, $contentstart);
						$var2 =  substr($var1 , $startpos+strlen($contentstart) ); //cut front off
						$startpos = strpos($var2, $contentend); 
						$Comment = substr($var2 ,0, $startpos);//First comment
						$CleanComment=strip_tags($Comment, '<p>'); // remove all links but leave p tags
						$fbcontent .= "<li>".$CleanComment."</li>";
						$var = substr($var2 , $startpos ); 
					}				
				}
				$fbcontent .="</ul>";
//echo $fbcontent;
//$contentfilename = $_SERVER['DOCUMENT_ROOT'].'/secret/content.txt';
$contentfilename = plugin_dir_path( __FILE__ ).'/content.txt';
if (!$handle = fopen($contentfilename, 'w')) {	 exit; }
if (fwrite($handle, $fbcontent) === FALSE) {	exit;}
// Send Sitemaps
$siteurl=$_SERVER['SERVER_NAME'];
$url="https://www.google.com/webmasters/sitemaps/ping?sitemap=http://".$siteurl."/sitemap.xml";
	$var = curl($url);
$url="http://www.bing.com/webmaster/ping.aspx?siteMap=".$siteurl."/sitemap.xml";
	$var = curl($url);
  // **************************************************************************
	// end of Cron Job Code
}

// cancel cron if plugin is de activated
register_deactivation_hook(__FILE__, 'cis_deactivation');

function cis_deactivation() {
	wp_clear_scheduled_hook('cis_daily_event');
}
//*******************************************
?>