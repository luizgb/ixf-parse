<?php

/**
 * ixp-unused-addr.php
 *
 * Lists unused addresses from ixf json
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
    echo "USAGE: php ixp-unused-addr.php <url>".PHP_EOL;
    echo "Parameter <url> is not a valid URL".PHP_EOL;
    exit();
}

$url=$argv[1];
$data=json_decode(getIxf($url), true);


$ixp_prefix_v4=null;
$ixp_prefix_v6=null;

if (is_array($data['ixp_list'])) {
    foreach ($data['ixp_list'] as $ixp) {
        //print_r($ixp);
        //echo $ixp['shortname'];

        if (is_array($ixp['vlan'])) {
            foreach ($ixp['vlan'] as $vlan) {
                //print_r($vlan);

                if (array_key_exists('ipv4', $vlan)){
                    $ixp_prefix_v4[]=$vlan['ipv4']['prefix']."/".$vlan['ipv4']['mask_length'];
                }
                if (array_key_exists('ipv6', $vlan)){
                    $ixp_prefix_v6=$vlan['ipv6']['prefix']."/".$mask6=$vlan['ipv6']['mask_length'];
                }
            }
        }
    }
}

//print_r($ixp_prefix_v4);
//print_r($ixp_prefix_v6);

$member_address_v4=null;
$member_address_v6=null;
if (is_array($data['member_list'])) {
    foreach ($data['member_list'] as $member) {
        //echo "ASN:".$member['asnum'].PHP_EOL;
        if (is_array($member['connection_list'])) {
            foreach ($member['connection_list'] as $connection) {

                if (is_array($connection['vlan_list'])) {
                    foreach ($connection['vlan_list'] as $vlan) {
                        $address4=null;
                        $address6=null;

                        if (array_key_exists('ipv4', $vlan)){
                            $member_address_v4[]=trim($vlan['ipv4']['address']);
                            //$mac=$vlan['ipv4']['mac_addresses'][0];
                        }
                        if (array_key_exists('ipv6', $vlan)){
                            $member_address_v6[]=$address6=trim($vlan['ipv6']['address']);

                        }
                    }
                }
            }
        }
    }
}


//print_r($member_address_v4);
//print_r($member_address_v6);



// imprime lista de IPs que nao estao alocados
foreach ($ixp_prefix_v4 as $cidr_v4) {
    echo "### CIDR: ".$cidr_v4.PHP_EOL;
    $ips=printIpDiff($member_address_v4, $cidr_v4);

    foreach ($ips as $ip){
        echo $ip.PHP_EOL;
    }
}






// functions

function getIxf($url){
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
