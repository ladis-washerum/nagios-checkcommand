// https://github.com/ladis-washerum/nagios-checkcommand

function printCheckCommand( debug = 0 ) {
	// Exit the button to copy cmd is already inserted in document
	if (window.frames[1].document.getElementById('check_command')) {
		if(debug) console.log("check_command already present in table.command");
		//clearInterval(timer);
		return 0;
	}

	// Detect the 'Service Information' HTML page
	var element = window.frames[1].document.getElementsByClassName('infoBoxTitle');
	if (element.length == 1 && element.item(0).innerHTML == 'Service Information') {
		if(debug) console.log ("Service page detected");
		// Parse DOM to retrieve HOST and SERVICE on the screen
		var goodplace = window.frames[1].document.getElementsByTagName('table').item(0).getElementsByTagName('tr').item(0).getElementsByTagName('td').item(0).nextElementSibling;
		var serviceName = goodplace.getElementsByClassName('dataTitle')[0].innerHTML;
		var hostName    = goodplace.getElementsByClassName('dataTitle')[1].innerHTML;
		if(debug) console.log(`Host(${hostName}) Service(${serviceName})`);

		// Ajax call - retrieve the checkcommand for the specific host and service
		$.ajax({
		       url : `/nagios/get_checkcommand.php?host=${hostName}&service=${serviceName}`,
		       type : 'GET',
		       dataType : 'html',
		       success : function(code_html, statut){
				// Calculate table '.command' nb rows
				var totalRowCount = 0;
				var table = window.frames[1].document.querySelectorAll("table.command")[0];
				var rows = table.getElementsByTagName("tr");
				for (var i = 0; i < rows.length; i++) {
				    totalRowCount++;
				}

				// Add row and cells
				var row = table.insertRow(totalRowCount);
				var cell1 = row.insertCell(0);
				var cell2 = row.insertCell(1);
				cell1.innerHTML = '<img src="/nagios/images/clipboard.png" alt="Copy" title="Copy the Service Checkcommand To Clipboard" border="0">';
				cell2.innerHTML = '<button id="link_checkcommand" style="background-color: transparent; border: none;text-decoration: none;text-decoration: none; text-align: left; padding: 0; color: #2a46b8; font-family: arial, verdana, serif; font-size: 12px;" onmouseover="this.style.cursor=\'pointer\';this.style.textDecoration=\'underline\';" onmouseleave="this.style.textDecoration=\'none\';">Copy check command into clipboard</button>';
				row.id = 'check_command';

				// Allow to copy the checkcommand to clipboard
				var inputText = window.frames[1].document.createElement("input");
				inputText.type="text";
				inputText.id = "inputText_checkcommand";
				inputText.style.width="0px";
				inputText.style.height="0px";
				window.frames[1].document.body.appendChild(inputText);
				window.frames[1].document.getElementById("inputText_checkcommand").value = code_html;

				// Add event on '#link_checkcommand' (button which looks like a link) to copy the checkcommand into clipboard
				window.frames[1].document.getElementById("link_checkcommand").addEventListener("click", function() {
					var inputText = window.frames[1].document.getElementById("inputText_checkcommand");
					inputText.select();
					document.execCommand('copy');
					window.frames[1].document.getElementById("inputText_checkcommand").remove();
					});
			       },
			error : function(resultat, statut, erreur){
				console.log("Error : " + erreur);
		       }
		});
	} else {
		if(debug) console.log("Not on Service page");
	}
}

