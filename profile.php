<?php
session_start();
include("setup2.php");

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Получение данных пользователя
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM readers WHERE ReaderID = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Обработка формы
$errors = [];
$success = false;
$edit_mode = isset($_GET['edit']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = [
    'LastName' => trim($_POST['last_name']),
    'FirstName' => trim($_POST['first_name']),
    'Middlename' => trim($_POST['middle_name'] ?? ''),
    'Phone' => trim($_POST['phone']),
    'Email' => trim($_POST['email']),
    'login' => trim($_POST['login'])
  ];

  // Валидация
  if (empty($data['FirstName']))
    $errors[] = "Имя обязательно для заполнения";
  if (!filter_var($data['Email'], FILTER_VALIDATE_EMAIL))
    $errors[] = "Некорректный email";

  // Проверка уникальности логина
  if ($data['login'] !== $user['login']) {
    $check_query = "SELECT ReaderID FROM readers WHERE login = ? AND ReaderID != ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "si", $data['login'], $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0)
      $errors[] = "Этот логин уже занят";
  }

  if (empty($errors)) {
    $query = "UPDATE readers SET 
                 LastName = ?,
                 FirstName = ?,
                 Middlename = ?,
                 Phone = ?,
                 Email = ?,
                 login = ?
                 WHERE ReaderID = ?";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param(
      $stmt,
      "ssssssi",
      $data['LastName'],
      $data['FirstName'],
      $data['Middlename'],
      $data['Phone'],
      $data['Email'],
      $data['login'],
      $user_id
    );

    if (mysqli_stmt_execute($stmt)) {
      $_SESSION['user_login'] = $data['login'];
      header("Location: profile.php");
      exit();
    } else {
      $errors[] = "Ошибка обновления: " . mysqli_error($conn);
    }
  }
}
?>
<!DOCTYPE html>
<html lang='ru'>

<head>
  <meta charset='UTF-8'>
  <title>Личный кабинет</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    :root {
      --primary: #4CAF50;
      --primary-dark: #388E3C;
      --accent: #8BC34A;
      --background: #F1F8E9;
      --card-bg: #FFF;
    }

    body {
      font-family: 'Roboto', sans-serif;
      margin: 0;
      padding: 20px;
      background: var(--background);
    }

    .profile {
      max-width: 800px;
      margin: 20px auto;
      padding: 30px;
      background: var(--card-bg);
      border-radius: 15px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    }

    h1 {
      color: var(--primary-dark);
      border-bottom: 3px solid var(--primary);
      padding-bottom: 15px;
      margin-bottom: 25px;
    }

    .profile-info p {
      background: #F8FFEE;
      padding: 15px;
      border-left: 4px solid var(--accent);
      margin: 15px 0;
      border-radius: 8px;
    }

    .edit-form {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      padding: 25px;
      background: #F8FFEE;
      border-radius: 12px;
      margin-top: 25px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      color: var(--primary-dark);
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
      border-color: var(--accent);
      box-shadow: 0 0 10px rgba(139, 195, 74, 0.2);
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
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: white;
      box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
    }

    .btn-secondary {
      background: #E8F5E9;
      color: var(--primary-dark);
      border: 2px solid var(--primary);
    }

    .btn-secondary:hover {
      background: var(--primary);
      color: white;
    }

    .btn-logout {
      background: #FFEBEE;
      color: #D32F2F;
      border: 2px solid #D32F2F;
    }

    .button-group {
      display: flex;
      gap: 15px;
      margin-top: 25px;
      flex-wrap: wrap;
    }

    .success-message {
      background: #E8F5E9;
      color: var(--primary-dark);
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      border-left: 4px solid var(--primary);
    }

    .error {
      background: #FFEBEE;
      color: #D32F2F;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      border-left: 4px solid #D32F2F;
    }

    .edit-mode .profile-info {
      display: none;
    }

    .edit-mode .edit-form {
      display: grid;
    }

    .edit-form {
      display: none;
    }

    @media (max-width: 768px) {
      .profile {
        padding: 20px;
      }

      .edit-form {
        grid-template-columns: 1fr;
      }

      .btn {
        width: 100%;
      }
    }
  </style>
</head>

<body class="<?= $edit_mode ? 'edit-mode' : '' ?>">
  <div class="profile">
    <?php if (!empty($errors)): ?>
      <div class="error">
        <?php foreach ($errors as $error): ?>
          <div><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="profile-info">
      <h1>Логин: <?= htmlspecialchars($user['login']) ?></h1>
      <p>Имя: <?= htmlspecialchars($user['FirstName']) ?></p>
      <p>Фамилия: <?= htmlspecialchars($user['LastName']) ?></p>
      <p>Отчество: <?= $user['MiddleName'] ? htmlspecialchars($user['MiddleName']) : '—' ?></p>
      <p>Телефон: <?= htmlspecialchars($user['Phone']) ?></p>
      <p>Email: <?= htmlspecialchars($user['Email']) ?></p>

      <div class="button-group">
        <a href="?edit=1" class="btn btn-primary"><i class="fas fa-edit"></i> Редактировать</a>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> На главную</a>
        <a href="history.php" class="btn btn-secondary"><i class="fas fa-history"></i> История бронирования</a>
        <a href="logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Выйти</a>
      </div>
    </div>

    <form method="POST" class="edit-form">
      <div class="form-group">
        <label>Логин:</label>
        <input type="text" name="login" value="<?= htmlspecialchars($user['login']) ?>" required>
      </div>

      <div class="form-group">
        <label>Имя:</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($user['FirstName']) ?>">
      </div>

      <div class="form-group">
        <label>Фамилия:</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($user['LastName']) ?>">
      </div>

      <div class="form-group">
        <label>Отчество:</label>
        <input type="text" name="middle_name" value="<?= htmlspecialchars($user['MiddleName']) ?>">
      </div>

      <div class="form-group">
        <label>Телефон:</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($user['Phone']) ?>">
      </div>

      <div class="form-group">
        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['Email']) ?>" required>
      </div>

      <div class="button-group">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Сохранить</button>
        <a href="profile.php" class="btn btn-secondary"><i class="fas fa-times"></i> Отмена</a>
      </div>
    </form>
  </div>
</body>

</html>