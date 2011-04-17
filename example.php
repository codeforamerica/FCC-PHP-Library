<?php
	require "lib/fcc.class.php";
		
	$fcc_api = new FCCApi();

	$result = $fcc_api->findCensusBlock(37.43, -122.17);
	echo "Renewal = " . print_r($result, true) . "<br>\n";

	$result = $fcc_api->getLicenseIssued();
	echo "Issued = " . print_r($result, true) . "<br>\n";
	
?>