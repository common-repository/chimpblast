<?php

/*
Plugin Name: ChimpBlast
Plugin URI: http://www.rsvpmaker.com/chimpblast-plugin-for-mailchimp/
Description: Email broadcast utility for MailChimp. Compose and preview messages in WordPress, then submit to MailChimp for distribution. Import event listings from RSVPMaker, and RSVP Now link will be coded with the recipient's email address so the event service can recognize return visitors.
Author: David F. Carr
Version: 1.3.4
Author URI: http://www.rsvpmaker.com/
*/

if(file_exists(WP_PLUGIN_DIR."/chimpblast-config.php") )
	include WP_PLUGIN_DIR."/chimpblast-config.php";
else
	include WP_PLUGIN_DIR."/chimpblast/chimpblast-config.php";

include WP_PLUGIN_DIR."/chimpblast/chimp_email_options.php";

//make sure new rules will be generated for custom post type
add_action('admin_init', 'flush_rewrite_rules');

function ChimpAdd($email, $merge_vars) {

	if(ChimpBlastCheckMember($data["email"]) == '200')
		return 'already on list';

	$chimp_options = $Chimp_Email_Options->get_options();
	$apikey = $chimp_options["chimp-key"];
	$listId = $chimp_options["chimp-list"]; 

    $memberId = md5(strtolower($email));
    $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
    $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId . '/members/' . $memberId;

    $json = json_encode([
        'email_address' => $data['email'],
        'status'        => 'subscribed', // "subscribed","unsubscribed","cleaned","pending"
        'merge_fields'  => [
            'FNAME'     => $merge_vars['FNAME'],
            'LNAME'     => $merge_vars['LNAME']
        ]
    ]);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);                                                                                                                 
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
	if($chimp_options["add_notify"])
	{
		if ($httpCode != '200'){
			//if this fails because the person is already subscribed, ignore. Otheriwse, send error message 
			mail($chimp_options["add_notify"],"ChimpAdd error $email ",$api->errorMessage);
		}
		 else {
			mail($chimp_options["add_notify"],"ChimpAdd invite sent to $email ",$retval);
		}
	}
    return $httpCode;
}

//attempt to add people who register with website, if specified on user form
if($chimp_options["chimp_add_new_users"])
	add_action('user_register','register_chimpmail');

function register_chimpmail($user_id) {
$new_user = get_userdata($user_id);
$email = $new_user->user_email;
$merge_vars["FNAME"] = $new_user->first_name;
$merge_vars["LNAME"] = $new_user->last_name;
ChimpAdd($email, $merge_vars);
}

function draw_blastoptions() {

global $post;
global $Chimp_Email_Options;
$chimp_options = $Chimp_Email_Options->get_options();

if($_GET["post"])
{
$custom_fields = get_post_custom($post->ID);
$permalink = get_permalink($post->ID);
; ?>
<p><strong>To Send Your Message</strong></p>
<p>If you are ready to send your message, click <a href="<?php echo $permalink; ?>">View Post</a> now to view the message in the email template. You will also be sent a test or preview email that you can review before approving the email broadcast.</p>
<p>If you make more changes here, be sure you save them by clicking Update first before sending your message.</p>
<?php
}
elseif($_GET["page"] == 'chimp_get_content')
	;
else
{
; ?>
<p><strong>To Send Your Message</strong></p>
<p>If you are ready to send your message, click Publish, then follow the link to View Post to view the message in the email template. You will also be sent a test or preview email that you can review before approving the email broadcast.</p>
<?php
}

$template = get_option('chimp_template');
if(!is_array($template) )
	echo '<p style="color: red;">Error: please <a href="edit.php?post_type=chimpblast&page=chimp_template">update template</a></p>';
; ?>
<table>
<tr><td>From Name:</td><td><input type="text"  size="80" name="email[from_name]" value="<?php echo ($custom_fields["_email_from_name"][0]) ? $custom_fields["_email_from_name"][0] : $chimp_options["email-name"]; ?>" /></td></tr>
<tr><td>From Email:</td><td><input type="text" size="80"  name="email[from_email]" value="<?php echo ($custom_fields["_email_from_email"][0]) ? $custom_fields["_email_from_email"][0] : $chimp_options["email-from"]; ?>" /></td></tr>
<tr><td>Preview To:</td><td><input type="text" size="80" name="email[preview_to]" value="<?php echo ($custom_fields["_email_preview_to"][0]) ? $custom_fields["_email_preview_to"][0] : $chimp_options["email-from"]; ?>" /></td></tr>
</table>
<p><input type="checkbox" name="email[headline]" id="email[headline]" value="1" <?php echo ($custom_fields["_email_headline"][0]) ? ' checked="checked" ' : ''; ?> /> Show post title as headline in email (in addition to subject line)</p>
<p>Template <select name="email[template]">
<?php
foreach($template as $index => $value)
	{
	$c = ( $index == $custom_fields["_email_template"][0]) ? ' selected="selected" ' : '';
	echo sprintf('<option value="%d" %s>%s</option>',$index,$c,$value["slug"]);
	}
; ?>
</select></p>

<p>List <select name="email[list]">
<?php
if(!$custom_fields["_email_list"][0])
	$custom_fields["_email_list"][0] = $chimp_options["chimp-list"];

require_once WP_PLUGIN_DIR.'/chimpblast/mailchimp-api3.php';
echo ChimpBlastListOptions($custom_fields["_email_list"][0]);

?>
</select></p>

<p>You can import the content of posts and RSVPMaker events on the <a href="<?php echo admin_url('edit.php?post_type=chimpblast&page=chimp_get_content') ?>">Content for Eblast</a> screen.</p>

<?php

}

function my_chimpblasts_menu() {

add_meta_box( 'BlastBox', 'ChimpBlast Options', 'draw_blastoptions', 'chimpblast', 'normal', 'high' );

}

function save_chimpblast_data($postID) {

if($_POST["email"]["from_name"])
	{
	global $wpdb;
	global $current_user;
	
	//for debugging
	//$message = var_export($args,true).var_export($_POST,true).var_export($current_user,true); mail("david@carrcommunications.com","save chimpblast $postID",$message);
	if($parent_id = wp_is_post_revision($postID))
		{
		$postID = $parent_id;
		}
		
		$ev = $_POST["email"];
		if(!$ev["headline"])
			$ev["headline"] = 0;
		foreach($ev as $name => $value)
			{
			$field = '_email_'.$name;
			$single = true;
			$current = get_post_meta($postID, $field, $single);
			 
			if($value && ($current == "") )
				add_post_meta($postID, $field, $value, true);
			
			elseif($value != $current)
				update_post_meta($postID, $field, $value);
			
			elseif($value == "")
				delete_post_meta($postID, $field, $current);
			}
	
	$message = var_export($args,true).var_export($_POST,true);
	}
}

add_action('admin_menu', 'my_chimpblasts_menu');

add_action('save_post','save_chimpblast_data');

add_filter('the_editor_content','default_chimpblast_content');

add_action( 'init', 'create_chimpblast_post_type' );

//    'menu_icon' => WP_PLUGIN_URL.'/chimpblast/email.gif',

function create_chimpblast_post_type() {
  register_post_type( 'chimpblast',
    array(
      'labels' => array(
        'name' => __( 'ChimpBlasts' ),
        'add_new_item' => __( 'Add New ChimpBlast' ),
        'edit_item' => __( 'Edit ChimpBlast' ),
        'new_item' => __( 'ChimpBlasts' ),
        'singular_name' => __( 'ChimpBlast' )
      ),
	'public' => true,
	'exclude_from_search' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'query_var' => true,
    'rewrite' => true,
    'capability_type' => 'page',
    'hierarchical' => false,
    'menu_position' => 5,
	'menu_icon' => WP_PLUGIN_URL.'/chimpblast/email.gif',
    'supports' => array('title','editor')
    )
  );
}

add_action("template_redirect", 'chimpblast_template_redirect');

// Template selection
function chimpblast_template_redirect()
{

global $wp;
global $wp_query;

	if ($wp->query_vars["post_type"] == "chimpblast")
	{
		if (have_posts())
		{
			include(WP_PLUGIN_DIR . '/chimpblast/chimpblast-template.php');
			die();
		}
		else
		{
			$wp_query->is_404 = true;
		}
	}
}

function chimpblast_ui($content, $text)
{
ob_start();

//must be editor or administrator to send broadcasts
if(!current_user_can('edit_pages') )
	return;

global $post;

global $custom_fields;
if(!$custom_fields)
	$custom_fields = get_post_custom($post->ID);
$opts['from_email'] = $custom_fields["_email_from_email"][0]; 
$opts['from_name'] = $custom_fields["_email_from_name"][0];
$previewto = $custom_fields["_email_preview_to"][0];

$opts['subject'] = $post->post_title;

$postID = $post->ID;
$permalink = get_permalink($postID);
$edit_link = get_edit_post_link($postID);

$chimp_options = get_option('chimp');

require_once WP_PLUGIN_DIR.'/chimpblast/mailchimp-api3.php';

if($campaignId = $_POST["campaignid"])
{

if($_POST["now"])
{
	$result = ChimpBlastSendNow($campaignId);
	echo "<pre>";
	print_r($result);
	echo "<pre>";	
/*
add error test
	if ($api->errorCode){
		echo "Unable to Send Campaign!";
		echo "\n\tCode=".$api->errorCode;
		echo "\n\tMsg=".$api->errorMessage."\n";
	} else {
		echo "Campaign Sent!\n";
	}
*/
}
else {
// preview mode

preg_match_all ("/\b[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]+\.[A-z0-9]{2,6}\b/", $_POST["previewto"], $emails);
$emails = $emails[0];

	$result = ChimpBlastSendTest($campaignId,$emails);
	echo "<pre>";
	print_r($result);
	echo "<pre>";	

/*
$retval = $api->campaignSendTest($campaignId, $emails);

if ($api->errorCode){
	echo "Unable to Send Test Campaign!";
	echo "\n\tCode=".$api->errorCode;
	echo "\n\tMsg=".$api->errorMessage."\n";
} else {
	echo "Campaign Tests Sent!\n";
}
*/
} // end preview mode

}
else
{ // get draft email
$type = 'regular';

$opts['list_id'] = $listId = $custom_fields["_email_list"][0];

$opts['tracking']=array('opens' => true, 'html_clicks' => true, 'text_clicks' => false);

$opts['authenticate'] = true;
//$opts['analytics'] = array('google'=>'my_google_analytics_key');
$opts['title'] = substr($post->post_title,0,50).' '.date('r');
$opts['inline_css'] = true;
//$opts['generate_text'] = true;

$chimpcontent = array('html'=> $content, 
		  'plain_text' => $text
		);

	$campaignId = ChimpBlastCampaignCreate($type, $opts, $chimpcontent);
//	echo "<pre>";
//	print_r($result);
//	echo "<pre>";	

/*
if ($api->errorCode){
	echo "Unable to Create New Campaign!";
	echo "\n\tCode=".$api->errorCode;
	echo "\n\tMsg=".$api->errorMessage."\n";
	exit();
} else {
	echo "New Campaign ID:".$retval."\n";
	$campaignId = $retval;
}
*/

} // end create campaign

if(!$_POST)
{
if(empty($campaignId))
	echo "<p>Campaign not initialized</p>";
else
	ChimpBlastGetCampaignDetails($campaignId) 

?>



<form method="post" action="<?php echo $permalink; ?>">
<input type="hidden" name="campaignid" value="<?php echo $campaignId; ?>" />
<input type="submit" name="now" value="Send Now" />
</form>
<p>Or <a href="<?php echo $edit_link; ?>">Revise</a></p>
<?php
}
else
	printf('<p>Return to the <a href="%s">WordPress dashboard</a>.</p>',admin_url());

return '<div style="background-color: #FFFFFF; color: #000000;">'.ob_get_clean().'</div>';

}

function extract_email() {

global $wpdb;

if($_POST)
	{

global $chimp_options;
require_once('mailchimp-api3.php');	
	preg_match_all ("/\b[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]+\.[A-z0-9]{2,6}\b/", $_POST["emails"], $emails);
	$emails = $emails[0];

	foreach($emails as $email)
		{
		if($_POST["in_mailchimp"])
			{
			
			if(ChimpBlastCheckMember($data["email"]) == '200')
				{
				$inchimp .= "\n<br />$email";
				continue;
				}
			}
		echo "\n<br />$email";

		}
if($inchimp)
	echo "<h3>In MailChimp</h3>$inchimp";

	}

; ?>
<div id="icon-options-general" class="icon32"><br /></div>
<h2>Extract Emails</h2>
<p>You can enter an disorganized list of emails mixed in with other text, and this utility will extract just the email addresses.</p>
<form id="form1" name="form1" method="post" action="">

  <p>
    <textarea name="emails" id="emails" cols="45" rows="5"></textarea>
  </p>
  <p>Filter out emails that:</p>
  <p>
    <input name="in_mailchimp" type="checkbox" id="in_mailchimp" checked="checked" />
  Registered in MailChimp</p>
  <p>
    <input type="submit" name="button" id="button" value="Submit" />
  </p>
</form>
<?php
}

add_action( 'admin_init', 'register_chimpblast_settings' );

function register_chimpblast_settings() {
	//register our settings
	register_setting( 'chimpblast-settings-group', 'chimp_template' );
}

function chimpblast_template () {
; ?>
<div id="icon-options-general" class="icon32"><br /></div>
<h2>ChimpBlast Template</h2>
<form id="form1" name="form1" method="post" action="options.php">
<?php settings_fields( 'chimpblast-settings-group' ); 

global $rsvp_options;
if($rsvp_options)
	{
	if($rsvp_options["custom_css"])
		$rsvpstyle = $rsvp_options["custom_css"];
	else
		$rsvpstyle = plugins_url('/rsvpmaker/style.css');
	$style = '/* WordPress formatting of photos and captions */

.alignleft {
	float: left;
	margin: 5px 20px 20px 0;
}

.alignright {
	float: right;
	margin: 5px 0 20px 20px;
}

.aligncenter {
	display: block;
	margin: 0 auto 20px auto;
}

.alignnone {
	margin: 0;
}

.wp-caption.alignleft {
	float: left;
	margin: 5px 20px 20px 0px;
}

.wp-caption.alignright {
	float: right;
	margin: 5px 0 20px 20px;
}

.wp-caption.aligncenter {
	display: block;
	margin: 0 auto 20px auto;
}

.wp-caption.alignnone {
	margin: 20px 0;
}

.post .wp-caption {
	border: 1px solid #cccccc;
	background: #ebebeb;
	text-align: center;
	padding: 10px 5px 0 5px;
}

.post .wp-caption-text {
	margin: 0;
	font-size: 12px;
}
/* RSVPMaker formatting */

'.file_get_contents($rsvpstyle);
	}

$template = get_option('chimp_template');

if($template && !is_array($template) )
	$template = NULL;

if(!$template)
	{
	echo '<h3 style="color: red;">Not Saved (showing suggested template)</h3>';
	$template[0]['slug'] = 'default';
	$template[0]['html'] = '<html>
<head>
	<title>*|MC:SUBJECT|*</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	
<style type="text/css">
	/*
	@tab Page
	@section background color
	@tip Choose a color for your HTML email background. You might choose one to match your company branding.
	@theme page
	*/
		body,.backgroundTable{
			/*@editable*/background-color:#FFFFCC;
		}
	/*
	@tab Page
	@section border
	@tip Add a border to help your template content stand out from your email background.
	*/
		#contentTable{
			/*@editable*/border:0px none #000000;
			/*@editable*/margin-top:10px;
		}
	/*
	@tab Header
	@section top bar
	@tip You can make this color stand out, or you might make it the same as your email background color.
	@theme header
	*/
		.headerTop{
			/*@editable*/background-color:#ffffff;
			/*@editable*/border-top:0px none #000000;
			/*@editable*/border-bottom:0px none #FFFFFF;
			/*@editable*/text-align:center;
			/*@editable*/padding:0px;
		}
	/*
	@tab Header
	@section top bar text
	@tip Customize the way the "View in a browser" text looks in the top bar.
	@theme header
	*/
		.adminText{
			/*@editable*/font-size:10px;
			/*@editable*/color:#333333;
			/*@editable*/line-height:200%;
			/*@editable*/font-family:Verdana;
			/*@editable*/text-decoration:none;
		}
	/*
	@tab Header
	@section header bar
	@tip Choose a set of colors that look good with the colors of your logo image or text header.
	*/
		.headerBar{
			/*@editable*/background-color:#ffffff;
			/*@editable*/border-top:0px none #333333;
			/*@editable*/border-bottom:0px none #FFFFFF;
			/*@editable*/padding:10px;
		}
	/*
	@tab Header
	@section header bar text
	@tip Customize the way your text header looks if you do not use a graphic header.
	*/
		.headerBarText{
			/*@editable*/color:#333333;
			/*@editable*/font-size:30px;
			/*@editable*/font-family:Arial;
			/*@editable*/font-weight:normal;
			/*@editable*/text-align:left;
		}
	/*
	@tab Body
	@section title style
	@tip Titles and headlines in your message body. Make them big and easy to read.
	@theme title
	*/
		.title{
			/*@editable*/font-size:24;
			/*@editable*/font-weight:bold;
			/*@editable*/color:#0000FF;
			/*@editable*/font-family:Times New Roman;
			/*@editable*/line-height:150%;
		}
	/*
	@tab Body
	@section subtitle style
	@tip This is the byline text that appears immediately underneath your titles/headlines.
	@theme subtitle
	*/
		.subTitle{
			/*@editable*/font-size:18;
			/*@editable*/font-weight:bold;
			/*@editable*/color:#0000FF;
			/*@editable*/font-style:normal;
			/*@editable*/font-family:Times New Roman;
		}
	/*
	@tab Body
	@section default text
	@tip This is the default text style for the body of your email.
	@theme main
	*/
		.defaultText{
			/*@editable*/font-size:12px;
			/*@editable*/color:#333333;
			/*@editable*/line-height:150%;
			/*@editable*/font-family:Verdana;
			/*@editable*/background-color:#FFFFFF;
			/*@editable*/padding:20px;
			/*@editable*/border:0px none #FFFFFF;
		}
	/*
	@tab Footer
	@section footer
	@tip You might give your footer a light background color and separate it with a top border
	@theme footer
	*/
		.footerRow{
			/*@editable*/background-color:#cccccc;
			/*@editable*/border-top:0px none #FFFFFF;
			/*@editable*/padding:20px;
		}
	/*
	@tab Footer
	@section footer style
	@tip You can make this font smaller than your body text, but pick a color that is easy to read.
	@theme footer
	*/
		.footerText{
			/*@editable*/font-size:10px;
			/*@editable*/color:#333333;
			/*@editable*/line-height:100%;
			/*@editable*/font-family:Verdana;
		}
	/*
	@tab Page
	@section link style
	@tip Specify a color for all your hyperlinks.
	@theme link
	*/
		a,a:link,a:visited{
			/*@editable*/color:#800000;
			/*@editable*/text-decoration:underline;
			/*@editable*/font-weight:normal;
		}
	/*
	@tab Header
	@section link style
	@tip Specify a color for your header hyperlinks.
	@theme link_header
	*/
		.headerTop a{
			/*@editable*/color:#333333;
			/*@editable*/text-decoration:none;
			/*@editable*/font-weight:normal;
		}
	/*
	@tab Footer
	@section link style
	@tip Specify a color for your footer hyperlinks.
	@theme link_footer
	*/
		.footerRow a{
			/*@editable*/color:#800000;
			/*@editable*/text-decoration:underline;
			/*@editable*/font-weight:normal;
		}
		.headerBarText a{
			color:#000000;
			text-decoration:none;
		}
		body,.backgroundTable{
			background-color:#5AA4E1;
		}
		a,a:link,a:visited{
			color:#006600;
		}
'
.$style.
'
</style></head>
<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">

<table width="100%" cellspacing="0" class="backgroundTable">
<tr>
<td valign="top" align="center">

<table id="contentTable" cellspacing="0" cellpadding="0" width="600"><tr><td>
<table width="600" cellpadding="0" cellspacing="0">
<tr>
<td class="headerTop" align="right"><div class="adminText" mc:edit="header">
    Email not displaying correctly?
    <a href="*|ARCHIVE|*" class="adminText">View it in your browser.</a>
</div></td>
</tr>
 
<tr>
<td class="headerBar"><div class="headerBarText"><a href="'.home_url().'">'.get_bloginfo('name').'</a></div></td>
</tr>

</table>

<table width="600" cellpadding="20" cellspacing="0" class="bodyTable">
<tr>
<td valign="top" align="left" class="defaultText" mc:edit="main">
{content}
</td>
</tr>

<tr>
<td class="footerRow" align="left" valign="top">
<div class="footerText" mc:edit="footer">

    *|LIST:DESCRIPTION|*<br>
    <br>
    <a href="*|UNSUB|*">Unsubscribe</a> *|EMAIL|* from this list | <a href="*|FORWARD|*">Forward to a friend</a> | <a href="*|UPDATE_PROFILE|*">Update your profile</a>
    <br>
    <strong>Our mailing address is:</strong><br>
    *|LIST:ADDRESS|*<br>
    <em>Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.</em><br>    
</div>
<div id="monkeyRewards" style="margin-top: 10px; text-align: right;">
*|REWARDS|*
</div>
</td>
</tr>

</table>

</td></tr></table>

</td>
</tr>
</table>
<span style="padding: 0px;"></span>
</body>
</html>';
	}
; ?>

  <p>Paste in template code and click Save.</p>
  <p>Include the {content} placeholder (including the curly brackets) wherever your message should appear. See below for more tips on recommended CSS to include.</p>
<?php
foreach($template as $index => $value)
{
; ?>
<div id="temp<?php echo $index; ?>">
  <p>
    <input type="text" name="chimp_template[<?php echo $index; ?>][slug]" id="chimp_template[<?php echo $index; ?>][slug]" value="<?php echo $template[$index]["slug"]; ?>" /> <a href="#" onclick="remove_template(<?php echo $index; ?>); return false;">Remove</a>
  </p>
  <p>
    <textarea name="chimp_template[<?php echo $index; ?>][html]" id="chimp_template[<?php echo $index; ?>][html]" cols="80" rows="10"><? echo $template[$index]["html"]; ?></textarea>
  </p>
</div>
<?php
}
$index++;
; ?>
<div id="add_template"><button id="addtemp"> (+) Add another template</button></div>
<p>
<button>Save</button>
</p>

<script>
function remove_template(id) {
var t = document.getElementById('temp'+id);
var f = document.getElementById('form1');
f.removeChild(t);
}

jQuery(document).ready(function($){
$('#addtemp').click( function(event) {
	event.preventDefault();
	$('#add_template').html('<p><input type="text" name="chimp_template[<?php echo $index; ?>][slug]" id="chimp_template[<?php echo $index; ?>][slug]" value="<?php echo "template".($index+1); ?>" /></p><p><textarea name="chimp_template[<?php echo $index; ?>][html]" id="chimp_template[<?php echo $index; ?>][html]" cols="80" rows="10"></textarea></p>');
	} );

});

</script>

</form>

<p><strong>Tip:</strong> These CSS styles are recommended for use with ChimpBlast and RSVPMaker.</p>

<pre>

<?php echo $style; ?>

</pre>

<?php
} // end chimpblast template form

function my_chimpblast_menu() {
/*
$page_title = "";
$menu_title = "";
$capability = "edit_posts";
$menu_slug = "";
$function = "";
$icon_url = "";
$position = "";

add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
*/

$parent_slug = "edit.php?post_type=chimpblast";
$page_title = "Extract Addresses";
$menu_title = $page_title;
$capability = "edit_posts";
$menu_slug = "extract";
$function = "extract_email";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

$parent_slug = "edit.php?post_type=chimpblast";
$page_title = "Chimp Template";
$menu_title = $page_title;
$capability = "edit_posts";
$menu_slug = "chimp_template";
$function = "chimpblast_template";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

$parent_slug = "edit.php?post_type=chimpblast";
$page_title = "Content for Eblast";
$menu_title = $page_title;
$capability = "edit_posts";
$menu_slug = "chimp_get_content";
$function = "chimp_get_content";

add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

}

add_action('admin_menu', 'my_chimpblast_menu');

function chimpshort($atts, $content = NULL ) {

$atts = shortcode_atts( array(
  'query' => 'post_type=post&posts_per_page=5',
  'format' => '',
  ), $atts );

	ob_start();
	query_posts($atts["query"]);

if ( have_posts() ) {
while ( have_posts() ) : the_post(); ?>
<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<h3 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
<?php
if($atts["format"] == 'excerpt')
	{
; ?>
<div class="excerpt-content">

<?php the_excerpt(); ?>

</div><!-- .excerpt-content -->
<?php	
	}
elseif($atts["format"] == 'full')
	{
; ?>
<div class="entry-content">

<?php the_content(); ?>

</div><!-- .entry-content -->
<?php
}
; ?>
</div>
<?php 
endwhile;
wp_reset_query();
} 
	
	$content = ob_get_clean();

	return $content;
}
add_shortcode('chimpshort', 'chimpshort');

function chimp_get_content () {

; ?>
<div id="icon-options-general" class="icon32"><br /></div>
<h2>Content for EBlast</h2>

<p>Use this form to create a draft eblast based on blog content.</p>

<?php
global $wpdb;
global $rsvp_options;

if($_POST["get_for_eblast"])
{

ob_start();

foreach($_POST["get_for_eblast"] as $index => $getit)
	{
	if($getit == '')
		continue;
	$parts = explode(":",$getit);
	if($parts[0] == 'upcoming')
		{
		$atts = array();
		echo rsvpmaker_upcoming($atts);
		$subject = "Upcoming Events";
		$qstring = NULL;
		}
	elseif($parts[0] == 'rsvpmaker')
		{
		$qstring = 'post_type=rsvpmaker&post_status=publish&p='.$parts[1];
		}
	elseif($parts[0] == 'post')
		{
		$qstring = 'post_status=publish&p='.$parts[1];
		}
	elseif($parts[0] == 'recent')
		{
		$qstring = 'post_status=publish&posts_per_page='.$parts[1];
		}
	elseif($parts[0] == 'qstring')
		{
		$qstring = $parts[1];
		}

	if($qstring)
	{
$cq = new WP_Query($qstring);
//print_r($cq);
while ( $cq->have_posts() ) : $cq->the_post();
global $post;
if(!$subject)
	$subject = get_the_title();
; ?>
<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<h1 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
<div class="entry-content">

<?php 
if($_POST["get_format"][$index] == 'content')
	the_content();
elseif($_POST["get_format"][$index] == 'excerpt')
	the_excerpt();
else
	echo wpautop(wptexturize(convert_chars($post->post_content)));
 ; ?>

</div><!-- .entry-content -->
</div>
<?php 
endwhile;
	}

	}

$content = ob_get_clean();

$content = "<p>WRITE AN INTRODUCTION HERE</p>\n".$content;

$my_post = array(
     'post_title' => $subject,
     'post_content' => $content,
     'post_status' => 'draft',
     'post_author' => get_current_user_id(),
     'post_type' => 'chimpblast'
  );
$id = wp_insert_post($my_post);

$editurl = admin_url("post.php?post=$id&action=edit");

echo "<p>Draft eblast created: <a href=\"$editurl\">Edit Now</a></p>";
echo "<h3>Email Subject: $subject</h3>";
echo $content;

} //end of $_POST processing

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
	$events .= "<option value=\"upcoming\">Upcoming Events List</option>\n";

foreach ($results as $row)
	{
	$t = strtotime($row["datetime"]);
	$date = date('F jS, Y',$t);
	$events .= sprintf("<option value=\"rsvpmaker:%d\">%s %s</option>\n",$row["postID"],substr($row["post_title"],0,60), $date);
	}

$events = '<optgroup label="Upcoming Events">'.$events."</optgroup>\n";

} // if events
} // if rsvpmaker is active

$sql = "SELECT ID, post_title FROM $wpdb->posts WHERE post_status='publish' AND post_type='post' ORDER BY post_date DESC LIMIT 0, 50";
$wpdb->show_errors();
$results = $wpdb->get_results($sql, ARRAY_A);
if($results)
{

$posts .= "<option value=\"recent:10\">10 Most Recent Blog Posts</option>\n";
$posts .= "<option value=\"recent:5\">5 Most Recent Blog Posts</option>\n";
$posts .= "<option value=\"recent:4\">4 Most Recent Blog Posts</option>\n";
$posts .= "<option value=\"recent:3\">3 Most Recent Blog Posts</option>\n";
$posts .= "<option value=\"recent:2\">2 Most Recent Blog Posts</option>\n";

foreach ($results as $row)
	{
	$posts .= sprintf("<option value=\"post:%d\">%s</option>\n",$row["ID"],substr($row["post_title"],0,80));
	}

$posts = '<optgroup label="Recent Posts">'.$posts."</optgroup>\n";
}

$options = '<option value="">Select Posts or Listing</option>'."\n".$events . $posts;

$categories = get_categories();
foreach($categories as $c)
	{
	if(!$slug)
		$slug = $c->slug;
	if($slugs)
		$slugs .= ", ";
	$slugs .= $c->slug;
	}

; ?>

<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
<p><select name="get_for_eblast[0]"><?php echo $options; ?></select><select name="get_format[0]">
<option value="content">Content -> More</option>
<option value="excerpt">Excerpt</option>
<option value="full">Full</option>
</select>
</p>
<p><select name="get_for_eblast[1]"><?php echo $options; ?></select><select name="get_format[1]">
<option value="content">Content -> More</option>
<option value="excerpt">Excerpt</option>
<option value="full">Full</option>
</select>
</p>
<p><select name="get_for_eblast[2]"><?php echo $options; ?></select><select name="get_format[2]">
<option value="content">Content -> More</option>
<option value="excerpt">Excerpt</option>
<option value="full">Full</option>
</select>
</p>
<p><select name="get_for_eblast[3]"><?php echo $options; ?></select><select name="get_format[3]">
<option value="content">Content -> More</option>
<option value="excerpt">Excerpt</option>
<option value="full">Full</option>
</select>
</p>
<p id="last_select"><select name="get_for_eblast[4]"><?php echo $options; ?></select><select name="get_format[4]">
<option value="content">Content -> More</option>
<option value="excerpt">Excerpt</option>
<option value="full">Full</option>
</select>
</p>

<button>Load Content</button>
</form>

<p><a href="#last_select" id="custom_query" name="custom_query">(+) <?php echo __('Add custom query','chimpblast'); ?></a></p>

<script>
jQuery(document).ready(function($) {

$('#custom_query').click(function(){
	$('#last_select').append('<p><input type="text" name="get_for_eblast[]" value="qstring:post_status=publish&posts_per_page=5" size="80" /><select name="get_format[]"><option value="content">Content -> More</option><option value="excerpt">Excerpt</option><option value="full">Full</option></select></p>');
	});
});
</script>
<p>You can add up to 5 posts, or post listings, including event listings from the RSVPMaker plugin.</p>
<p>The format parameters are:</p>
<ul>
<li><strong>Content -> More</strong> List as if on a blog page, with a link to (more...) if the &lt;!--more--&gt; tag was used in the body of the post.</li>
<li><strong>Excerpt</strong> Show only short excerpt with a link to the rest of the post.</li>
<li><strong>Full</strong> Show the full text, ignore any &lt;!--more--&gt; tag.</li>
</ul>
<p>These parameters are not applied to the Upcoming Events List option.</p>
<p>You can also enter a custom query string such as <code>post_status=publish&posts_per_page=5&category_name=<?php echo $slug; ?></code>, which would retrieve the 5 most recent posts from the <?php echo $slug; ?> category (available category slugs on this site include: <?php echo $slugs; ?>). More query string options are <a href="http://codex.wordpress.org/Class_Reference/WP_Query">documented on WordPress.org</a>.</p>
  <?php


} // end chimp get content

add_action('rsvpmaker_email_list_ok','ChimpBlastAddToList',10,1);

?>