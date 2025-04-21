<?php
// Функция подключения к базе данных

$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'library';

// Создание соединения
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Проверяем соединение
if (!$conn) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

// Устанавливаем кодировку
mysqli_set_charset($conn, "utf8");

?>