<?php

// Получние JSON данных

function handleJsonInput():object {

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		$eventMessage = "Не верный REQUEST_METHOD";
		logEvent($eventMessage);
		http_response_code(400); // Ошибка "Bad Request"
		exit();
	}

	// Получаем данные из входящего потока
	$jsonInput = file_get_contents('php://input');

	// Проверяем, удалось ли получить данные
	if ($jsonInput === false || empty($jsonInput)) {
		$eventMessage = "Не удалось прочитать входные данные JSON";
		logEvent($eventMessage);
		http_response_code(400); // Ошибка "Bad Request"
		exit();
	}

	$maxJsonLength = 1024 * 8; // Максимально допустимый объем входящих данных 8 КБ

	// Обработка ошибки: слишком большой объем данных
	if (strlen($jsonInput) > $maxJsonLength) {
		$eventMessage = "Превышение полезной нагрузки JSON запроса";
		logEvent($eventMessage);
		http_response_code(413); // Ошибка "Payload Too Large"
    	exit();
	}

	// Декодируем JSON данные
	$decodedData = json_decode($jsonInput);

	// Проверяем на ошибки декодирования
	if ($decodedData === null && json_last_error() !== JSON_ERROR_NONE) {
		$eventMessage = "Неверные данные JSON";
		logEvent($eventMessage);
		http_response_code(400); // Ошибка "Bad Request"
		exit();
    }

    return $decodedData;

}


// Подключение к БД
function dbConn():object {
    static $link;
    if (!isset($link)) {
    	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    	try {
			$link = mysqli_connect('127.0.0.1', 'root', 'knidpp', 'mixobs');
		} catch (mysqli_sql_exception $e) {
			logEvent("Ошибка подключения к БД - {$e}");
		    exit();
		}
    }
    return $link;
}

// Логирование событий
function logEvent(string $eventMessage = ''):string {
	$fileNameDate = date('d-m-Y');
	$logFile = "{$fileNameDate}.log";
	$timestamp = date('Y-m-d H:i:s');
	$request_info = "Request method: ({$_SERVER['REQUEST_METHOD']}), php self: ({$_SERVER['PHP_SELF']}), query string: ({$_SERVER['QUERY_STRING']})";
	$clientInfo = "User Agent: {$_SERVER['HTTP_USER_AGENT']}\nIP: {$_SERVER['REMOTE_ADDR']}";
	$logMessage = "[{$timestamp}] - {$eventMessage}\n{$request_info}\n{$clientInfo}";
	try { 
		// Открываем файл для добавления (или создаем его, если он не существует)
		$fileHandle = fopen($logFile, 'a');
		if ($fileHandle === false) {
			throw new Exception("Не удалось открыть файл для записи лога: $logFile");
		}
		// Записываем сообщение в файл
    	fwrite($fileHandle, "$logMessage\r\n-------------\r\n");
    	fclose($fileHandle);

	} catch (Exception $e) {
		
	}
	return $eventMessage;
}


?>