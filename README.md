AUTH MOODLEUSERS 

This plugin creates and updates Mahara user accounts to synchronise with 
a linked Moodle installation (Mahoodle), and so allow Mahara groups to 
be set up before the members of the group have logged in for the first 
time. 

====== REQUIREMENTS ====== 

Mahara 1.10 

====== INSTALLATION ====== 

1.  Copy this folder to htdocs/auth/ 

2.  Add connection details for your Moodle database to htdocs/config.php 
    The user account requires read access to the mdl_users table. 

    $cfg->mdldbtype = 'mysql'; 
    $cfg->mdldbhost = ''; 
    $cfg->mdldbname = ''; 
    $cfg->mdldbuser = ''; 
    $cfg->mdldbpass = ''; 
    $cfg->mdldbprefix = 'mdl_'; 

3.  Log in as admin, go to Administration => Extensions, and click the
    Install link for the plugin under the auth column. 

====== USAGE ===== 

The plugin administration panel allows configuration of User update 
options and Frequency. 

*   Choose which Mahara institution users should be added to, and the file 
    quota they should be allowed. 

*   By default, current Mahara user accounts will have their first name, 
    last name and email addresses updated to match the content of the users 
    table in the Moodle database. If any of these checkboxes are disabled, 
    that field will not be updated and, if all are disabled, the plugin will 
    only create new Mahara accounts. 

*   The Mahara cron job must be running for the plugin to function. 
    However, the administration panel allows the timing and frequency of the 
    updates to be configured from the front end using cron notation. The 
    maximum frequency allowed is once an hour to avoid overloading the 
    server. 
    https://wiki.mahara.org/index.php/Developer_Area/Cron_API#Scheduling_parameters 

*   The number of Mahara accounts to be created or updated can also be 
    configured. Combining this with the timing and frequency configuration, 
    the administrator is able to balance the needs of users with the server 
    resources available. The limit is 1,000 new or updated accounts per run. 

*   Questions, comments or bug fixes welcome 

© 2015 Michael Ford m.ford@qmul.ac.uk