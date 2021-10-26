#!/usr/bin/php
<?php

// Nur einfache Fehler melden
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// set timer
$beginn = microtime(true); 



print "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ RESULT START\n";

// mysql
$dbhost = '127.0.0.1';
$dbuser = 'dbuser';
$dbpass = 'dbpass';

// postgres
$pshost = '127.0.0.1';
$psuser = 'network_info';
$pspass = 'network_info';
$counter=0;
$offset=0;
$debug=1;



if ($debug){
	var_dump($argv);
}


$conn = new PDO('mysql:host=localhost;dbname=pwhois', $dbuser, $dbpass);

if ($conn){
       	print "mysql connect OK.\n";
} else {
	print "mysql connect failed.\n";
}
$psconn = new PDO('pgsql:host=localhost;port=5432;dbname=network_info', $psuser, $pspass); 

if ($psconn){
        print "postgresql connect OK.\n";
} else {
        print "postgresql connect failed.\n";
}
//
// we have db-connects - letz check no of recorde
//
// dropped all ipv4 records from network_info:
// by means of 'delete from bulk where family(inetnum)=4;'
// cause importing full bulkwhois arin_db.txt generally fails on network_info 
// so letz import records from pwhois database - data with muiltiple cidrs per dataset will throw error
// 
$query = "SELECT count(*) as num from netblock where isipv4=1";
// $query = "SELECT count(*) as num from netblock where source=1 and isipv6=1";

if ($debug){
        print "$query\n";
}

// prepare statement
$result = $conn->prepare($query);

if($result->execute()){

    while($row = $result->fetch()) {
    $recordnum = $row['num'];

    }
    
    
    if ($debug){
        print "We have $recordnum records to get...\n";
	}
}
$maxnum = $recordnum + 1;
// Do Loop for matchin records
while ($counter < $maxnum ){

//$query  = "SELECT netHandle, netname, country, netcidr, org_id, orgname, registerdate, updatedate, source FROM netblock LIMIT 0,10";
$query  = "SELECT id,netHandle, netname, country, netcidr, org_id, orgname, registerdate, updatedate, source FROM netblock where isipv4=1 LIMIT {$offset},10000";
// query  = "SELECT id,netHandle, netname, country, netcidr, org_id, orgname, registerdate, updatedate, source FROM netblock where source=1 and isipv6=1 LIMIT {$offset},10000";
if ($debug){
	print "$query\n";
}

// prepare statement
$result = $conn->prepare($query);

if($result->execute()){

    while($row = $result->fetch()) {
        $netid = $row['id'];
	    $nethandle = $row['netHandle'];
	    $netname = $row['netname'];
	    $netorg=$row['org_id'];
	    $netorgname=$row['orgname'];
	    $netcountry=$row['country'];
	    $netcidr=$row['netcidr'];
	    $netregister=$row['registerdate'];
	    $netupdate=$row['updatedatel'];
	    $netsourcecode=$row['source'];
		// pwhois uses codes for source RIR
                            //           'arin'     => 1,
                            //           'ripe'     => 2,
                            //           'apnic'    => 3,
                            //           'jpnic'    => 4,
                            //           'afrinic'  => 5,
                            //           'lacnic'   => 6,
                            //           'twnic'    => 7,
                            //           'krnic'    => 8,
                            //           'brnic'    => 9,
                            //           'cnnic'    => 10,
                            //           'aunic'    => 11

                                if ($netsourcecode == 1) $source='arin';
                                if ($netsourcecode == 2) $source='ripe';
                                if ($netsourcecode == 3) $source='apnic';
                                if ($netsourcecode == 4) $source='jpnic';
                                if ($netsourcecode == 5) $source='afrinic';
                                if ($netsourcecode == 6) $source='lacnic';
                                if ($netsourcecode == 7) $source='twnic';
                                if ($netsourcecode == 8) $source='krnic';
				if ($netsourcecode == 9) $source='brnic';
	    			if ($netsourcecode == 10) $source='cnnic';
                                if ($netsourcecode == 11( $source='aunic';
				// fill up empty values with defaults			 
				if ($source == '') $source='ripe';
				if ($netregister == '') $netregister='1970-01-01';
				if ($netupdate == '') $netupdate='1970-01-01';

	print "Record $counter/$recordnum: $netid | $netcidr | $netcountry | $nethandle | $netname | $netorg | $netorgname | $netregister | $netupdate | $source |\n";
        // letz insert record into network_info
	$psquery = "insert into block (inetnum,netname,country,description,maintained_by,created,last_modified,source) VALUES ('{$netcidr}','{$netname}','{$netcountry}','{$netorg}','{$nethandle}','{$netregister}','{$netupdate}','{$source}')";
	if ($debug){
	    print $psquery."\n";
	}
	// prepare statement
	$psresult = $psconn->prepare($psquery);

	if($psresult->execute()){
		print "insert OK\n";
	} else {
		print "Error inserting !!!\n";
	}

 	$counter ++;
    } 

   print "Offset: $offset\n";

}
   $offset=$offset + 10000;

} // end loop for records

	$dauer = microtime(true) - $beginn; 

print "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ RESULT END\n";

 if ($debug){
                print "$counter Datensaetze\n";
        }


echo "Query-Duration: $dauer sec.\n";



?>
