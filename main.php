<?php
$repo = $_REQUEST['repo'];
$bs = $_REQUEST['bs'];
if(!isset($bs))
{
  $bs = "";
}
if($repo == "saurik")
{
  $repo = "http://apt.saurik.com/";
  $bs = "dists/ios/main/binary-iphoneos-arm/";
}
elseif($repo == "modmyi")
{
  $repo = "http://apt.modmyi.com";
  $bs = "dists/stable/main/binary-iphoneos-arm/";
}
elseif($repo == "bigboss")
{
  $repo = "http://apt.thebigboss.org/repofiles/cydia";
  $bs = "dists/stable/main/binary-iphoneos-arm/";
}
aaa("DEBURL: repo: {$repo}, package: {$package}, ip: {$_SERVER['REMOTE_ADDR']}, bullshit: {$bs}");
echo pkgurl($repo,$_REQUEST['package'],$bs);
aaa("DEBURL: DONE");
function pkgurl($repo,$pkg,$bs)
{
  $repo = validate($repo);
  $pkglist = getpkglist($repo . $bs);
  $sectionstart = find($pkglist,"Package",$pkg);
  if(!$sectionstart)
  {
    die("Package not found");
  }
  return $repo . find($pkglist,"Filename",NULL,$sectionstart,3);
}
function getpkglist($repourl)
{
  $pkglt = getpkglisttype($repourl);
  $pkgurl = $repourl . $pkglt;
  $cachename = "cache/" . str_replace("/","-",$pkgurl);
  if(!$pkglt)
  {
    die("Couldn't find packages listing");
  }
  if(!file_exists($cachename))
  {
    aaa("Couldn't find {$cachename} in cache, going to download it form {$pkgurl}...");
    $rawlist = curl($pkgurl);
    file_put_contents($cachename,$rawlist);
  }
  else
  {
    aaa("getting {$cachename} form cache");
    $rawlist = file_get_contents($cachename);
  }
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
    aaa("getpkglisttype() {$c}");
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
    aaa("Going to curl {$url}");
    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt( $ch, CURLOPT_HEADER, true );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_USERAGENT, "PHP55");
    $response = preg_split( '/([\r\n][\r\n])\\1/', curl_exec( $ch ));
    $response = preg_split( '/([\r\n][\r\n]){2}/', curl_exec( $ch ),2);
    curl_close( $ch );
    aaa("Curling done");
    return $response[1];
  }
  function find($haystack,$key,$value,$start = 0,$mode = 2)
  {
    $lines = explode("\n",$haystack);
    for($index = $start;$index < (sizeof($lines) -1);$index++)
    {
      $line = $lines[$index];
      $splitted = explode(":",$line);
      $splitted[1] = substr($splitted[1],1);
      if($splitted[0] == $key)
      {
        if($mode == 2)
        {
          if($splitted[1] == $value)
          {
            return $index+1;
          }
        }
        elseif($mode == 3)
        {
          return $splitted[1];
        }
      }
    }
    return false;
  }
  function fix($a)
  {
    return str_replace("\r","",$a);
  }
  function aaa($bbb)
  {
    $date = date("m-d-Y H:i:s ");
    $ls = $date . $bbb;
    file_put_contents("deburl.log",file_get_contents("deburl.log") . "\n" . $ls);
  }
