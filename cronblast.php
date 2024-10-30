<?php

//attempt to locate wp-config.php
$pathparts = explode('/',$_SERVER['PATH_TRANSLATED']);	
$dir = '/';
foreach($pathparts as $index => $part)
	{
	if($index < 1)
		continue;
	$dir .= $part . '/';
	$wp_config = $dir.'wp-config.php';
	if(@file_exists($wp_config) )
		{
		require($wp_config);
		break;
		}
	}

global $wpdb;

$today = date('Y-m-d' );
$blastday_stamp = strtotime($chimp_options["cron-day"]);
$blastday = date('Y-m-d',$blastday_stamp);

if($today == $blastday)
	{
	$preview = false;
	}
else
	{
	$preview = true;
	}

global $rsvp_options;
if($rsvp_options)
$sql = "SELECT datetime FROM ".$wpdb->prefix."rsvp_dates JOIN $wpdb->posts ON $wpdb->posts.ID=".$wpdb->prefix."rsvp_dates.postID WHERE datetime > CURDATE() AND $wpdb->posts.post_status='publish' ";
if( $wpdb->get_var($sql) )
	$events_ok = true;

$chimp_options = get_option('chimp');

ob_start();
$last_stamp = strtotime('last '.$chimp_options["cron-day"]);
$last_blastday = date('Y-m-d 00:00:01',$last_stamp);

//based on Austin Matzko's code from wp-hackers email list
function filter_where($where = '') {
    if(strpos($where,'rsvpmaker') )
		return $where; // don't interfere with RSVPMaker
	global $last_blastday;
	//posts in the last 30 days
    $where .= " AND post_date > '" . $last_blastday . "'";
    return $where;
  }
add_filter('posts_where', 'filter_where',20);
query_posts('pagename=blog');

if (have_posts()) : 
	$content_ok = true;
?>

<h3 class="blast_subhead">News</h3>

<?php
while (have_posts()) : the_post(); 

//print_r($post);

if($post->comment_count)
	$c = sprintf(" (%d comments)",$post->comment_count);
else
	$c = "";
?>
<h4 style="color:#0066FF;"><a style="color: #0033FF;" href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a> <?php echo $c;?></h4>
<?php endwhile;
endif;
$content = ob_get_clean();

if(!$events_ok && !$content_ok)
	die('no new content');

if($events_ok)
{
ob_start();
echo '<h3 class="blast_subhead">Events</h3>';

$start_stamp = strtotime($chimp_options["cron-day"]);
$atts["start"] = date('Y-m-d',$start_stamp);
$atts["querystring"] = "posts_per_page=25";
//$atts["customnav"] = "\n".'<p><a href="http://www.pack179.com/calendar/">More events at pack179.com/calendar/</a></p>'."\n";
//echo str_replace('http://www.pack179.com/page/2/','http://www.pack179.com/calendar/page/2/',rsvpmaker_upcoming($atts) );
echo rsvpmaker_upcoming($atts);
$content .= ob_get_clean();
}

$t_array = get_option('chimp_template');
$template = $t_array[$chimp_options["cron-template"]]["html"];
//get rid of comments
$template = preg_replace('!/\*.*?\*/!s', '', $template);
$template = preg_replace('/\n\s*\n/', "\n", $template);
$template = do_shortcode($template);
$message = str_replace('{content}',$content,$template);
$text = trim(strip_tags($content)).'

==============================================
*|LIST:DESCRIPTION|*

Forward to a friend:
*|FORWARD|*

*|FORWARD|*        
Unsubscribe *|EMAIL|* from this list:
*|UNSUB|*

Update your profile:
*|UPDATE_PROFILE|*

Our mailing address is:
*|LIST:ADDRESS|*
Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.';

echo str_replace('{content}',$content,$template);

require_once WP_PLUGIN_DIR.'/chimpblast/MCAPI.class.php';
$api = new MCAPI_chimpblast($chimp_options["chimp-key"]);

$type = 'regular';

$opts['list_id'] = $listId = $chimp_options["cron-list"];
$opts['subject'] = $chimp_options["cron-subject"];
if($preview)
	$opts['subject'] .= ' (Preview)';

$opts['from_email'] = $chimp_options["email-from"]; 
$opts['from_name'] = $chimp_options["email-name"];

$opts['tracking']=array('opens' => true, 'html_clicks' => true, 'text_clicks' => false);

$opts['authenticate'] = true;
//$opts['analytics'] = array('google'=>'my_google_analytics_key');
$opts['title'] = substr($chimp_options["cron-subject"],0,50).' '.date('r');
$opts['inline_css'] = true;
//$opts['generate_text'] = true;

$chimpcontent = array('html'=> $message, 
		  'text' => $text
		);

$retval = $api->campaignCreate($type, $opts, $chimpcontent);

?>
<div style="background-color: #FFFFFF; color: #000000;">
<?php

if ($api->errorCode){
	echo "Unable to Create New Campaign!";
	echo "\n\tCode=".$api->errorCode;
	echo "\n\tMsg=".$api->errorMessage."\n";
	exit();
} else {
	echo "New Campaign ID:".$retval."\n";
	$campaignId = $retval;
}

$emails[] = $chimp_options["email-from"];

if($preview)
{
$retval = $api->campaignSendTest($campaignId, $emails);

if ($api->errorCode){
	echo "Unable to Send Test Campaign!";
	echo "\n\tCode=".$api->errorCode;
	echo "\n\tMsg=".$api->errorMessage."\n";
} else {
	echo "Campaign Tests Sent!\n";
}
}// end send preview
else
{

$retval = $api->campaignSendNow($campaignId);

if ($api->errorCode){
	echo "Unable to Send Campaign!";
	echo "\n\tCode=".$api->errorCode;
	echo "\n\tMsg=".$api->errorMessage."\n";
} else {
	echo "Campaign Sent!\n";
}


}// end send live
?>