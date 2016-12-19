<?php


class hlds
{	var $socket;
	var $host='127.0.0.1';
	var $port=27015;


	var $error='';

	var $connected=false;

	var $rcon_passwd='';
	var $rcon_challenge=0;


	function hlds($host='',$port=27015)
	{    	if ($host!='')
    		$this->connect($host,$port);
    }

	function send($cmd)
	{		if (!$this->connected)
		{
			$this->error = 'Не подключён к серверу!';
			return '';
		}

        $data='';

		fwrite($this->socket,$cmd);



		$data = fread ($this->socket, 1);

		if (!$data)
		{
			return false;
		}

		$status = socket_get_status($this->socket);

        if ($status["unread_bytes"] > 0)
        {
           $data .= fread($this->socket, $status["unread_bytes"]);
        }

        if(substr($data, 0, 4) == "\xfe\xff\xff\xff")
	    {

		    $data2 = fread ($this->socket, 1);
		    $status = socket_get_status($this->socket);
		    $data2 .= fread($this->socket, $status["unread_bytes"]);


		    if(strlen($data) > strlen($data2))
		     	$data = substr($data, 14) . substr($data2, 9);
		    else
		     	$data = substr($data2, 14) . substr($data, 9);

	    }
	    else
	    {	    	$data = substr($data, 4);
	    }

		return $data;

	}

	function connect ($host,$port=27015)
	{

		if ($this->connected)
		{
			fclose($this->socket);
			$this->connected = false;
		}

		if (strpos($host,':'))
		{			$this->host=substr($host,0,strpos($host,':'));
			$this->port=substr($host,(strpos($host,':') + 1));
		}
		else
		{			$this->host=$host;
			$this->port=$port;
		}
		$this->host=gethostbyname($this->host);

		if (!ereg('^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$',$this->host))
		{
		    $this->error='Неверный адрес';
			return 0;
  		}

		$this->port=(int)($this->port);

		if ($this->port<=0)
		{
			$this->error='Неверный порт';
			return 0;
		}

		$fp = fsockopen('udp://'.$this->host,$this->port);

		if (!$fp)
		{
			$this->error = 'Ошибка сокета!';
			fclose($fp);
			$this->connected = false;
			return false;
		}


		socket_set_timeout($fp,1);

		$this->socket = $fp;
		$this->connected = true;

		$temp='';

        $temp=$this->send("\xFF\xFF\xFF\xFFi\x00");

		if (!$temp)
			$temp=$this->send("\xFF\xFF\xFF\xFFi\x00");

		if (!$temp)
		{			$this->error = 'Сервер не доступен!';
			fclose($fp);
			$this->connected = false;
			return false;
		}

        if($temp{0}!='j')
        {
			$this->error = 'Сервер не доступен!';
			fclose($fp);
			$this->connected = false;
			return false;
		}

		return true;

	}

	function set_rcon($password)
	{		if (!$this->connected)
		{			$this->error = 'Не подключён к серверу!';
			return 0;
		}

		if ($password=='')
	    {
			$this->error = 'Пустой пароль!';
			return 0;
		}

		$rcon_temp='';

		$rcon_temp=$this->send("\xFF\xFF\xFF\xFFchallenge rcon\x00");


		$this->rcon_challenge = substr($rcon_temp,15);
		$this->rcon_challenge = trim($this->rcon_challenge);

		$rcon_temp='';

		$rcon_temp=$this->send("\xFF\xFF\xFF\xFFrcon ".$this->rcon_challenge.' "'.$password.'" status');

		if (strstr($rcon_temp,'Bad rcon_password'))
		{			$this->error='Неверный пароль!';
			return 0;
		}

		$this->rcon_passwd=$password;

		return true;

	}

	function rcon($cmd)
	{
		if (!$this->connected)
		{
			$this->error='Не подключён к серверу!';
			return 0;
		}


		if ($this->rcon_passwd=='')
		{
			$this->error='Установить пароль!';
			return 'Установить пароль!';
		}


		if ($cmd=='')
		{			$this->error='Пустая команда!';
			return 'Пустая команда!';
		}

		$rcon_temp='';		$rcon_temp=$this->send("\xFF\xFF\xFF\xFFrcon ".$this->rcon_challenge.' "'.$this->rcon_passwd.'" '.$cmd);

		return substr($rcon_temp,1);

	}

	function info_cs16($str)
	{
		$server_info=array();

		$pos=strpos($str,0)+1;
		$pos2=strpos($str,0,$pos+1);

		$server_info["name"]=substr($str,$pos,$pos2-$pos);

		$pos=$pos2;
		$pos2=strpos($str,0,$pos+1);


		$server_info["map"]=substr($str,$pos+1,$pos2-$pos-1);

		$pos=$pos2+1;
		$pos2=strpos($str,0,$pos+1);


		$server_info["mod"]=substr($str,$pos,$pos2-$pos);



		$pos=$pos2+1;
		$pos2=strpos($str,0,$pos+1);


		$server_info["descriptor"]=substr($str,$pos,$pos2-$pos);


		$pos=$pos2;

		$server_info["players"]=ord(substr($str,++$pos,1));


	  	$server_info["max_players"]=ord(substr($str,++$pos,1));


	  	$server_info["protocol"]=ord(substr($str,++$pos,1));


		$server_info["type"]=substr($str,++$pos,1)=='d' ? 'Dedicated' : 'Listen';



	  	$server_info["os"]=substr($str,++$pos,1)=='w' ? 'Windows' : 'Linux';

		$pos+=3;
		$pos2=strpos($str,0,$pos+1);

		$server_info['mod_url']=substr($str,$pos,$pos2-$pos);

		$pos=$pos2+14;


		$server_info["bots"]=ord(substr($str,$pos,1));

	   	return $server_info;

	}

	function info_source($str)
	{


		$server_info=array();


		$pos2=strpos($str,0);
  		$server_info['name']=substr($str,2,$pos2-2);

        $pos=$pos2;
		$pos2=strpos($str,0,$pos+1);
       	$server_info['map']=substr($str,$pos+1,$pos2-$pos-1);

       	$pos=$pos2+1;
		$pos2=strpos($str,0,$pos+1);
        $server_info['mod']=substr($str,$pos,$pos2-$pos);

       	$pos=$pos2+1;
		$pos2=strpos($str,0,$pos+1);
  		$server_info['descriptor']=substr($str,$pos,$pos2-$pos);


		$pos=$pos2+1;
		$server_info['steam_id']=ord($str{$pos}) + (ord($str{$pos+1})<<8);

		$pos=$pos2+3;
		$server_info['players']=ord($str{$pos});

		$pos+=1;
		$server_info['max_players']=ord($str{$pos});

		$pos+=1;
        $server_info['bots']= ord($str{$pos});

		$pos+=1;
        $type=$str{$pos};
        if ($type=='l')
        	$server_info['type']='Listen';
        else if ($type=='d')
        	$server_info['type']='Dedicated';
        else if ($type=='p')
        	$server_info['type']='SourceTV';
        else
        	$server_info['type']='Unknown';

        $pos+=1;
        $type=$str{$pos};
        if ($type=='w')
        	$server_info['os']='Windows';
        else if ($type=='l')
        	$server_info['os']='Linux';
        else
        	$server_info['os']='Unknown';


        $pos+=1;
        if ($str{$pos}=="0x01")
        	$server_info['password']='Yes';
        else
        	$server_info['password']='No';

        $pos+=1;
        if ($str{$pos}=="0x01")
        	$server_info['vac']='Yes';
        else
        	$server_info['vac']='No';

        $pos+=1;
        $pos2=strpos($str,0,$pos);
        $server_info['version']=substr($str,$pos,$pos2-$pos);;

	   	return $server_info;

	}


	function info()
	{
		if (!$this->connected)
		{
			$this->error='Не подключён к серверу!';
			return 0;
		}

		$str='';

		$str=$this->send("\xFF\xFF\xFF\xFFTSource Engine Query\x00");

		if (!$str)
			$str=$this->send("\xFF\xFF\xFF\xFFTSource Engine Query\x00");

		if (!$str)
			$str=$this->send("\xFF\xFF\xFF\xFFTSource Engine Query\x00");

   		if ($str{0}=='m')
   			return $this->info_cs16($str);
   		else if ($str{0}=='I')
   			//return info_source($str);
   			return $this->info_source($str);
   		else
   		{
   			$this->error='Неизвестный тип сервера!';
   			return 0;
   		}


	}


	function get_players($sort='frag_desc')
	{		if (!$this->connected)
		{
			$this->error='Не подключён к серверу!';
			return 0;
		}

		$str='';

		$str=$this->send("\xFF\xFF\xFF\xFF\x559999\x0");

		if (!$str)
			$str=$this->send("\xFF\xFF\xFF\xFF\x559999\x0");

		if (!$str)
			$str=$this->send("\xFF\xFF\xFF\xFF\x559999\x0");
		
		if (strlen($str) == 5)
		{
			$challenge=substr($str,1,4);

			$str='';
		
			$str=$this->send("\xFF\xFF\xFF\xFF\x55".$challenge."\x0");
		
			if (!$str)
				$str=$this->send("\xFF\xFF\xFF\xFF\x55".$challenge."\x0");
		
			if (!$str)
				$str=$this->send("\xFF\xFF\xFF\xFF\x55".$challenge."\x0");
		}
		$playercount=ord(substr($str,1,1));

		$str=substr($str,2);

		$players=array();

		$pos=0;
		for ($i=0;$i<$playercount;$i++)
		{

			$pos2=strpos($str,0,$pos+1);
			$players[$i]['name']=htmlspecialchars(substr($str,$pos+1,$pos2-$pos-1));
			$pos=$pos2+1;
			$players[$i]['frag']=ord(substr($str,$pos,1));
			if ($players[$i]['frag']>200) $players[$i]['frag']-=256;

			$tmptime = unpack('ftime', substr($str, $pos + 4, 4));

			$players[$i]['time']=date('i:s', round($tmptime['time'], 0) + 82800);

			$pos=$pos2+9;
		}

		return $players;

	}


	function get_rules($sort='frag_desc')
	{
		if (!$this->connected)
		{
			$this->error='Не подключён к серверу!';
			return 0;
		}

		$str='';

		$str=$this->send("\xFF\xFF\xFF\xFF\x57\x0");

		$challenge=substr($str,1,4);


		$str=$this->send("\xFF\xFF\xFF\xFF\x56".$challenge."\x0");

		$rulescount=ord(substr($str,0,1));

		$str=substr($str,2);

		$rule=explode("\x00",$str);

		$rules=array();

		for ($i=0;$i<$rulescount;$i++)
		{
			$rules[$i]['name']=$rule[2 * $i];
			$rules[$i]['value'] = $rule[2 * $i + 1];
		}

		return $rules;

	}




}




?>