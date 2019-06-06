# nagios-checkcommand
Easily retrieve the checkcommand executed by Nagios to check a service

## Install
Locate the Nagios *html* directory on your server. Under CentOS, the full path is */usr/share/nagios/html*
In this folder, edit *index.php* file. 
Modify block 
```
<script LANGUAGE="javascript">
	var n = Math.round(Math.random() * 10000000000);
	document.write("<title>Nagios Core on " + window.location.hostname + "</title>");
	document.cookie = "NagFormId=" + n.toString(16);
 </script>
```
so it looks like this : 
```
<script LANGUAGE="javascript">
	var n = Math.round(Math.random() * 10000000000);
	document.write("<title>Nagios Core on " + window.location.hostname + "</title>");
	document.cookie = "NagFormId=" + n.toString(16);

	var timer = window.setInterval(function(){
		printCheckCommand();
	}, 3000);
</script>
```

In the same folder, copy files **_nagios_checkcommand.js_** and **_get_checkcommand.php_** with rights 644.
In the subfolder *images*, copy the __*clipboard.png__* file.

## And now, how to get the services checkcommand ?
Restart your web server (for exemple : systemctl restart httpd)
Then, in the Nagios Web Console, access the "Services" page and click on a listed service. 
In the "Service Commands" section, a new entry let you copy the checkcommand by clicking on the link :)

Enjoy !

## How does it work ? 
Simply, to avoid modify a lot of Nagios file and not erase changes in case of package update, we add a few lines in *index.php*. 
So it can call a function in **_nagios_checkcommand.js_** which periodically check your current Nagios Web page. If it detects the "Service Information" page, it retrieve the host and the service, then call **_get_checkcommand.php_** to get the checkcommand and insert a link to copy it.
