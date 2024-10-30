<?php
  // Avoid name collisions.
  if (!class_exists('Chimp_Email_Options'))
      : class Chimp_Email_Options
      {
          // this variable will hold url to the plugin  
          var $plugin_url;
          
          // name for our options in the DB
          var $db_option = 'chimp';
          
          // Initialize the plugin
          function Chimp_Email_Options()
          {
              $this->plugin_url = trailingslashit( WP_PLUGIN_URL.'/'. dirname( plugin_basename(__FILE__) ) );

              // add options Page
              add_action('admin_menu', array(&$this, 'admin_menu'));
              
          }
          
          // hook the options page
          function admin_menu()
          {
              add_options_page('ChimpBlast', 'ChimpBlast', 4, basename(__FILE__), array(&$this, 'handle_options'));
          }
          
          // handle plugin options
          function get_options()
          {
              
			  // default values
              $options = array(
			  'email-from' => 'webmaster@' . str_replace("www.","",$_SERVER['SERVER_NAME'])
			  ,'email-name' => get_bloginfo('name')
			  ,'reply-to' => 'webmaster@' . str_replace("www.","",$_SERVER['SERVER_NAME'])
			  ,'chimp-key' => ''
			  ,'chimp-list' => ''
			  ,'cron-day' => ''
			  ,'cron-template' => ''
			  ,'cron-list' => ''
			  ,'cron-subject' => ''
			  ,"chimp_add_new_users" => 0
			  ,"add_notify" => get_bloginfo('admin_email')
			  );
              
              // get saved options
              $saved = get_option($this->db_option);
              
              // assign them
              if (!empty($saved)) {
                  foreach ($saved as $key => $option)
                      $options[$key] = $option;
              }
              
              // update the options if necessary
              if ($saved != $options)
                  update_option($this->db_option, $options);
              
              //return the options  
              return $options;
          }
          
          // Set up everything
          function install()
          {
              // set default options
              $this->get_options();
          }
          
          // handle the options page
          function handle_options()
          {
              $options = $this->get_options();
              
              if ($_POST) {
              		
              		//check security
              		check_admin_referer('email-nonce');
              		
                  //$options = array();
                  foreach ($options as $name => $value)
				  	{
					$options[$name] = $_POST[$name];
				  	}
                  update_option($this->db_option, $options);
                  
                  echo '<div class="updated fade"><p>Plugin settings saved.</p></div>';
              }
              
              // URL for form submit, equals our current page
              $action_url = $_SERVER['REQUEST_URI'];
; ?>
<div class="wrap" style="max-width:950px !important;">
	<h2>MailChimp Options</h2>
				
	<div id="poststuff" style="margin-top:10px;">

	 <div id="mainblock" style="width:710px">
	 
		<div class="dbx-content">
		 	<form name="Email Options" action="<?php echo $action_url ; ?>" method="post">
					<input type="hidden" name="submitted" value="1" /> 
					
					<?php wp_nonce_field('email-nonce'); ?>
					
                    <p>Eblast</p>
                    <p>Email From: 
                      <input type="text" name="email-from" id="email-from" value="<?php echo $options["email-from"]; ?>" />
                    </p>
                    <p>Email Name: 
                      <input type="text" name="email-name" id="email-name" value="<?php echo $options["email-name"]; ?>" />
                    </p>
                    <p>API-Key: 
                      <input type="text" name="chimp-key" id="chimp-key" value="<?php echo $options["chimp-key"]; ?>" />
                    </p>
                    <p>Default List: 
<?php
if(!empty($options["chimp-key"]))
{
require_once WP_PLUGIN_DIR.'/chimpblast/mailchimp-api3.php';
$lists = ChimpBlastListOptions($options["chimp-list"]);
}
?>
<select name="chimp-list" id="chimp-list" >
<option value="">Select List</option>
<?php echo $lists; ?>
</select>                    
                    </p>
                    <p>Attempt to Subscribe New WordPress user emails: 
                      <input type="checkbox" name="chimp_add_new_users" id="chimp_add_new_users" value="1" <?php echo ($options["chimp_add_new_users"]) ? ' checked="checked" ' : ''; ?> />
                    </p>
                    <p>Email to notify on API listSubscribe success/failure (optional): 
                      <input type="text" name="add_notify" id="add_notify" value="<?php echo $options["add_notify"]; ?>" />
                    </p>

<h3>Cron Newsletter Options</h3>
                    <p>Cron weekly newsletter goes out on <select name="cron-day"><?php 
					$days = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
					foreach($days as $day)
						{
						$c = ( $day == $options["cron-day"]) ? ' selected="selected" ' : '';
						echo sprintf('<option value="%s" %s>%s</option>',$day,$c,$day);
						}
					?></select> using the <select name="cron-template">
<?php
$t_array = get_option('chimp_template');
foreach($t_array as $index => $value)
	{
	$c = ( $index == $options["cron-template"]) ? ' selected="selected" ' : '';
	echo sprintf('<option value="%d" %s>%s</option>',$index,$c,$value["slug"]);
	}
 ?></select> template</p>
 <p>Cron Subject Line: <input type="text" name="cron-subject" value="<?php echo $options["cron-subject"];?>" />.</p>

<p>Cron List: 
  <input type="text" name="cron-list" id="cron-list" value="<?php echo $options["cron-list"]; ?>" />
</p>

              <div class="submit"><input type="submit" name="Submit" value="Update" /></div>
			</form>

<p>Cron command line: <code>php <?php echo WP_PLUGIN_DIR; ?>/chimpblast/cronblast.php</code></p>

<?php

$template = get_option('chimp_template');
if(!is_array($template) )
	echo '<p style="color: red;">You must <a href="edit.php?post_type=chimpblast&page=chimp_template">set up the email template</a> before first use</p>';

if($options["chimp-key"])
{
$apikey = $options["chimp-key"];
include WP_PLUGIN_DIR.'/chimpblast/MCAPI.class.php';
$api = new MCAPI_chimpblast($apikey);

$retval = $api->lists();

if ($api->errorCode){
	$listmsg .=  "Unable to load lists()!";
	$listmsg .=  "\n\tCode=".$api->errorCode;
	$listmsg .=  "\n\tMsg=".$api->errorMessage."\n";
} else {
	$listmsg .= "Your lists:<br />\n";
	foreach ($retval as $list){
		$listmsg .=  "<strong>Id = ".$list['id']." - ".$list['name'].'</strong><br />'; //." - ".$list['web_id']."\n";
		$listmsg .=  "\tSub = ".$list['member_count']."\tUnsub=".$list['unsubscribe_count']."\tCleaned=".$list['cleaned_count']."<br />\n";
	}
}

echo wpautop($listmsg);

}
; ?>

		</div>
				
	 </div>

	</div>

</p>
</div>
<?php              
          }
      }
  
  else
      : exit("Class already declared!");
  endif;
  
  // create new instance of the class
  $Chimp_Email_Options = new Chimp_Email_Options();
  //print_r($Chimp_Email_Options);
  if (isset($Chimp_Email_Options)) {
      // register the activation function by passing the reference to our instance
      register_activation_hook(__FILE__, array(&$Chimp_Email_Options, 'install'));
  }
; ?>