<?php
// make this use our timezone
date_default_timezone_set('America/Toronto');
// import everything we need
require '**************\Composer\vendor\autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

#region truck class
class Truck
{
    #region variables
    private $truckName;
    private $lon;
    private $lat;
    #endregion

    function __construct ($truckName, $lon, $lat)
    {
        $this->setTruckName($truckName);
        $this->setLon($lon);
        $this->setLat($lat);
    }

    #region setters & getters
    public function setTruckName ($truckName)
        {$this->truckName = $truckName;}
    public function getTruckName ()
        {return $this->truckName;}

    public function setLon ($lon)
        {$this->lon = $lon;}
    public function getLon ()
        {return $this->lon;}
    
    public function setLat ($lat)
        {$this->lat = $lat;}
    public function getLat ()
        {return $this->lat;}
    #endregion

}
#endregion

#region Pulling data from API
$trucksInYard = array();
$searchCriteria = "{\"vehicleName\": \"ALL\"}";

$division = '*********';
$apiKey = '***************';
$url = '*********************' . $division . '****************';
$headers = array();
$headers[] = 'Content-Type: application/json';
$headers[] = 'Accept: application/json';
$headers[] = 'X-Apikey: ' . $apiKey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POSTFIELDS, $searchCriteria);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$jsonExec = curl_exec($ch);
if(curl_errno($ch))
{
	echo 'Error:' . curl_error($ch);
}

curl_close($ch);
$json = json_decode($jsonExec, true);
$length = count($json['Data'], 0);
echo $length . " trucks are online!\n";
#endregion

#region fill array with truck objects pulled from API
if ($length > 0)
    {
        for ($i = 0; $i < $length; $i++)
            {
                $truckName = $json['Data'][$i]['vehicleName'];
                $lon = $json['Data'][$i]['lon'];
                $lat = $json['Data'][$i]['lat'];
                array_push($trucksInYard, new Truck($truckName, $lon, $lat));
            }
    }
#endregion

#region function to generate string with list of trucks currently in the yard
function getTrucksAM($trucksInYard) {
    $trucksInYardAM = '';
    foreach($trucksInYard as $truck)
    {
            
        if (($truck->getLat() >= "43.71100" && $truck->getLat() <= "43.71400") &&
        (($truck->getLon() >= "-79.58600" && $truck->getLon() <= "-79.58100")))
            {
                if (empty($trucksInYardAM))
                {
                    $trucksInYardAM .= $truck->getTruckName();
                }
                else
                {
                    $trucksInYardAM .= ", " . $truck->getTruckName();
                }
            }
    }
    return $trucksInYardAM;
}
#endregion

#region reset the daily list every morning
if (date("Hi")< "0015")// reset daily truck list
{
    $trucksInYardAM  = getTrucksAM($trucksInYard);
    $fp = fopen(__DIR__ .'/trucksInYard.txt', 'w');
    fwrite($fp, $trucksInYardAM);
    $fp = fopen(__DIR__ .'/trucksInYardAllDay.txt', 'w');
    fwrite($fp, $trucksInYardAM);  
    fclose($fp);
}
#endregion

#region add a new truck to daily truck list
else
{
    //Make array with lsit of trucks that were in yard today
    $trucksInYardAllDay = file_get_contents(__DIR__ .'/trucksInYardAllDay.txt');
    $dailyYardList = explode(", ", $trucksInYardAllDay); //STRING array of truck names that have been in yard
    //var_dump($dailyYardList);

    //Make the array of objects into an arroy of strings so that can compare to other array
    $trucksInYardSTRINGs = array(); // this array will hold the strings
    $i = 0;
    foreach($trucksInYard as $truck)
    {
            
        if (($truck->getLat() >= "43.71100" && $truck->getLat() <= "43.71500") &&
        (($truck->getLon() >= "-79.58770" && $truck->getLon() <= "-79.58100")))
            {
                array_push($trucksInYardSTRINGs, $truck->getTruckName());
            }
    }
    //compare the arrays and add any new trucks
    $newTrucks = array_diff($trucksInYardSTRINGs, $dailyYardList);
    //echo var_dump($newTrucks);
    $newTruckString= implode(", ", $newTrucks);
    echo $newTruckString;
    //add any new trucks to the list
    if (!empty($newTruckString))
    {
        $fp = fopen(__DIR__ .'/trucksInYardAllDay.txt', 'a');
        fwrite($fp, ", " . $newTruckString);  
        fclose($fp);
    }
}
#endregion
?>
