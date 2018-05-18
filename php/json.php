<?php

include 'SnmpHost.php';
function HostList($hosts) {
    $ret = array();
    foreach ($hosts as $key => $host) {
        $snmp = new SnmpHost($host, "public");
        $row = $snmp->data();
        $row['host'] = $host;
        $ret[] = $row;
    }
    return $ret;
}


$hosts = array();
$hosts[] = '192.168.0.108';
$hosts[] = '192.168.0.111';

header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');
echo json_encode(HostList($hosts));

