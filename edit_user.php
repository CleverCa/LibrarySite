<?php
session_start();
require_once("setup2.php");

// Проверка авторизации и прав администратора
if (!isset($_SESSION['user_id'])) {
 header("Location: login.php");
 exit();
}

$errors = [];
$success = false;
$user_id = $_GET['user_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $user_id = $_POST['user_id'];
 $first_name = trim($_POST['first_name']);
 $last_name = trim($_POST['last_name']);
 $middle_name = trim($_POST['middle_name']);
 $phone = trim($_POST['phone']);
 $email = trim($_POST['email']);
 $login = trim($_POST['login']);

 // Валидация
 if (empty($first_name)) {
  $errors[] = "Имя обязательно для заполнения";
 }
 if (empty($last_name)) {
  $errors[] = "Фамилия обязательна для заполнения";
 }
 if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors[] = "Некорректный email";
 }

 // Проверка уникальности логина
 if ($login !== $user['login']) {
  $check_query = "SELECT ReaderID FROM readers WHERE login = ? AND ReaderID != ?";
  $stmt = mysqli_prepare($conn, $check_query);
  mysqli_stmt_bind_param($stmt, "si", $login, $user_id);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  if (mysqli_num_rows($result) > 0) {
   $errors[] = "Этот логин уже занят";
  }
 }

 if (empty($errors)) {
  // Обновление данных пользователя
  $update_query = "UPDATE readers SET
                            FirstName = ?,
                            LastName = ?,
                            MiddleName = ?,
                            Phone = ?,
                            Email = ?,
                            login = ?
                            WHERE ReaderID = ?";
  $stmt = mysqli_prepare($conn, $update_query);
  mysqli_stmt_bind_param($stmt, "ssssssi", $first_name, $last_name, $middle_name, $phone, $email, $login, $user_id);

  if (mysqli_stmt_execute($stmt)) {
   $success = true;
  } else {
   $errors[] = "Ошибка обновления: " . mysqli_error($conn);
  }
 }
}

// Получение данных пользователя для редактирования
if (!empty($user_id)) {
 $query = "SELECT * FROM readers WHERE ReaderID = ?";
 $stmt = mysqli_prepare($conn, $query);
 mysqli_stmt_bind_param($stmt, "i", $user_id);
 mysqli_stmt_execute($stmt);
 $result = mysqli_stmt_get_result($stmt);
 $user = mysqli_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
 <meta charset="UTF-8">
 <title>Редактирование пользователя</title>
 <style>
  body {
   font-family: 'Arial', sans-serif;
   max-width: 800px;
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
  <a href="user.php" class="btn btn-back">Пользователи</a>
  <a href="index.php" class="btn btn-back">На главную</a>
  <h1>Редактирование пользователя</h1>

  <?php if (!empty($errors)): ?>
   <div class="error">
    <?php foreach ($errors as $error): ?>
     <div><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>
   </div>
  <?php endif; ?>

  <?php if ($success): ?>
   <div class="success">
    Данные пользователя успешно обновлены!
   </div>
  <?php endif; ?>

  <form method="POST">
   <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id) ?>">
   <div class="form-group">
    <label for="first_name">Имя:</label>
    <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['FirstName'] ?? '') ?>"
     required>
   </div>

   <div class="form-group">
    <label for="last_name">Фамилия:</label>
    <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['LastName'] ?? '') ?>"
     required>
   </div>

   <div class="form-group">
    <label for="middle_name">Отчество:</label>
    <input type="text" id="middle_name" name="middle_name" value="<?= htmlspecialchars($user['MiddleName'] ?? '') ?>">
   </div>

   <div class="form-group">
    <label for="phone">Телефон:</label>
    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['Phone'] ?? '') ?>">
   </div>

   <div class="form-group">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['Email'] ?? '') ?>" required>
   </div>

   <div class="form-group">
    <label for="login">Логин:</label>
    <input type="text" id="login" name="login" value="<?= htmlspecialchars($user['login'] ?? '') ?>" required>
   </div>

   <button type="submit" class="btn btn-primary">Сохранить изменения</button>
  </form>
 </div>
</body>

</html>