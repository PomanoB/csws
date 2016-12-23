<?
date_default_timezone_set("Europe/Moscow");

include 'hlds.php';
include 'config.php';

// Body
$hlds = new hlds();

$starttime = time() - $checktime;

$do = true;
while($do) {
	if(time() == $starttime+$checktime) {
		foreach($servers as $key=>$server) {
			if(!$hlds->connect($server[1])) {
				if(!isset($servers[$key][2]) || $servers[$key][2] == 0)
					$servers[$key][2] = 1;
			}
			else
				$servers[$key][2] = 0;
		}
		
		$status = 0;
		$ret = "\n".strftime("%H:%M:%S %d.%m.%Y", $starttime)."\n";
		foreach($servers as $key=>$server) {
			if($servers[$key][2]>0) {
				$ret .= $server[0]." - ".$server[1]." - DOWN\n";
			}
			
			if($servers[$key][2] == 1) {
				$servers[$key][2] = 2;
				$status = 1;
			}
		}
		
		echo $ret;
		
		if($status == 1) {
			if($sendmail == 1)
				echo "MAIL SEND! to $email \n";
				mail($email, 'Pinger', "$ret", 'From: $email');
			}
			else
				echo "MAIL NOT SEND!";
		}
		
		$starttime = time();
	}
}
?>