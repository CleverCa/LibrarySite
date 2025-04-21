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
$isbn = $_GET['isbn'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $isbn = $_POST['isbn'];
  $title = trim($_POST['title']);
  $description = trim($_POST['description']);
  $publisher_id = trim($_POST['publisher_id']);
  $year_published = trim($_POST['year_published']);
  $authors = isset($_POST['authors']) ? $_POST['authors'] : [];

  // Валидация
  if (empty($title)) {
    $errors[] = "Название книги обязательно для заполнения";
  }
  if (empty($publisher_id)) {
    $errors[] = "Издатель обязателен для выбора";
  }
  if (empty($year_published)) {
    $errors[] = "Год издания обязателен для заполнения";
  }
  if (empty($authors)) {
    $errors[] = "Должен быть выбран хотя бы один автор";
  }

  if (empty($errors)) {
    // Начало транзакции
    $conn->begin_transaction();

    try {
      // Обновление данных о книге
      $update_book_query = "UPDATE Books SET Title = ?, Description = ?, PublisherID = ?, YearPublished = ? WHERE ISBN = ?";
      $update_book_stmt = mysqli_prepare($conn, $update_book_query);
      mysqli_stmt_bind_param($update_book_stmt, "ssiis", $title, $description, $publisher_id, $year_published, $isbn);
      mysqli_stmt_execute($update_book_stmt);

      // Удаление старых связей книги с авторами
      $delete_author_query = "DELETE FROM BookAuthors WHERE ISBN = ?";
      $delete_author_stmt = mysqli_prepare($conn, $delete_author_query);
      mysqli_stmt_bind_param($delete_author_stmt, "s", $isbn);
      mysqli_stmt_execute($delete_author_stmt);

      // Вставка новых данных о связях книги с авторами
      $insert_author_query = "INSERT INTO BookAuthors (ISBN, AuthorID) VALUES (?, ?)";
      $insert_author_stmt = mysqli_prepare($conn, $insert_author_query);
      foreach ($authors as $author_id) {
        mysqli_stmt_bind_param($insert_author_stmt, "si", $isbn, $author_id);
        mysqli_stmt_execute($insert_author_stmt);
      }

      // Фиксация транзакции
      $conn->commit();
      $success = true;
    } catch (Exception $e) {
      $conn->rollback();
      $errors[] = "Ошибка при обновлении книги: " . $e->getMessage();
    }
  }
}

// Получение данных книги для редактирования
if (!empty($isbn)) {
  $book_query = "SELECT * FROM Books WHERE ISBN = ?";
  $book_stmt = mysqli_prepare($conn, $book_query);
  mysqli_stmt_bind_param($book_stmt, "s", $isbn);
  mysqli_stmt_execute($book_stmt);
  $book_result = mysqli_stmt_get_result($book_stmt);
  $book = mysqli_fetch_assoc($book_result);

  // Получение списка авторов книги
  $authors_query = "SELECT AuthorID FROM BookAuthors WHERE ISBN = ?";
  $authors_stmt = mysqli_prepare($conn, $authors_query);
  mysqli_stmt_bind_param($authors_stmt, "s", $isbn);
  mysqli_stmt_execute($authors_stmt);
  $authors_result = mysqli_stmt_get_result($authors_stmt);
  $book_authors = mysqli_fetch_all($authors_result, MYSQLI_ASSOC);
}

// Получение списка издателей
$publishers_query = "SELECT PublisherID, Name FROM Publishers ORDER BY Name";
$publishers_result = mysqli_query($conn, $publishers_query);
$publishers = mysqli_fetch_all($publishers_result, MYSQLI_ASSOC);

// Получение списка авторов
$all_authors_query = "SELECT AuthorID, LastName, FirstName FROM Authors ORDER BY LastName, FirstName";
$all_authors_result = mysqli_query($conn, $all_authors_query);
$all_authors = mysqli_fetch_all($all_authors_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <title>Редактирование книги</title>
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

    input,
    select,
    textarea {
      width: 100%;
      padding: 12px;
      border: 2px solid #E0F2E5;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    input:focus,
    select:focus,
    textarea:focus {
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

    .button-group {
      display: flex;
      gap: 15px;
      margin-top: 25px;
    }
  </style>
</head>

<body>
  <div class="container">
    <a href="admin_books.php" class="btn btn-back">Каталог</a>
    <a href="index.php" class="btn btn-back">На главную</a>
    <h1>Редактирование книги</h1>

    <?php if (!empty($errors)): ?>
      <div class="error">
        <?php foreach ($errors as $error): ?>
          <div><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success">
        Книга успешно обновлена!
      </div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="isbn" value="<?= htmlspecialchars($isbn) ?>">
      <div class="form-group">
        <label for="title">Название книги:</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($book['Title'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label for="description">Описание:</label>
        <textarea id="description" name="description"
          rows="4"><?= htmlspecialchars($book['Description'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label for="publisher_id">Издатель:</label>
        <select id="publisher_id" name="publisher_id" required>
          <option value="">Выберите издателя</option>
          <?php foreach ($publishers as $publisher): ?>
            <option value="<?= $publisher['PublisherID'] ?>" <?= $book['PublisherID'] == $publisher['PublisherID'] ? 'selected' : '' ?>><?= htmlspecialchars($publisher['Name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="year_published">Год издания:</label>
        <input type="number" id="year_published" name="year_published"
          value="<?= htmlspecialchars($book['YearPublished'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label for="authors">Авторы:</label>
        <select id="authors" name="authors[]" multiple required>
          <?php foreach ($all_authors as $author): ?>
            <option value="<?= $author['AuthorID'] ?>" <?= in_array(['AuthorID' => $author['AuthorID']], $book_authors) ? 'selected' : '' ?>><?= htmlspecialchars($author['LastName'] . ' ' . $author['FirstName']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="button-group">
        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
      </div>
    </form>
  </div>
</body>

</html>