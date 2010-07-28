<?php

if($device[os] != "Snom") {

  echo(" TCP");

  $oids = array ('tcpActiveOpens', 'tcpPassiveOpens', 'tcpAttemptFails', 'tcpEstabResets', 'tcpCurrEstab',
  'tcpInSegs', 'tcpOutSegs', 'tcpRetransSegs', 'tcpInErrs', 'tcpOutRsts');

#  $oids['tcp_collect'] = $oids['tcp'];
#  $oids['tcp_collect'][] = 'tcpHCInSegs';
#  $oids['tcp_collect'][] = 'tcpHCOutSegs';

  unset($snmpstring, $rrdupdate, $snmpdata, $snmpdata_cmd, $rrd_create);
  $rrd_file = $config['rrd_dir'] . "/" . $device['hostname'] . "/netstats-tcp.rrd";

  $rrd_create = "RRA:AVERAGE:0.5:1:600 RRA:AVERAGE:0.5:6:700 RRA:AVERAGE:0.5:24:775 RRA:AVERAGE:0.5:288:797 RRA:MAX:0.5:1:600 \
                 RRA:MAX:0.5:6:700 RRA:MAX:0.5:24:775 RRA:MAX:0.5:288:797";

  foreach($oids as $oid){
    $oid_ds = truncate($oid, 19, '');
    $rrd_create .= " DS:$oid_ds:COUNTER:600:U:10000000"; ## Limit to 10MPPS
    $snmpstring .= " $oid.0"; 
  }

  $snmpstring .= " tcpHCInSegs.0";
  $snmpstring .= " tcpHCOutSegs.0";

  $data = snmp_get_multi($device, $snmpstring);
  
  $rrdupdate = "N";

  foreach($oids as $oid){
    if(is_numeric($data[0][$oid])) 
    { 
      $value = $data[0][$oid]; 
    } else { 
      $value = "0"; 
    }
    $rrdupdate .= ":$value";
  }

  unset($snmpstring);

  if(isset($data[0]['tcpInSegs']) && isset($data[0]['tcpOutSegs'])) {
    if(!file_exists($rrd_file)) { rrdtool_create($rrd_file, $rrd_create); }
    rrdtool_update($rrd_file, $rrdupdate);
    $graphs['netstats-tcp'] = TRUE;
  }

  unset($oids, $data, $data_array, $oid, $protos);
}

?>