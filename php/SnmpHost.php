<?php

class SnmpHost {
    private $hostname, $community;
    function __construct($hostname, $community) {
        $this->hostname = $hostname;
        $this->community = $community;
    }
    function snmpwalk($object_id, $type = '') {
        snmp_set_valueretrieval(SNMP_VALUE_OBJECT | SNMP_VALUE_PLAIN);
        $array = snmpwalk($this->hostname, $this->community, $object_id);
        foreach($array as $key=>&$value) {
            $value=$this->unpack($value, $type);
        }
        return $array;
    }
    function unpack($obj, $type = '') {
        switch($obj->type) {
            case(2):
                return $obj->value;
            case(4):
                if ($type === 'char') {
                    return unpack("C*", $obj->value);
                } else {
                    $str = $obj->value;
                    $str = iconv('gbk', 'utf-8', $str);
                    return $str;
                }
        }
        return $obj;
    }
    function formatFileSize($fileSize) {
        $unit = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $i = 0;
        $inv = 1 / 1024;
        while($fileSize >= 1024 && $i < 8) {
            $fileSize *= $inv;
            ++$i;
        }
        //$fileSize = intval($fileSize * 100) / 100;
        $fileSizeTmp = sprintf("%.2f", $fileSize);
        return $fileSizeTmp . ' ' . $unit[$i];
    }
    function PercentFormatted($float) {
        return number_format($float * 100, 1) . ' %';
    }
    function getTime() {
        $hrSystemDate = $this->snmpwalk(".1.3.6.1.2.1.25.1.2.0", 'char');
        $d = $hrSystemDate[0];
        date_default_timezone_set('PRC');
        $time = mktime($d[5], $d[6], $d[7], $d[3], $d[4], $d[1] * 256 + $d[2]);
        $time += $d[8] / 10;
        return $time;
    }
    function hrStorageTable() {
        $hrStorageDescr = $this->snmpwalk(".1.3.6.1.2.1.25.2.3.1.3", 'string');
        $hrStorageAllocationUnits = $this->snmpwalk(".1.3.6.1.2.1.25.2.3.1.4");
        $hrStorageSize = $this->snmpwalk(".1.3.6.1.2.1.25.2.3.1.5");
        $hrStorageUsed = $this->snmpwalk(".1.3.6.1.2.1.25.2.3.1.6");
        $ret = array();
        foreach ($hrStorageDescr as $key => $value) {
            $row = array();
            $row['name'] = explode(' ', $value)[0];
            $row['Descr'] = $value;
            $row['AllocationUnits'] = $hrStorageAllocationUnits[$key];
            $row['Size'] = $hrStorageSize[$key];
            if ($row['Size'] == 0) {
                continue;
            }
            $row['Used'] = $hrStorageUsed[$key];
            $row['SizeBytes'] = $row['Size'] * $row['AllocationUnits'];
            $row['SizeBytesFormatted'] = $this->formatFileSize($row['SizeBytes']);
            $row['UsedBytes'] = $row['Used'] * $row['AllocationUnits'];
            //$row['UsedBytesFormatted'] = $this->formatFileSize($row['UsedBytes']);
            $row['FreeBytes'] = ($row['Size'] - $row['Used']) * $row['AllocationUnits'];
            $row['FreeBytesFormatted'] = $this->formatFileSize($row['FreeBytes']);
            $row['UsedPercent'] = $row['Used'] / $row['Size'];
            $row['FreePercent'] = 1 - $row['Used'] / $row['Size'];
            $row['UsedPercentFormatted'] = $this->PercentFormatted($row['UsedPercent']);
            $row['FreePercentFormatted'] = $this->PercentFormatted($row['FreePercent']);
            unset($row['UsedPercent']);
            unset($row['FreePercent']);
            unset($row['AllocationUnits']);
            unset($row['Size']);
            unset($row['Used']);
            unset($row['SizeBytes']);
            unset($row['UsedBytes']);
            unset($row['FreeBytes']);
            if ($value === 'Virtual Memory') {
                $this->data['VirtualMemory'] = $row;
                continue;
            }
            if ($value === 'Physical Memory') {
                $this->data['PhysicalMemory'] = $row;
                continue;
            }
            $ret[] = $row;
        }
        return $ret;
    }
    function hrProcessorLoad() {
        $hrProcessorLoad = $this->snmpwalk(".1.3.6.1.2.1.25.3.3.1.2");
        $sum = 0;
        foreach($hrProcessorLoad as $value) {
            $sum += $value;
        }
        $Percent =  $sum / count($hrProcessorLoad);
        return $this->PercentFormatted($Percent / 100);
    }
    function data() {
        $this->data = array();
        $this->data['Time'] = $this->getTime();
        $this->data['DateTime'] = date('Y-m-d H:i:s', $this->data['Time']);
        $this->data['StorageTable'] = $this->hrStorageTable();
        $this->data['ProcessorLoad'] = $this->hrProcessorLoad();
        return $this->data;
    }
}

