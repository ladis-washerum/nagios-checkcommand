# nagios-checkcommand
Easily retrieve in Nagios UI the checkcommand executed to monitor a service

## Install
Locate the Nagios *html* directory on your server. Under CentOS, the full path is */usr/share/nagios/html*.

In this folder, edit *index.php* file. Modify block :
```
<script LANGUAGE="javascript">
	var n = Math.round(Math.random() * 10000000000);
	document.write("<title>Nagios Core on " + window.location.hostname + "</title>");
	document.cookie = "NagFormId=" + n.toString(16);
 </script>
```
so it looks like this : 
```
<script type="text/javascript" src="/nagios/js/jquery-1.7.1.min.js"></script>
<script src="/nagios/nagios_checkcommand.js"></script>

<script LANGUAGE="javascript">
	var n = Math.round(Math.random() * 10000000000);
	document.write("<title>Nagios Core on " + window.location.hostname + "</title>");
	document.cookie = "NagFormId=" + n.toString(16);

	var timer = window.setInterval(function(){
		printCheckCommand();
	}, 3000);
</script>
```

> Ensure _/usr/share/nagios/html/js/jquery-1.7.1.min.js_ exists, otherwise update your jquery version inside _\<script\>_
	
In the same folder, copy files **_nagios_checkcommand.js_** and **_get_checkcommand.php_** with rights 644.
In the subfolder *images*, copy the **_clipboard.png_** file with also rights 644.

## Configuration
Notice that the scripts think you have a basic Apache configuration, so the Nagios URL is under */nagios* (for example : http://\<myserver\>/nagios).

You can configure a few constants at the top of **_get_checkcommand.php_** file :
- `USER1` the value should be the same that your nagios conf
- `CACHEFILE` the path to the Nagios objects cache file as declared in your own *nagios.conf* file.

## And now, how to get the services checkcommand ?
Restart your web server (for example with : systemctl restart httpd).

Then, in the Nagios UI, access the "Services" page and click on a listed service. 
In the "Service Commands" section, a new entry lets you copy the checkcommand by clicking on the link :)

Enjoy !  :smiley:

## How does it work ? 
Simply, to avoid modify a lot of Nagios files and not see our changes erased in case of package update, we just add a few lines in *index.php*.

So it can call a function in **_nagios_checkcommand.js_** which periodically check your current Nagios UI page. If it detects the "Service Information" page, it retrieves the host and the service, then call **_get_checkcommand.php_** to get the checkcommand and insert a link on the page to copy it.

## Compatibility
Successfuly tested with Nagios 4 and Firefox or Chrome

Note that you must not open links in Nagios web page in new tab ; if you do so, those scripts won't be called. 
