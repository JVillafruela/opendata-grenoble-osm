<?php
/**
 * 
 * A simple script to convert geojson files to csv files
 * Supports only the 'Point' geometry 
 * 
 * php -d memory_limit=-1 geojson2csv.php datafile.json
 * 
 * @author Jérôme Villafruela
 * @license http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPL v3
 * 
 */




if ($argc!=2) {
    die("Usage {$argv[0]} file.json");
}

// input file
$file=$argv[1];

$json = file_get_contents($file);
if ($json===false) die("Error reading $file \n");
$result=json_decode($json, true);

$lines=array();
$columns=array();
$columns['lat']=0;
$columns['lon']=1;
$k= count($columns);
foreach($result['features'] as $i => $geoinfo) {
    $geometry=$geoinfo['geometry']['type']; 
    if ($geometry != 'Point') continue;
    
    $lon=$geoinfo['geometry']['coordinates'][0];
    $lat=$geoinfo['geometry']['coordinates'][1];
    $lines[$i][0]=$lat;
    $lines[$i][1]=$lon;    
    
    foreach ($geoinfo['properties'] as $property => $value) {
        if(array_key_exists($property, $columns)) {
            $j=$columns[$property];
        } else {
            $k++;
            $columns[$property]=$k;
            $j=$k;
        }
        $lines[$i][$j]=$value;    
    }   
    
}  

$filename=dirname($file) . '/' . basename($file) . ".csv";
save_csv($filename,$lines,$columns);
echo "Output file : $filename " . count($lines) ." lines \n" ;


/*------------------------------------------------------------------------*/

/**
 * save csv file
 * 
 * @param string $filename
 * @param array $lines : data
 * @param boolean $header : column headers
 */
function save_csv($filename, array $lines,array $headers=array()) {
    $sep="\t";
    $eof="\n";
    $out=fopen($filename, 'w');
    if ($out===false) die("Error writing $filename \n");

    if(is_array($headers) && count($headers)>0) {
        $buffer=implode($sep,array_keys($headers));
        fwrite($out,$buffer . $eof);
    }
    
    foreach ($lines as $i => $columns) {
        $buffer=implode($sep,$columns);
        fwrite($out,$buffer . $eof);        
    }
    
    fclose($out);
    
}
