<?php
/**
Attention:this code is used to add 866 field to holding record in ALMA. 

Attention: be sure to test this code in Sandbox before apply it in Production environment.

Running envirnment: Windows 7 + PHP 5.6.26;
No any garantee!

03-2019
by Andy Tang
*/

ini_set("memory_limit","360M"); //Setup the maximux file size you will open.

$holdingFile_handle=file_get_contents("holding_data_from_product.txt"); //Open the xml file we retieved previously by using API
//when using file_get_contents to put xml into variable, no need to escape double quotes. Andy

$holdingFile_handle=str_replace ("<?xml", "$$<?xml", $holdingFile_handle); //add seperator $$ to each xml paragraph

$pos = strpos($holdingFile_handle, "$$");
if ($pos !== false) {
    $newstring = substr_replace($holdingFile_handle, "", $pos, strlen("$$")); //Remove the first occurance of $$ which is seperator.
}   

$process = explode("$$", $newstring); //Use $$ as a seperator to explode xml file and put xml records into array.
 
$handle = @fopen('Holding_PID_and_summary_holding.txt', "r");  //This file contains holding PID and summary holding part.
if ($handle) { 
   while (!feof($handle)) { 
       $lines[] = fgets($handle, 8192); //16384 is the size of each line can contain.
   } 
   //print "count of array:".count($lines);
   fclose($handle); 
} 

for ($i=0;$i<count($process);$i++){
	$pos_left = strpos($process[$i], "<holding_id>")+strlen("<holding_id>");  //Get holding PID left position
	$pos_right=strpos($process[$i], "</holding_id>"); //Get holding PID right position

	$holdingID=substr($process[$i],$pos_left,$pos_right-$pos_left);//Get holding PID
	
	$j=0;
	$summeryHoldingContent="";
	for ($j=0;$j<sizeof($lines);$j++){
			if($holdingID==substr($lines[$j],0,strpos($lines[$j], "|"))){
				echo "find it";
				echo "holdingID:  ".$holdingID;
				$summeryHoldingContent=substr($lines[$j],strpos($lines[$j], "|")+1, strlen($lines[$j])-strpos($lines[$j], "|"));
				$summeryHoldingContent=str_replace(array("\r\n", "\n", "\r"), '', $summeryHoldingContent);
					echo "summeryHoldingContent:  ".$summeryHoldingContent;
			}
		}
	echo "-------------------------beign-----------------------";	
	echo "-------------------------end-----------------------";

	var_dump($process[$i]);
	
	$xml = simplexml_load_string($process[$i]);
		$add=$xml->record[0]->addChild("datafield"); //The below syntax is to add 866 field 
		$add->addAttribute("ind1","4");
		$add->addAttribute("ind2","0");
		$add->addAttribute("tag","866");
		$addsubfield=$add->addChild("subfield",$summeryHoldingContent);
		$addsubfield->addAttribute("code","a");

		echo $xml->saveXML();
	    $data=$xml->saveXML();
	apicall('0',(string)$holdingID,$data); //andy
	echo "------------------------------------------------";
	//echo $contents2;
	//apicall('0',(string)$holdingID,$contents3);
}

function apicall($bibID,$holdingID,$contents){
	$ch = curl_init();
	$baseUrl = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/bibs/{bib_id}/holdings/{holding_id}';
	$templateParamNames = array('{bib_id}','{holding_id}');
	$templateParamValues = array(urlencode($bibID),urlencode($holdingID));
  $baseUrl = str_replace($templateParamNames, $templateParamValues, $baseUrl);

	$queryParams = array(
		'apikey' => 'Put your API key here' // API key. If you use sandbox key, it will get holding records from sandbox. If you use production key, it will get holding records from production environment.
	);
	$url = $baseUrl . "?" . http_build_query($queryParams);
	$data=$contents;
	echo $url;
	//echo "---------------";
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');  //We use PUT to update holding records
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec($ch);
	
	if (curl_errno($ch)) {
		// this would be your first hint that something went wrong
		die('Couldn\'t send request: ' . curl_error($ch));
	} else {
		// check the HTTP status code of the request
		$resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($resultStatus == 200) {
			// everything went better than expected
		} else {
        die('Request failed: HTTP status code: ' . $resultStatus);
		}
	}

	curl_close($ch);

}
?>
