<?php
session_start();
require_once("setup2.php");

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $email = trim($_POST['email']);

 // Проверка, существует ли пользователь с таким email
 $query = "SELECT ReaderID, login FROM readers WHERE Email = ?";
 $stmt = mysqli_prepare($conn, $query);
 mysqli_stmt_bind_param($stmt, "s", $email);
 mysqli_stmt_execute($stmt);
 $result = mysqli_stmt_get_result($stmt);

 if (mysqli_num_rows($result) > 0) {
  $user = mysqli_fetch_assoc($result);
  $reader_id = $user['ReaderID'];
  $login = $user['login'];

  // Генерация токена для восстановления пароля
  $token = bin2hex(random_bytes(16));
  $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

  // Сохранение токена в базе данных
  $insert_query = "INSERT INTO password_resets (ReaderID, token, expires_at) VALUES (?, ?, ?)";
  $insert_stmt = mysqli_prepare($conn, $insert_query);
  mysqli_stmt_bind_param($insert_stmt, "iss", $reader_id, $token, $expiry);
  mysqli_stmt_execute($insert_stmt);

  // Отправка email с ссылкой для восстановления пароля
  $reset_link = "http://yourdomain.com/reset_password.php?token=$token";
  $subject = "Восстановление пароля";
  $message = "Здравствуйте, $login!\n\n";
  $message .= "Для восстановления пароля перейдите по ссылке:\n";
  $message .= "$reset_link\n\n";
  $message .= "Ссылка действительна в течение одного часа.\n";
  $headers = "From: no-reply@yourdomain.com";

  if (mail($email, $subject, $message, $headers)) {
   $success = true;
  } else {
   $errors[] = "Ошибка отправки email.";
  }
 } else {
  $errors[] = "Пользователь с таким email не найден.";
 }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
 <meta charset="UTF-8">
 <title>Восстановление пароля</title>
 <style>
  body {
   font-family: 'Arial', sans-serif;
   max-width: 600px;
   margin: 20px auto;
   padding: 20px;
   background-color: #f5f5f5;
  }

  .container {
   background: white;
   padding: 30px;
   border-radius: 10px;
   box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
  }

  h1 {
   color: #2c3e50;
   border-bottom: 2px solid #4CAF50;
   padding-bottom: 10px;
  }

  .form-group {
   margin-bottom: 15px;
  }

  label {
   display: block;
   margin-bottom: 8px;
   color: #333;
   font-weight: 600;
  }

  input {
   width: 100%;
   padding: 12px;
   border: 2px solid #E0F2E5;
   border-radius: 8px;
   transition: all 0.3s ease;
  }

  input:focus {
   border-color: #4CAF50;
   box-shadow: 0 0 10px rgba(76, 175, 80, 0.2);
  }

  .btn {
   padding: 12px 25px;
   border: none;
   border-radius: 30px;
   font-size: 1em;
   cursor: pointer;
   transition: all 0.3s ease;
   display: inline-flex;
   align-items: center;
   gap: 8px;
  }

  .btn-primary {
   background: linear-gradient(135deg, #4CAF50, #8BC34A);
   color: white;
   box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
  }

  .btn-primary:hover {
   transform: translateY(-2px);
   box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
  }

  .btn-back {
   background: #4CAF50;
   color: white;
   border: none;
   border-radius: 30px;
   font-size: 1em;
   cursor: pointer;
   transition: all 0.3s ease;
   display: inline-flex;
   align-items: center;
   gap: 8px;
   margin-bottom: 20px;
  }

  .btn-back:hover {
   background: #45a049;
  }

  .error {
   color: #dc3545;
   padding: 10px;
   border: 1px solid #f5c6cb;
   border-radius: 5px;
   margin: 20px 0;
   background: #f8d7da;
  }

  .success {
   color: #28a745;
   padding: 10px;
   border: 1px solid #c3e6cb;
   border-radius: 5px;
   margin: 20px 0;
   background: #d4edda;
  }
 </style>
</head>

<body>
 <div class="container">
  <button class="btn btn-back" onclick="window.history.back();">Назад</button>
  <h1>Восстановление пароля</h1>

  <?php if (!empty($errors)): ?>
   <div class="error">
    <?php foreach ($errors as $error): ?>
     <div><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>
   </div>
  <?php endif; ?>

  <?php if ($success): ?>
   <div class="success">
    Ссылка для восстановления пароля отправлена на ваш email.
   </div>
  <?php endif; ?>

  <form method="POST">
   <div class="form-group">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
   </div>
   <button type="submit" class="btn btn-primary">Отправить</button>
  </form>
 </div>
</body>

</html>