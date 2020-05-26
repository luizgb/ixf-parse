<?php

/**
 * alice-unused-addr.php
 *
 * Lists unused addresses from alice-lg json
 *
 * @package     ixf-parse
 * @author      Luiz Gustavo Barros <luzigb@gmail.com>
 * @version     1.0.0
 * @link        https://github.com/luizgb/ixf-parse
 */



if (!function_exists('curl_init')) {
    echo "curl_init() function is not available. Install php-curl module".PHP_EOL;
    exit();
}

if (!filter_var($argv[1], FILTER_VALIDATE_URL)) {
    echo "USAGE: php alice-unused-addr.php <url> <network>".PHP_EOL;
    echo "Example: php alice-unused-addr.php https://lg.apps.uepg.br/api/v1/routeservers/rs1-v4/neighbors 45.186.143.0/24".PHP_EOL;
    echo "ERROR: Parameter <url> is not a valid URL".PHP_EOL;
    exit();
}

    if (!filter_var(explode("/", $argv[2])[0], FILTER_VALIDATE_IP)) {
    echo "USAGE: php alice-unused-addr.php <url> <network>".PHP_EOL;
    echo "Example: php alice-unused-addr.php https://lg.apps.uepg.br/api/v1/routeservers/rs1-v4/neighbors 45.186.143.0/24".PHP_EOL;
    echo "ERROR: Parameter <network> is not a valid network".PHP_EOL;
    exit();
}




$url=$argv[1];
$cidr=$argv[2];
$data=json_decode(getJson($url), true);


$member_address=null;
if (is_array($data['neighbours'])) {
    foreach ($data['neighbours'] as $neighbour) {
        $member_address[]=$neighbour['address'];
    }
}



echo "### CIDR: ".$cidr.PHP_EOL;
$ips=printIpDiff($member_address, $cidr);

foreach ($ips as $ip){
    echo $ip.PHP_EOL;
}





// functions

function getJson($url){
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

    $curl_response = curl_exec($curl);
    curl_close($curl);

    return $curl_response;
}


function printIpDiff($ip_list, $cidr)
{
    $ips = array();
    $range = getIpRange($cidr);
    for ($ip = $range['firstIP']; $ip <= $range['lastIP']; $ip++) {
        if (in_array(long2ip($ip), $ip_list) === false) {
            $ips[] = long2ip($ip);
        }
    }
    return $ips;
}


function getIpRange(  $cidr) {

    list($ip, $mask) = explode('/', $cidr);

    $maskBinStr =str_repeat("1", $mask ) . str_repeat("0", 32-$mask );      //net mask binary string
    $inverseMaskBinStr = str_repeat("0", $mask ) . str_repeat("1",  32-$mask ); //inverse mask

    $ipLong = ip2long( $ip );
    $ipMaskLong = bindec( $maskBinStr );
    $inverseIpMaskLong = bindec( $inverseMaskBinStr );
    $netWork = $ipLong & $ipMaskLong;

    $start = $netWork+1;//ignore network ID(eg: 192.168.1.0)

    $end = ($netWork | $inverseIpMaskLong) -1 ; //ignore brocast IP(eg: 192.168.1.255)
    return array('firstIP' => $start, 'lastIP' => $end );
}





?>
