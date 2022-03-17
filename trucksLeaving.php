<?php
date_default_timezone_set('America/Toronto');
#region imports
require '********\Composer\vendor\autoload.php';
require '********\Composer\vendor\PHPMailer-master\PHPMailer-master\src\PHPMailer.php';
require '********\Composer\vendor\PHPMailer-master\PHPMailer-master\src\Exception.php';
require '********\Composer\vendor\PHPMailer-master\PHPMailer-master\src\SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
#endregion

#region reset the processed truck list
if (date("Hi")< "0015")// reset processed list
{
    $fp = fopen(__DIR__ .'/processedTrucks.txt', 'w');
    fwrite($fp, "");
    fclose($fp);
    echo "Cleared Processed";
}
#endregion

#region import the lists of trucks needed
$trucksInYard = file_get_contents( __DIR__ .'/trucksInYardAllDay.txt');
$trucksInYardArray = explode(", ", $trucksInYard);

$processedTrucks = file_get_contents( __DIR__ .'/processedTrucks.txt');
if (!empty($processedTrucks))
{
    $processedTrucksArray = explode(", ", $processedTrucks);
}
else
{
    $processedTrucksArray = array();
}
$arraydiff = array_diff($trucksInYardArray, $processedTrucksArray);
#endregion

#region set up the excel spreadsheet
$inputFileName = __DIR__.'\trucksLeavingYard.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Truck Name');
$sheet->setCellValue('B1', 'Time Left Yard');
$sheet->getColumnDimension('A')->setAutoSize(true);
$sheet->getColumnDimension('B')->setAutoSize(true);

$writer = new Xlsx($spreadsheet);
$writer->save('trucksLeavingYard.xlsx');
#endregion

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

#region pull data fom API
$allTrucks = array();
$searchCriteria = "{\"vehicleName\": \"ALL\"}";

$division = '********';
$apiKey = '********';
$url = '********' . $division . '********';
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

#region fill object array with data from API
if ($length > 0)
    {
        for ($i = 0; $i < $length; $i++)
            {
                $truckName = $json['Data'][$i]['vehicleName'];
                $lon = $json['Data'][$i]['lon'];
                $lat = $json['Data'][$i]['lat'];
                array_push($allTrucks, new Truck($truckName, $lon, $lat));
            }
    }
#endregion

#region check if the truck has left the yard
foreach ($allTrucks as $singleTruck)
{
    if (in_array($singleTruck->getTruckName(), $arraydiff))
    {
        if (($singleTruck->getLat() > 43.71472 || $singleTruck->getLat() < 43.71139) && ($singleTruck->getLon() < -79.58769 || $singleTruck->getLon() > -79.58176))
        {
            insertTruckIntoSpreadsheet($spreadsheet, $singleTruck);
            echo $singleTruck->getTruckName() . ", ";
            $index = array_search($singleTruck->getTruckName(), $trucksInYardArray);
            if($index !== FALSE)
            {
                array_push($processedTrucksArray, $trucksInYardArray[$index]);
            }
        }
    }
}
#endregion

#region add any changes to processed trucks
$processedTrucks = implode(", ", $processedTrucksArray);
$fp = fopen(__DIR__ .'/processedTrucks.txt', 'w');
fwrite($fp, $processedTrucks);  
fclose($fp);
#endregion

#region function to add processed trucks to the spreadsheet
function insertTruckIntoSpreadsheet($spreadsheet, $truck)
{
    $inputFileName = __DIR__.'\trucksLeavingYard.xlsx';
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->insertNewRowBefore(2, 1);
    $sheet->setCellValue('A2', $truck->getTruckName());
    $sheet->setCellValue('B2', date("G:i"));

    $writer = new Xlsx($spreadsheet);
    $writer->save('trucksLeavingYard.xlsx');
}
#endregion
?>