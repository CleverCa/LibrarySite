<?php
$host = 'localhost';
$db = 'library';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Подключение к базе данных
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$opt = [
 PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
 PDO::ATTR_EMULATE_PREPARES => false,
];
$pdo = new PDO($dsn, $user, $pass, $opt);

// Создание временной таблицы
function createTempTable($pdo)
{
 $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS TempBooks (
        ISBN VARCHAR(13) PRIMARY KEY,
        Title VARCHAR(255) NOT NULL,
        YearPublished INT,
        Pages INT,
        PublisherID INT
    )";
 $pdo->exec($sql);
}

// Инициализация переменных
$message = '';
$error = '';
$tempBooks = [];

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 try {
  createTempTable($pdo);

  if (isset($_POST['add_book'])) {
   // Добавление книги
   $stmt = $pdo->prepare("INSERT INTO TempBooks 
                (ISBN, Title, YearPublished, Pages, PublisherID)
                VALUES (?, ?, ?, ?, ?)");

   $stmt->execute([
    $_POST['isbn'],
    $_POST['title'],
    $_POST['year'],
    $_POST['pages'],
    $_POST['publisher_id']
   ]);
   $message = "Книга успешно добавлена во временную таблицу!";
  } elseif (isset($_POST['save_to_main'])) {
   // Перенос в основную таблицу
   $stmt = $pdo->prepare("INSERT INTO Books 
                SELECT * FROM TempBooks");
   $stmt->execute();
   $message = "Данные успешно перенесены в основную таблицу!";
  } elseif (isset($_POST['delete_book'])) {
   // Удаление книги
   $stmt = $pdo->prepare("DELETE FROM TempBooks WHERE ISBN = ?");
   $stmt->execute([$_POST['delete_isbn']]);
   $message = "Книга успешно удалена из временной таблицы!";
  }
 } catch (PDOException $e) {
  $error = "Ошибка: " . $e->getMessage();
 }
}

// Получение книг из временной таблицы
try {
 createTempTable($pdo);
 $tempBooks = $pdo->query("SELECT * FROM TempBooks")->fetchAll();
} catch (PDOException $e) {
 $error = "Ошибка получения данных: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Управление временными книгами</title>
    <style>
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .delete-btn { 
            background: #ff4444; 
            color: white; 
            border: none; 
            padding: 5px 10px; 
            cursor: pointer;
        }
        .delete-btn:hover { background: #cc0000; }
        .message { 
            padding: 10px; 
            margin: 10px 0; 
            border-radius: 4px; 
        }
        .success { background: #dff0d8; color: #3c763d; }
        .error { background: #f2dede; color: #a94442; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Добавление временной книги</h2>
        
        <?php if ($error): ?>
             <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
             <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>ISBN:</label>
                <input type="text" name="isbn" required maxlength="13">
            </div>
            
            <div class="form-group">
                <label>Название:</label>
                <input type="text" name="title" required>
            </div>
            
            <div class="form-group">
                <label>Год издания:</label>
                <input type="number" name="year">
            </div>
            
            <div class="form-group">
                <label>Страницы:</label>
                <input type="number" name="pages">
            </div>
            
            <div class="form-group">
                <label>ID издателя:</label>
                <input type="number" name="publisher_id">
            </div>
            
            <button type="submit" name="add_book">Добавить во временную таблицу</button>
            <button type="submit" name="save_to_main">Сохранить в основную таблицу</button>
        </form>

        <h3>Книги во временной таблице</h3>
        <?php if (!empty($tempBooks)): ?>
             <table>
                 <tr>
                     <th>ISBN</th>
                     <th>Название</th>
                     <th>Год</th>
                     <th>Страницы</th>
                     <th>Издатель</th>
                     <th>Действия</th>
                 </tr>
                 <?php foreach ($tempBooks as $book): ?>
                      <tr>
                          <td><?= htmlspecialchars($book['ISBN']) ?></td>
                          <td><?= htmlspecialchars($book['Title']) ?></td>
                          <td><?= htmlspecialchars($book['YearPublished']) ?></td>
                          <td><?= htmlspecialchars($book['Pages']) ?></td>
                          <td><?= htmlspecialchars($book['PublisherID']) ?></td>
                          <td>
                              <form method="post" 
                                    onsubmit="return confirm('Удалить эту книгу?')">
                                  <input type="hidden" name="delete_isbn" 
                                         value="<?= htmlspecialchars($book['ISBN']) ?>">
                                  <button type="submit" name="delete_book" 
                                          class="delete-btn">
                                      Удалить
                                  </button>
                              </form>
                          </td>
                      </tr>
                 <?php endforeach; ?>
             </table>
        <?php else: ?>
             <p>Нет книг во временной таблице</p>
        <?php endif; ?>
    </div>
</body>
</html>