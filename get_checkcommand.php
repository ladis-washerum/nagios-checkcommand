<?php
	# https://github.com/ladis-washerum/nagios-checkcommand
  
  const USER1 = "/usr/lib64/nagios/plugins";
	const CACHEFILE = "/var/spool/nagios/objects.cache";

	# request : http://<SERVER>/nagios/get_checkcommand.php?host=MYHOSTNAME&service=MYSERVICENAME
	if(! isset($_GET['host']) || ! isset($_GET['service'])) {
		return;
	}

	$host = $_GET['host'];
	$service  = $_GET['service'];
	# Sanitize
	$service = preg_replace('/\//', '\/', $service); # preg_match must not interpret '/'

	$host_def    = array();
	$service_def = array();
	$command_def = array();

	$myfile = fopen(CACHEFILE, "r") or die("Unable to open file!");
	while ($line = fgets($myfile)) {
		if( preg_match("/\s*define host/", $line) ) {
			if( preg_match("/\s*host_name\s*$host\s*$/", fgets($myfile)) ) { # This first line looks like : host_name MYHOSTNAME 
				while ($line = fgets($myfile)) { # Build hash with host definition
					if( preg_match("/^\s*}\s*$/", $line) ) {
						break;
					}
					$nagios_var = preg_replace('/^\s*(.*?)\s+.*/', '${1}', $line);
					$nagios_var = preg_replace('/\s/', '', $nagios_var); # to avoid a space at last char
					$nagios_var_value = preg_replace('/^\s*.*?\s+/', '', $line);
					$host_def[$nagios_var] = $nagios_var_value;
				}
				#print_r( $host_def);
			}
		} elseif( preg_match("/\s*define service/", $line) ) {
			if( preg_match("/\s*host_name\s*$host\s*$/", fgets($myfile)) ) { # This first line looks like : host_name MYHOSTNAME
				if( preg_match("/\s*service_description\s*$service\s*$/", fgets($myfile)) ) { # The second line looks like : service_description MYSERVICEDESC
					while ($line = fgets($myfile)) { # Build hash with service definition
						if( preg_match("/^\s*}\s*$/", $line) ) {
							break;
						}
						$nagios_var = preg_replace('/^\s*(.*?)\s+.*$/', '${1}', $line);
						$nagios_var = preg_replace('/\s/', '', $nagios_var); # to avoid a space at last char
						$nagios_var_value = preg_replace('/^\s*.*?\s+/', '', $line);
						$service_def[$nagios_var] = $nagios_var_value;
					}
				}
                        }
		} elseif( preg_match("/\s*define command/", $line) ) {
			$command_name = "";
			$command_line = "";
			while ($line = fgets($myfile)) { # Build hash with commands list definition
				if( preg_match("/^\s*}\s*$/", $line) ) {
					break;
				}
				$nagios_var = preg_replace('/^\s*(.*?)\s+.*$/', '${1}', $line);
				$nagios_var = preg_replace('/\s/', '', $nagios_var); # to avoid a space at last char
				$nagios_var_value = preg_replace('/^\s*.*?\s+/', '', $line);
				if($nagios_var == "command_name") {
					$command_name = $nagios_var_value;
				} elseif ($nagios_var == "command_line") {
					$command_line = $nagios_var_value;
					$command_def[$command_name] = $command_line;
				}
                         }
		}
	}
	fclose($myfile);

	# Now we have retrieved all the informations. Building checkcommand...
	$svcRealName = preg_replace('/\!.*$/', '', $service_def["check_command"]);
	$svcArgs = preg_replace('/^[^!]+[!]?/', '', $service_def["check_command"]);

	$args = array();
	while (strlen($svcArgs) > 0) {
		$args[] = preg_replace('/^([^!]+).*/', '${1}', $svcArgs);
		$svcArgs = preg_replace('/^[^!]+[!]?/', '', $svcArgs);
	}
	//print_r($args);
	
	$command_line = $command_def[$svcRealName];
	$command_fields = preg_split('/\s+/', $command_line, -1, PREG_SPLIT_NO_EMPTY);
	//print_r($command_fields);

	$command_nagios = "";
	foreach ($command_fields as $v) {
		if( preg_match('/^[\'\"]?\$_HOST.*\$[\'\"]?$/', $v) ) { # Host macro, need to change with its value. [\'\"]? -> a param can be surrounded by '' or ""
			$param = preg_replace('/^[\'\"]?\$_HOST(.*)\$[\'\"]?$/', '${1}', $v);
			$param = "_" . $param;
			$value = preg_replace('/\$_HOST(.*)\$/', $host_def[$param], $v);
			$command_nagios .= $value . " ";
		} elseif ( preg_match('/^[\'\"]?\$_SERVICE.*\$[\"\']?$/', $v) ) { # Service macro, need to change with its value
			$param = preg_replace('/^[\'\"]?\$_SERVICE(.*)\$[\'\"]?$/', '${1}', $v);
			$param = "_" . $param;
			$value = preg_replace('/\$_SERVICE(.*)\$/', $service_def[$param], $v);
			$command_nagios .= $value . " ";
		} elseif ($v == '$HOSTADDRESS$') { # Nagios parameter, need to change with its value
			$command_nagios .= $host_def["address"] . " ";
		} elseif ( preg_match('/\$USER1\$/', $v) ) { #Nagios variable, need to change with its value
			$param = preg_replace('/\$USER1\$/', USER1, $v);
			$command_nagios .= $param . " ";
		} elseif ( preg_match('/\$ARG[0-9]+\$/', $v) ) { # match $ARG1$, $ARG2$ etc
      			$argNb = preg_replace('/\$ARG([0-9]+)\$/', '${1}', $v);
      			$argRp = $args[$argNb-1];
      			if (preg_match('/^\$_HOST.*$/', $argRp)) {
      			  $param = preg_replace('/^\$_HOST(.*)$/', '${1}', $argRp);
      			  $param = "_" . $param;
      			  $param = preg_replace('/\s+$/', '', $param);
      			  $argRp = $host_def[$param];
      			} elseif (preg_match('/^\$_SERVICE.*$/', $argRp)) {
      			  $param = preg_replace('/^$_SERVICE(.*)$/', '${1}', $argRp);
        		  $param = "_" . $param;
       			  $param = preg_replace('/\s+$/', '', $param);
       			  $argRp = $service_def[$param];
      			}
      			$command_nagios .= $argRp . " ";
   		 }else { # Just copy
			$command_nagios .= $v . " ";
		}	
	}
print $command_nagios;

?>
