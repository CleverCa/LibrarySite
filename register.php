<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ru">

<head>
 <meta charset="UTF-8">
 <title>Регистрация</title>
 <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
 <style>
  /* Стили остаются без изменений */
  body {
   background-color: rgb(147, 243, 118);
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
  }

  label {
   display: block;
   margin-top: 15px;
  }

  input[type="text"],
  input[type="password"] {
   width: 100%;
   padding: 10px;
   margin-top: 5px;
   border: 1px solid #ccc;
   border-radius: 6px;
  }

  .submit-btn,
  .back-btn {
   margin-top: 25px;
   width: 100%;
   padding: 10px;
   background-color: rgb(76, 175, 80);
   color: white;
   border: none;
   border-radius: 6px;
   font-size: 16px;
  }

  .submit-btn:hover,
  .back-btn:hover {
   background-color: rgb(76, 175, 80);
  }

  .error {
   color: red;
   margin-top: 10px;
   text-align: center;
  }

  .success {
   color: green;
   margin-top: 10px;
   text-align: center;
  }

  .captcha-container {
   display: flex;
   align-items: center;
   margin-top: 15px;
  }

  .captcha-img {
   margin-right: 15px;
   max-width: 100px;
   max-height: 50px;
  }

  .button-container {
   display: flex;
   justify-content: space-between;
  }
 </style>
</head>

<body>
 <div class="container">
  <h2>Регистрация</h2>
  <?php
  include("setup2.php");

  // Инициализация капчи
  if ($_SERVER["REQUEST_METHOD"] !== "POST") {
   $captcha_query = "SELECT * FROM captchas ORDER BY RAND() LIMIT 1";
   $captcha_result = mysqli_query($conn, $captcha_query);
   $captcha = mysqli_fetch_assoc($captcha_result);
   $_SESSION["captcha_id"] = $captcha["capid"];
   $_SESSION["captcha_text"] = $captcha["text"];
   $_SESSION["captcha_pic"] = $captcha["pic"];
  }

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
   $player_name = trim($_POST["player_name"]);
   $login = trim($_POST["login"]);
   $password = $_POST["password"];
   $confirm_password = $_POST["confirm_password"];
   $user_captcha = trim($_POST["captcha"]);

   $errors = [];

   // Валидация данных
   if (empty($player_name) || strlen($player_name) > 30) {
    $errors[] = "Имя должно быть от 1 до 30 символов.";
   }
   if (empty($login) || strlen($login) > 70) {
    $errors[] = "Логин должен быть от 1 до 70 символов.";
   }
   if (!preg_match('/^[a-zA-Z0-9]{1,32}$/', $password)) {
    $errors[] = "Пароль должен содержать только латинские буквы и цифры, максимум 32 символа.";
   }
   if ($password !== $confirm_password) {
    $errors[] = "Пароли не совпадают.";
   }

   // Проверка капчи
   if (empty($user_captcha) || strtolower($user_captcha) !== strtolower($_SESSION["captcha_text"])) {
    $errors[] = "Неверно введена капча.";
   }

   // Если ошибок нет
   if (empty($errors)) {
    $player_name = mysqli_real_escape_string($conn, $player_name);
    $login = mysqli_real_escape_string($conn, $login);
    $password = mysqli_real_escape_string($conn, $password);

    // Проверка уникальности логина
    $check_query = "SELECT ReaderID FROM readers WHERE login='$login'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
     echo "<div class='error'>Такой логин уже существует.</div>";
    } else {
     $insert_query = "INSERT INTO readers 
                                   (ReaderID, LastName, FirstName, Middlename, Phone, Email, RegistrationDate, Status, login, password)
                                   VALUES 
                                   (NULL, 'Иванов','$player_name','Иванович', '+7', '@gmail.com', NOW(), 'Active','$login', '$password')";

     if (mysqli_query($conn, $insert_query)) {
      $_SESSION["user_login"] = $login;
      header("Location: profile.php");
      exit();
     } else {
      echo "<div class='error'>Ошибка при регистрации: " . mysqli_error($conn) . "</div>";
     }
    }
   } else {
    foreach ($errors as $err) {
     echo "<div class='error'>$err</div>";
    }
   }
  }
  ?>

  <form action="register.php" method="post">
   <label for="player_name">Имя читателя (макс 30 символов):</label>
   <input type="text" name="player_name" id="player_name" maxlength="30" required>

   <label for="login">Логин (макс 70 символов):</label>
   <input type="text" name="login" id="login" maxlength="70" required>

   <label for="password">Пароль (макс 32 символа):</label>
   <input type="password" name="password" id="password" maxlength="32" pattern="[a-zA-Z0-9]{1,32}" required>

   <label for="confirm_password">Повторите пароль:</label>
   <input type="password" name="confirm_password" id="confirm_password" maxlength="32" pattern="[a-zA-Z0-9]{1,32}"
    required>

   <label for="captcha">Введите текст с картинки:</label>
   <input type="text" name="captcha" id="captcha" required>
   <?php
   if (isset($_SESSION["captcha_pic"])) {
    $captcha_image = "capcha/" . htmlspecialchars($_SESSION["captcha_pic"]);
    echo "<div class='captcha-container'>";
    echo "<img class='captcha-img' src='$captcha_image' alt='Капча'>";
   }
   ?>
   <div class="button-container">
    <input type="submit" class="submit-btn" value="Зарегистрироваться">
    <button type="button" class="back-btn" onclick="window.history.back();">Назад</button>
   </div>
  </form>
 </div>
</body>

</html>