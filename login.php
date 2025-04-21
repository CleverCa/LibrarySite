<?php
session_start();
include("setup2.php");

// Включение отладки (убрать в продакшене)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверка авторизации
if (isset($_SESSION['user_id'])) {
 header("Location: profile.php");
 exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
 $login = trim($_POST["login"]);
 $password = $_POST["password"];

 $errors = [];

 // Валидация
 if (empty($login)) {
  $errors[] = "Логин обязателен для заполнения";
 }
 if (empty($password)) {
  $errors[] = "Пароль обязателен для заполнения";
 }

 if (empty($errors)) {
  $login = mysqli_real_escape_string($conn, $login);
  $password = mysqli_real_escape_string($conn, $password);

  // Поиск пользователя
  $query = "SELECT ReaderID, FirstName, password 
                FROM readers 
                WHERE login = '$login'";
  $result = mysqli_query($conn, $query);

  if (!$result) {
   die("Ошибка запроса: " . mysqli_error($conn));
  }

  if (mysqli_num_rows($result) == 1) {
   $user = mysqli_fetch_assoc($result);

   // Проверка пароля (без хеширования)
   if ($password === $user['password']) {
    // Установка сессии
    $_SESSION['user_id'] = $user['ReaderID'];
    $_SESSION['username'] = $user['FirstName'];

    // Редирект
    header("Location: profile.php");
    exit();
   } else {
    $errors[] = "Неверный пароль";
   }
  } else {
   $errors[] = "Пользователь с таким логином не найден";
  }
 }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
 <meta charset="UTF-8">
 <title>Вход — Библиотека</title>
 <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
 <style>
  body {
   background-color: #4CAF50;
   font-family: Arial, sans-serif;
   margin: 0;
   padding: 0;
  }

  .container {
   max-width: 500px;
   margin: 50px auto;
   padding: 30px;
   background-color: white;
   border-radius: 12px;
   box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  }

  h2 {
   text-align: center;
   color: #333;
  }

  .error {
   color: #ff0000;
   padding: 10px;
   margin: 10px 0;
   border-radius: 5px;
   background-color: #ffe6e6;
   text-align: center;
  }

  input[type="text"],
  input[type="password"] {
   width: 100%;
   padding: 12px;
   margin: 8px 0;
   border: 1px solid #ddd;
   border-radius: 4px;
   box-sizing: border-box;
  }

  .submit-btn {
   width: 100%;
   padding: 12px;
   background-color: #4CAF50;
   color: white;
   border: none;
   border-radius: 4px;
   cursor: pointer;
   font-size: 16px;
   margin-top: 15px;
  }

  .submit-btn:hover {
   background-color: #45a049;
  }
 </style>
</head>

<body>
 <div class="container">
  <h2>Вход в систему</h2>

  <?php
  if (!empty($errors)) {
   foreach ($errors as $err) {
    echo '<div class="error">' . htmlspecialchars($err) . '</div>';
   }
  }
  ?>

  <form method="POST" action="login.php">
   <div>
    <label>Логин:</label>
    <input type="text" name="login" required>
   </div>

   <div>
    <label>Пароль:</label>
    <input type="password" name="password" required>
   </div>

   <button type="submit" class="submit-btn">Войти</button>
  </form>
 </div>
</body>

</html>