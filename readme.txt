=== Import users from CSV with meta ===
Contributors: hornero, carazo
Donate link: http://codection.com
Tags: csv, import, importer, meta data, meta, user, users, user meta,  editor, profile, custom, fields, delimiter, update, insert
Requires at least: 3.4
Tested up to: 4.2.3
Stable tag: 1.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A plugin to import users using CSV files to WP database automatically including custom user meta

== Description ==

Clean and easy-to-use Import users plugin. It includes custom user meta to be included automatically from a CSV file and delimitation auto-detector.

## **Basics**

*   Import users from a CSV easily
*   And also extra profile information with the user meta data (included in the CSV with your custom fields)
*   Just upload the CSV file (one included as example)
*   All your users will be created/updated with the updated information, and of course including the user meta
*   Autodetect delimiter compatible with `comma , `, `semicolon ; ` and `bar | `

## **Usage**

Once the plugin is installed you can use it. Go to Tools menu and there, there will be a section called _Insert users from CSV_. Just choose your CSV file and go!

### **CSV generation**

You can generate CSV file with all users inside it, using a standar spreadsheet software like: Microsoft Excel, LibreOffice Calc, OpenOffice Calc or Gnumeric.

You have to create the file filled with information (or take it from another database) and you will only have to choose CSV file when you "Save as..." the file. As example, a CSV file is included with the plugin.

### **Some considerations**

Plugin will automatically detect:

* Charset and set it to **UTF-8** to prevent problems with non-ASCII characters.
* It also will **auto detect line-ending** to prevent problems with different OS.
* Finally, it will **detect the delimiter** being used in CSV file ("," or ";" or "|")


== Screenshots ==

1. Plugin link from dashboard
2. Plugin page
3. CSV file structure
4. Users imported
5. Extra profile information (user meta)


== Changelog ==

= 1.5.1 =
* 	Thanks to Mitch ( mitch AT themilkmob DOT org ) for reporting the bug, now headers do not appears twice.

= 1.5 =
* 	Thanks to Adam Hunkapiller ( of dreambridgepartners.com ) have supported all this new functionalities.
*	You can choose the mail from and the from name of the mail sent.
*	Mail from, from name, mail subject and mail body are now saved in the system and reused anytime you used the plugin in order to make the mail sent easier.
*	You can include all this fields in the mail: "user_nicename", "user_url", "display_name", "nickname", "first_name", "last_name", "description", "jabber", "aim", "yim", "user_registered" if you used it in the CSV and you indicate it the mail body in this way **FIELD_NAME**, for example: **first_name**

= 1.4.2 =
* 	Due to some support threads, we have add a different background-color and color in rows that are problematic: the email was found in the system but the username is not the same

= 1.4.1 =
* 	Thanks to Peri Lane for supporting the new functionality which make possible to activate users at the same time they are being importing. Activate users as WP Members plugin (https://wordpress.org/plugins/wp-members/) consider a user is activated

= 1.4 =
* 	Thanks to Kristopher Hutchison we have add an option to choose what you want to do with empty cells: 1) delete the meta-data or 2) ignore it and do not update, previous to this version, the plugin update the value to empty string

= 1.3.9.4 =
* 	Previous version does not appear as updated in repository, with this version we try to fix it

= 1.3.9.3 =
* 	In WordPress Network, admins can now use the plugin and not only superadmins. Thanks to @jephperro

= 1.3.9.2 =
* 	Solved some typos. Thanks to Jonathan Lampe

= 1.3.9.1 =
* 	JS bug fixed, thanks to Jess C

= 1.3.9 =
* 	List of old CSV files created in order to prevent security problems.
* 	Created a button to delete this files directly in the plugin, you can delete one by one or you can do a bulk delete.

= 1.3.8 =
* 	Fixed a problem with iterator in columns count. Thanks to alysko for their message: https://wordpress.org/support/topic/3rd-colums-ignored?replies=1

= 1.3.7 =
* 	After upload, CSV file is deleted in order to prevent security issues.

= 1.3.6 =
* 	Thanks to idealien for telling us that we should check also if user exist using email (in addition to user login). Now we do this double check to prevent problems with users that exists but was registered using another user login. In the table we show this difference, the login is not changed, but all the rest of data is updated.

= 1.3.5 =
* 	Bug in image fixed
*	Title changed

= 1.3.4 =
* 	Warning with sends_mail parameter fixed
*	Button to donate included

= 1.3.3 =
* 	Screenshot updated, now it has the correct format. Thank to gmsb for telling us the problem with screenshout outdated

= 1.3.2 =
* 	Thanks to @jRausell for solving a bug with a count and an array

= 1.3.1 =
* 	WooCommerce fields integration into profile
*	Duplicate fields detection into profile
*	Thanks to @derwentx to give us the code to make possible to include this new features

= 1.3 =
*	This is the biggest update in the history of this plugin: mails and passwords generation have been added.
*	Thanks to @jRausell to give us code to start with mail sending functionality. We have improved it and now it is available for everyone.
*	Mails are customizable and you can choose 
*	Passwords are also generated, please read carefully the documentation in order to avoid passwords lost in user updates.

= 1.2.3 =
*	Extra format check done at the start of each row.

= 1.2.2 =
*	Thanks to twmoore3rd we have created a system to detect email collisions, username collision are not detected because plugin update metadata in this case

= 1.2.1 =
*	Thanks to Graham May we have fixed a problem when meta keys have a blank space and also we have improved plugin security using filter_input() and filter_input_array() functions instead of $_POSTs

= 1.2 =
*	From this version, plugin can both insert new users and update new ones. Thanks to Nick Gallop from Weston Graphics.

= 1.1.8 =
*	Donation button added.

= 1.1.7 =
*	Fixed problems with \n, \r and \n\r inside CSV fields. Thanks to Ted Stresen-Reuter for his help. We have changed our way to parse CSV files, now we use SplFileObject and we can solve this problem.

=======
= 1.2 =
*	From this version, plugin can both insert new users and update new ones. Thanks to Nick Gallop from Weston Graphics.

= 1.1.8 =
*	Donation button added.

= 1.1.7 =
*	Fixed problems with \n, \r and \n\r inside CSV fields. Thanks to Ted Stresen-Reuter for his help. We have changed our way to parse CSV files, now we use SplFileObject and we can solve this problem.

>>>>>>> .r1121403
= 1.1.6 =
*	You can import now user_registered but always in the correct format Y-m-d H:i:s

= 1.1.5 =
*	Now plugins is only shown to admins. Thanks to flegmatiq and his message https://wordpress.org/support/topic/the-plugin-name-apears-in-dashboard-menu-of-non-aministrators?replies=1#post-6126743

= 1.1.4 =
*	Problem solved appeared in 1.1.3: sometimes array was not correctly managed.

= 1.1.3 =
*	As fgetscsv() have problems with non UTF8 characters we changed it and now we had problems with commas inside fields, so we have rewritten it using str_getcsv() and declaring the function in case your current PHP version doesn't support it.

= 1.1.2 =
*	fgetscsv() have problems with non UTF8 characters, so we have changed it for fgetcsv() thanks to a hebrew user who had problems.

= 1.1.1 =
*	Some bugs found and solved managing custom columns after 1.1.0 upgrade.
*	If you have problems/bugs about custom headers, you should deactivate the plugin and then activate it and upload a CSV file with the correct headers again in order to solve some problems.

= 1.1.0 =
*	WordPress user profile default info is now saved correctly, the new fields are: "user_nicename", "user_url", "display_name", "nickname", "first_name", "last_name", "description", "jabber", "aim" and "yim"
* 	New CSV example created.
*	Documentation adapted to new functionality.

= 1.0.9 =
*   Bug with some UTF-8 strings, fixed.

= 1.0.8 =
*   The list of roles is generated reading all the roles avaible in the system, instead of being the default always.

= 1.0.7 =
*   Issue: admin/super_admin change role when file is too large. Two checks done to avoid it.

= 1.0.6 =
*   Issue: Problems detecting extension solved (array('csv' => 'text/csv') added)

= 1.0.5 =
*   Issue: Existing users role change, fixed

= 1.0.0 =
*   First release

== Upgrade Notice ==

= 1.0 =
*   First installation

== Frequently Asked Questions ==

*   Not yet

== Installation ==

### **Installation**

*   Install **Import users from CSV with meta** automatically through the WordPress Dashboard or by uploading the ZIP file in the _plugins_ directory.
*   Then, after the package is uploaded and extracted, click&nbsp;_Activate Plugin_.

Now going through the points above, you should now see a new&nbsp;_Import users from CSV_&nbsp;menu item under Tool menu in the sidebar of the admin panel, see figure below of how it looks like.

[Plugin link from dashboard](http://ps.w.org/import-users-from-csv-with-meta/assets/screenshot-1.png)

If you get any error after following through the steps above please contact us through item support comments so we can get back to you with possible helps in installing the plugin and more.
