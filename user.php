<?php
session_start();
require_once("setup2.php");

// Проверка авторизации и прав администратора
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$success = false;
$errors = [];

if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['user_id'])) {
  $user_id = $_POST['user_id'];

  // Начало транзакции
  mysqli_begin_transaction($conn);

  try {
    // Удаление записей из таблицы Borrowings
    $delete_borrowings = "DELETE FROM Borrowings WHERE ReaderID = ?";
    $stmt = mysqli_prepare($conn, $delete_borrowings);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    // Удаление пользователя из таблицы Readers
    $delete_user = "DELETE FROM Readers WHERE ReaderID = ?";
    $stmt = mysqli_prepare($conn, $delete_user);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    // Фиксация транзакции
    mysqli_commit($conn);
    $success = true;
  } catch (Exception $e) {
    // Откат транзакции в случае ошибки
    mysqli_rollback($conn);
    $errors[] = "Ошибка удаления пользователя: " . $e->getMessage();
  }

  // Возвращаем JSON-ответ
  header('Content-Type: application/json');
  echo json_encode(['success' => $success, 'errors' => $errors]);
  exit();
}

// Получение списка пользователей
$query = "SELECT ReaderID, FirstName, LastName, MiddleName, Phone, Email, login FROM Readers";
$result = mysqli_query($conn, $query);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <title>Список пользователей</title>
  <style>
    body {
      font-family: 'Arial', sans-serif;
      max-width: 1200px;
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

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    th,
    td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }

    th {
      background-color: #4CAF50;
      color: white;
    }

    tr:nth-child(even) {
      background-color: #f2f2f2;
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

    .btn-danger {
      background: linear-gradient(135deg, #e74c3c, #c0392b);
      color: white;
      box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
    }

    .btn-danger:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
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
  <script>
    function deleteUser(userId) {
      if (confirm("Вы уверены, что хотите удалить этого пользователя?")) {
        fetch('user.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'delete',
            user_id: userId
          })
        })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Удаляем строку из таблицы без перезагрузки страницы
              document.getElementById(`user-row-${userId}`).remove();
            } else {
              alert(data.errors.join("\n"));
            }
          })
          .catch(error => {
            console.error('Error:', error);
          });
      }
    }
  </script>
</head>

<body>
  <div class="container">
    <a href="index.php" class="btn btn-back">На главную</a>
    <h1>Список пользователей</h1>

    <a href="new_user.php" class="btn btn-primary">Добавить нового пользователя</a>

    <?php if (!empty($errors)): ?>
      <div class="error">
        <?php foreach ($errors as $error): ?>
          <div><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($users)): ?>
      <table>
        <thead>
          <tr>
            <th>Имя</th>
            <th>Фамилия</th>
            <th>Отчество</th>
            <th>Телефон</th>
            <th>Email</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr id="user-row-<?= $user['ReaderID'] ?>">
              <td><?= htmlspecialchars($user['FirstName']) ?></td>
              <td><?= htmlspecialchars($user['LastName']) ?></td>
              <td><?= htmlspecialchars($user['MiddleName'] ?? '—') ?></td>
              <td><?= htmlspecialchars($user['Phone']) ?></td>
              <td><?= htmlspecialchars($user['Email']) ?></td>
              <td>
                <a href="edit_user.php?user_id=<?= urlencode($user['ReaderID']) ?>"
                  class="btn btn-primary">Редактировать</a>
                <button onclick="deleteUser(<?= $user['ReaderID'] ?>)" class="btn btn-danger">Удалить</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>Нет зарегистрированных пользователей.</p>
    <?php endif; ?>
  </div>
</body>

</html>