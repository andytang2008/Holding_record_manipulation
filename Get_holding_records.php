<?php
/**Get all holding records according to the holding PID list. We use the fake MMS ID as the coax, because each holding PID uniquely identify the holding record and did not need MMS ID at all.
Attention: be sure to test this code in Sandbox before apply it in Production environment.

Running envirnment: Windows 7 + PHP 5.6.26;
No any garantee!

03-2019
by Andy Tang
*/


ini_set("memory_limit","360M"); //Setup the maximux file size you will open.
$handle = @fopen('holding_PID_list.txt', "r");  //The file contais the list of holding PID you would like to use to download holding records from ALMA. Attention to include file extension. You need to change this file name to your holding PID file name.
if ($handle) { 
   while (!feof($handle)) { 
       $lines[] = fgets($handle, 8192); //16384 is the size of each line can contain.
   } 
  // Print "count of array:".count($lines);
   fclose($handle); 
} 

$fp = fopen('holding_data_from_product.txt', 'w'); //Prepare to output all downloaded holding records(XML format) into this file.

for ($i=0;$i<sizeof($lines);$i++){
	//echo $lines[$i];
	$holdingNumber= str_replace(array("\r\n", "\n", "\r"), '', $lines[$i]);  //Delete the CR and LF sign at the end of each line.
	apicall('0',$holdingNumber,$fp);  //we setup a fake Bib id as a coax.
	//sleep(1);
}

fclose($fp);

function apicall($bibID,$holdingID,$filehandle){

	$ch = curl_init(); //Initiate the Curl
	$baseUrl = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/bibs/{bib_id}/holdings/{holding_id}';  
	$templateParamNames = array('{bib_id}','{holding_id}');
	$templateParamValues = array(urlencode($bibID),urlencode($holdingID));
    $baseUrl = str_replace($templateParamNames, $templateParamValues, $baseUrl);
    // echo $baseUrl;

	$queryParams = array(
		//'user_id_type' => 'all_unique',
		//	'view' => 'full',
		//	'expand' => 'none',
		'apikey' => 'put your API key here' // API key. If you use sandbox key, it will get holding records from sandbox. If you use production key, it will get holding records from production environment.
	);
	$url = $baseUrl . "?" . http_build_query($queryParams);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');  //Using Get method
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec($ch);
	curl_close($ch);
	//echo $response;
	fwrite($filehandle, $response);
}
?>
