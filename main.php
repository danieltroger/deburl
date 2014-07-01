<?php
error_reporting(E_ALL);
var_dump(find(getpkglist("http://repo.natur-kultur.eu"),"Package","eu.danieltroger.stether")); // http://apt.saurik.com/dists/ios/main/binary-darwin-arm/
function getpkglist($repourl)
{
$repourl = validate($repourl);
$pkglt = getpkglisttype($repourl);
$pkgurl = $repourl . $pkglt;
if(!$pkglt)
{
die("Couldn't find packages listing");
}
$rawlist = curl($pkgurl);
if($pkglt == "Packages")
{
return fix($rawlist);
}
elseif($pkglt == "Packages.bz2")
{
return fix(bzdecompress($rawlist));
}
elseif($pkglt == "Packages.gz")
{
$tmp = "tmp" . rand(1,999);
$out = "";
file_put_contents($tmp,$rawlist);
$buffer_size = 4096;
$file = gzopen($tmp, 'rb');
while(!gzeof($file)) {
$out .= gzread($file, $buffer_size);
}
gzclose($file);
unlink($tmp);
return fix($out);
}
}
function validate($repourl)
{
$lastchar = $repourl[strlen($repourl)-1];
if($lastchar != "/")
{
$repourl .= "/";
}
if(substr($repourl,0,7) != "http://")
{
die("Invalid protocol");
}
return $repourl;
}
function getpkglisttype($c)
{
$a = array("Packages.gz","Packages.bz2","Packages");
foreach($a as $b)
{
if(found($c . $b))
{
return $b;
}
}
return false;
}
function found($url)
{
$headers = get_headers($url);
if(strpos($headers[0],"404") !== false)
{
return false;
}
else
{
return true;
}
}
function curl($url)
{
$ch = curl_init( $url );
curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
curl_setopt( $ch, CURLOPT_HEADER, true );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_USERAGENT, "PHP55");
$response = preg_split( '/([\r\n][\r\n])\\1/', curl_exec( $ch ));
$response = preg_split( '/([\r\n][\r\n]){2}/', curl_exec( $ch ),2);
curl_close( $ch );
return $response[1];
}
function find($haystack,$key,$value,$start = 0)
{
$lines = explode("\n",$haystack);
for($index = $start;$index < (sizeof($lines) -1);$index++)
{
$line = $lines[$index];
$splitted = explode(":",$line);
$splitted[1] = substr($splitted[1],1);
if($splitted[0] == $key)
{
if($splitted[1] == $value)
{
echo "{$splitted[1]} matched {$value}\n";
return $index;
}
else
{
echo "{$splitted[1]} didn't match {$value}\n";
}
}
}
return false;
}
function fix($a)
{
return str_replace("\r","",$a);
}
