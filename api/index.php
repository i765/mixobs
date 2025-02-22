<?php

// Подключаем общие функции
require_once('api_functions.php');

// Получаем JSON данные
$data = handleJsonInput();

function getPodcasts():string{
	$sql = mysqli_query(dbConn(), "SELECT * FROM podcasts;");
	if (mysqli_num_rows($sql)){
		$data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
		return json_encode($data);
	}
}

function getEpisodes($podcast_id):string{
	$sql = mysqli_query(dbConn(), "SELECT episode_id, episode_name, release_date FROM episodes WHERE podcast_id = {$podcast_id} ORDER BY release_date DESC ;");
	if (mysqli_num_rows($sql)){
		$data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
		return json_encode($data);
	} else {
		return json_encode(['result' => 0]);
	}
}

function getTracklist($episode_id):string{

	$sql = mysqli_query(dbConn(), "SELECT * FROM tracks WHERE episode_id = {$episode_id} ORDER BY timecode DESC;");
	if (mysqli_num_rows($sql)){
		$data = mysqli_fetch_all($sql, MYSQLI_ASSOC);
		return json_encode($data);
	} else {
		return json_encode(['result' => 0]);
	}
}


switch ($data -> action) {
	case 'get-podcasts':
		echo getPodcasts();
	break;
	case 'get-episodes':
		echo getEpisodes((int)$data -> podcast_id);
	break;
	case 'get-tracklist':
		echo getTracklist((int)$data -> episode_id);
	break;



	default:
		
	break;
}



?>