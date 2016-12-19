<html><head><title>Valve-Games Web Scanner v0.6</title>
<meta content="text/html; charset=utf-8" http-equiv=content-type>
<link rel="stylesheet" href="style.css" type="text/css">
</head>
<body>
<script language="JavaScript">
function checkip()
{
	location.href="?host="+document.getElementById('host').value;
}
function refresh()
{
	if (document.getElementById('id').value != '-1')
		location.href="?id="+document.getElementById('id').value;
}
function players()
{
<?php
	if (isset($_GET['host']))
		echo 'location.href="?players&host="+document.getElementById(\'host\').value;';
	else
	{
?>
	if (document.getElementById('host').value)
		location.href="?players&host="+document.getElementById('host').value;
	else
		location.href="?players&id="+document.getElementById('id').value;
<?php
	}
?>
}
</script>
<center>
<h2>Valve-Games Web Scanner v0.6</h2><hr>
<?php
include 'config.php';
include 'hlds.php';

$count=count($servers);
if (isset($_GET['id']))
{
	$id=abs((int)$_GET['id']+0);
	if ($id<0 || $id > $count-1 || !$servers[$id][1])
		$id=0;
	$adress=(string)$servers[$id][1];
}
else if (isset($_GET['host']))
{
	$adress=$_GET['host'];
	$id=$count;
}
else
	$id=0;



if ($id!=count($servers))
{
	$adress=(string)$servers[$id][1];
}
else
{
	$adress=isset($_GET['host'])?$_GET['host']: $servers[0][1];
	$id=$count;
}


if (isset($_GET['host']))
	echo '<input id=host name=host value='.$_GET['host'].'> ';
else
	echo '<input id=host name=host> ';

echo '<input type=button value="Проверить IP" onclick="checkip();"><p>';
echo '<select name="id" id="id" onchange="refresh();">';
if (isset($_GET['host']))
	echo '<option value='.$_GET['host'].' selected>'.$_GET['host'];
for ($i=0;$i<$count;$i++)
{
	if ($servers[$i][1])
		echo $i==$id ? '<option value='.$i.' selected>'.$servers[$i][0] : '<option value='.$i.'>'.$servers[$i][0];
	else
		echo '<option value=\'-1\'>'.$servers[$i][0].'</option>';
}
echo '</select> ';
echo '<input type=button value="Игроки" onclick="players();"><p>';

$server=new hlds();
if (!$server->connect($adress))
	echo $server->error;
else
{
	if (isset($_GET['players']))
	{
		$info=$server->info();
		$players=$server->get_players();
		echo '<b>Сервер: '.$info['name'].'<br>';
		echo 'Игроков: ',$info['players'],"<br></b>\n<hr>";
		echo '<table><tr><td width=180><b>Игрок</b><td><b>Очки</b>';
   		for ($i=0;$i<count($players);$i++)
		{
			echo '<tr><td>',$players[$i]['name'];
			echo '<td>',$players[$i]['frag'];

		}
		echo '</table><hr>';

		if (isset($_GET['host']))
			echo '<a href="?host='.$_GET['host'].'">Назад</a>';
		else
			echo '<a href="?id='.$id.'">Назад</a>';
	}
	else
	{
		$info=$server->info();

		echo '<hr><table><tr><td width=170>IP<td>'.$adress;
		echo '<tr><td>Имя<td>'.$info['name'];
		echo '<tr><td>Карта<td>'.$info['map'];
		echo '<tr><td>Мод<td>'.$info['mod'];
		echo '<tr><td>Дескриптор<td>'.$info['descriptor'];
		echo '<tr><td>Игроки<td>'.$info['players'].'/'.$info['max_players'];
		echo '<tr><td>Тип<td>'.$info['type'];
		echo '<tr><td>ОС<td>'.$info['os'];
		echo '<tr><td>Ботов<td>'.$info['bots'];
		echo '</table><p>';

		$mapname=$info['map'].'.jpg';
		$mapname='./img/'.$info['mod'].'/'.$mapname;
		if (!file_exists($mapname))
		{
			$mapname='./img/noimage.png';
		}
		echo "<img src=$mapname id='mapimg'>";
        echo '<hr>';
	}
}
?>

</center>
</body></html>
