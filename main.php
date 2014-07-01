<?php
error_reporting(E_ALL);
echo getpkglist("http://repo.natur-kultur.eu"); // http://apt.saurik.com/dists/ios/main/binary-darwin-arm/
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
return $rawlist;
}
elseif($pkglt == "Packages.bz2")
{
return bzdecompress($rawlist);
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
return $out;
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
function p($a)
{
return explode(":",$a);
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
