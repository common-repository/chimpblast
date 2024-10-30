=== ChimpBlast ===
Contributors: davidfcarr
Donate: http://www.rsvpmaker.com
Tags: email, email list, eblast, event, calendar, rsvp
Requires at least: 3.0
Tested up to: 3.1.3
Stable tag: 1.3.4

Email broadcast utility for MailChimp.

== Description ==

UPDATE: The latest release of [RSVPMaker](http://wordpress.org/extend/plugins/rsvpmaker/) includes all this functionality and more, and has been updated to support version 3.0 of the MailChimp API.

ChimpBlast lets you compose broadcast email messages for use with the MailChimp service from within WordPress. You can compose messages in the WordPress rich text editor, import the content of posts, preview the results on your website, and then submit the resulting message to MailChimp using the API.

ChimpBlast was designed for use in combination with the [RSVPMaker](http://wordpress.org/extend/plugins/rsvpmaker/) event management plugin. You can import your RSVPMaker events into ChimpBlast, add introductory text, and send out your event invitations. The RSVP Now link in the invitation will automatically be coded to include a reference to the recipient's email address, allowing RSVPMaker to retrieve that person's profile details so they don't have to be reentered manually.

You can import a your MailChimp template and add CSS to help your WordPress content display better, such as the markup for WordPress photos and captions, or your own stylings. The MailChimp service automatically inlines the CSS code to work better with email clients, which don't always respect CSS in the header.

Sign up for my mailing list at [RSVPMaker.com](http://www.rsvpmaker.com/) and I'll be happy to send examples.

== Installation ==

1. Upload the entire `chimpblast` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Visit the options page to configure defaults. You must log into your MailChimp.com account and obtain an API Key. From the MailChimp dashboard, look under Account, then API Keys & Info.
1. Customize the template as necessary. The Chimp Template is a submenu under ChimpBlasts. You can download templates you use on MailChimp.com and load them here. We provide a few suggested stylesheet tweaks for compatibility with WordPress formats.

For basic usage, you can also have a look at the [plugin homepage](http://www.rsvpmaker.com/chimpblast-plugin-for-mailchimp/).

== Frequently Asked Questions ==

= Where can I get more information about using RSVPMaker? =

For basic usage, you can also have a look at the [plugin homepage](http://www.rsvpmaker.com/chimpblast-plugin-for-mailchimp/).

== Credits ==

    MailChimp
    Copyright (C) 2011 David F. Carr

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    See the GNU General Public License at <http://www.gnu.org/licenses/>.
	
	ChimpBlast incorporates code from the MailChimp SDK for PHP.
	
== Changelog ==

= 1.3.3 =

Added action hook for 'rsvpmaker_email_list_ok' - works with RSVPMaker to automatically add someone to the email list if they check the optional 'email_list_ok' (add me to your email list) checkbox on the RSVP form.

= 1.3.2 =

Corrected instructions for cron setup (file path)

= 1.3.1 =

Update to Settings screen. Eliminated the requirement to specify the file path to MailChimp API class file.

= 1.3 =

* Change selected list per broadcast
* Cron script for weekly newsletters

= 1.2.1 =

Renamed MailChimp API class to avoid conflicts with other plugins, such as the MailChimp signup form plugin.

= 1.2 =

* More options for adding blog entries, listings of blog entries, or RSVPMaker events into a draft eblast
* Fixed some PHP shortcuts that don't work well on all systems.

= 1.1 =

* Bug fix to function for loading posts.
* Eliminating most filters on the_content in loadpost.php and chimpblast-template.php.

= 1.0 =

* First publication to MailChimp repository, March 13, 2011

= 0.9 =

* Beta available for download from RSVPMaker.com

= 0.6 =

* Prototype published to RSVPMaker.com, 2010