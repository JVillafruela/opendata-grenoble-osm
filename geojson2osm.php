<?php
/**
 * 
 * A simple script to convert geojson files to xml .osm files
 * Supports only the 'Point' geometry 
 * 
 * @author Jérôme Villafruela
 * @license http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPL v3
 * 
 */

/*---------------------- config -------------------------------------------*/

// transcode geojson tags to OSM ones
$replace_tags=array(
    'MOB_ARCE_ID' => 'ref',
    'MOB_ARCE_NB' => 'capacity',
    'MOB_ARCE_TYP' => '', // ignored
    'MOB_ARCE_DATECRE' => '', //'start_date'
);

// tags to add at each node
$add_tags=array(
    'amenity' => 'bicycle_parking',
    'bicycle_parking' => 'stands'
);

/*------------------------------------------------------------------------*/


if ($argc!=2) {
    die("Usage {$argv[0]} file.json");
}

// input file
$file=$argv[1];

$json = file_get_contents($file);
if ($json===false) die("Error reading $file \n");
$result=json_decode($json, true);

$geometries=array();
$properties=array();
foreach($result['features'] as $j => $geoinfo) {
    $geometry=$geoinfo['geometry']['type']; 
    $geometries[$geometry]=$geometry;
    foreach ($geoinfo['properties'] as $property => $value) {
          $properties[$property]=$property;
    }
    $result['features'][$j]['properties']=process_properties($geoinfo['properties']);
    
}  

$filename=dirname($file) . '/' . basename($file) . ".osm";
save_osm($result,$filename);
echo "Output file : $filename\n" ;



/**
 * Transcode tags
 * 
 * @global array $replace_tags 
 * @global array $add_tags
 * @param array $properties
 * @return array new (osm) tags
 */
function process_properties(Array $properties) {
    global $replace_tags,$add_tags;
    $props=$add_tags;
    foreach ($properties as $key => $value) {
        if(array_key_exists($key, $replace_tags)) {
            $k=$replace_tags[$key];
            if ($k=='') continue;
            $props[$k]=$value;  
        }                  
    }
    //print_r($props);
    return $props;
}

/**
 * save osm file
 * 
 * @param string $geojson 
 * @param string $filename
 */
function save_osm($geojson,$filename) {
    
/* osm file looks like :
<?xml version='1.0' encoding='UTF-8'?>
<osm version='0.6' upload='false' generator='JOSM'>
  <node id='-9122' visible='true' lat='45.1865895' lon='5.7261676'>
    <tag k='amenity' v='bicycle_parking' />
	<tag k='MOB_ARCE_DATECRE' v='20010101000000' />
    <tag k='MOB_ARCE_ID' v='46' />
    <tag k='MOB_ARCE_NB' v='1' />
    <tag k='MOB_ARCE_TYP' v='nouveau modèle' />
    <tag k='name' v='46' />
  </node>    

geojson data sample :
{"type":"Feature",
 	"geometry":{"type":"Point","coordinates":[5.72616761182579,45.1865894781128]},
	"properties":{"MOB_ARCE_ID":46,"MOB_ARCE_NB":1,"MOB_ARCE_TYP":"nouveau modèle","MOB_ARCE_DATECRE":"20010101000000"}}

 */
$out=fopen($filename, 'w');
if ($out===false) die("Error writing $filename \n");

fprintf($out,"<?xml version='1.0' encoding='UTF-8'?>\n");
fprintf($out,"<osm version='0.6' upload='false' generator='geojson2osm'>\n");

foreach($geojson['features'] as $j => $geoinfo) {
    $geometry=$geoinfo['geometry']['type']; 
    $coordinates=$geoinfo['geometry']['coordinates']; 
    if ($geometry=='Point') {
        $id=-(10001+$j);
        $lat=$coordinates[1];
        $lon=$coordinates[0];
        fprintf($out,"\t<node id='%d' visible='true' lat='%f' lon='%f'>\n",$id,$lat,$lon);
        foreach ($geoinfo['properties'] as $property => $value) {
			if ($property=='capacity')
				$value *= 2;	
            fprintf($out, "\t\t<tag k='%s' v='%s' />\n",$property,$value);     
        }
        fprintf($out, "\t\t<tag k=\"source:ref\" v=\"http://sig.grenoble.fr/opendata/Arceaux/json/Arceaux_EPSG4326.json\" >\n");
        fprintf($out, "\t\t<tag k=\"operator\" v=\"Grenoble Alpes Métropole\" >\n");
        fprintf($out, "\t\t<tag k=\"operator:wikidata\" v=\"Q999238\" />\n");
        fprintf($out,"\t</node>\n");
    }
}  
fprintf($out,"</osm>\n");

}
