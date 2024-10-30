<?php
// to customize, save as config-custom.php

function list_load_content () {
global $wpdb;
global $rsvp_options;

$u = explode("&load",$_SERVER['REQUEST_URI']);
$uri = $u[0];

if($rsvp_options)
{
$sql = "SELECT *, $wpdb->posts.ID as postID
FROM `".$wpdb->prefix."rsvp_dates`
JOIN $wpdb->posts ON ".$wpdb->prefix."rsvp_dates.postID = $wpdb->posts.ID
WHERE datetime > CURDATE( ) AND $wpdb->posts.post_status = 'publish'
ORDER BY datetime";

$wpdb->show_errors();
$results = $wpdb->get_results($sql, ARRAY_A);
if($results)
{
	$headlines .= "<h3>Upcoming Events</h3>\n".'<p><a href="'.$uri.'&load=rsvpmaker&events=1">Load All</a></p>'."\n";



foreach ($results as $row)
	{
	$t = strtotime($row["datetime"]);
	$date = date('F jS, Y',$t);
	$headlines .= sprintf("<p><a href=\"$uri&load=rsvpmaker&loadpost=%d\">%s %s</a></p>\n",$row["postID"],$row["post_title"], $date);
	}

} // if events
} // if rsvpmaker is active

$sql = "SELECT ID, post_title FROM $wpdb->posts WHERE post_status='publish' AND post_type='post' ORDER BY post_date DESC LIMIT 0, 10";
$wpdb->show_errors();
$results = $wpdb->get_results($sql, ARRAY_A);
if($results)
{
$headlines .= "<h3>Recent Blog Posts</h3>";

foreach ($results as $row)
	{
	$headlines .= sprintf("<p><a href=\"$uri&load=post&loadpost=%d\">%s</a></p>\n",$row["ID"],$row["post_title"]);
	}

} // end if posts


return $headlines;


}

function default_chimpblast_content($content) {

// load in content from events or blogs
if(!$_GET["load"])
	return $content;

ob_start();
$type = $_GET["load"];

if($_GET["events"])
	$content .= file_get_contents(plugins_url().'/chimpblast/loadpost.php?load=rsvpmaker&events=1');
elseif($loadpost = $_GET["loadpost"])
	$content .= file_get_contents(plugins_url().'/chimpblast/loadpost.php?load='.$type.'&loadpost='.$loadpost);

$content = "&nbsp;\n\n".$content;

return $content;
}


; ?>