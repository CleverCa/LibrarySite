<?php
session_start();
require_once("setup2.php");

if (!isset($_SESSION['user_id'])) {
 header("Location: login.php");
 exit();
}

// Получаем данные пользователя
$reader_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Обработка формы бронирования
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['copy_ids'])) {
 try {
  $conn->begin_transaction();

  $copy_ids = $_POST['copy_ids'];
  $placeholders = implode(',', array_fill(0, count($copy_ids), '?'));

  $stmt = $conn->prepare("
            SELECT CopyID
            FROM Copies
            WHERE CopyID IN ($placeholders)
            AND Status = 'Available'
            FOR UPDATE
        ");
  $stmt->bind_param(str_repeat('i', count($copy_ids)), ...$copy_ids);
  $stmt->execute();
  $available_copies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

  if (count($available_copies) !== count($copy_ids)) {
   throw new Exception("Некоторые книги уже заняты");
  }

  $insert_stmt = $conn->prepare("
            INSERT INTO Borrowings (CopyID, ReaderID, BorrowDate, DueDate)
            VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY))
        ");

  $update_stmt = $conn->prepare("
            UPDATE Copies SET Status = 'Borrowed' WHERE CopyID = ?
        ");

  foreach ($copy_ids as $copy_id) {
   $insert_stmt->bind_param("ii", $copy_id, $reader_id);
   $insert_stmt->execute();

   $update_stmt->bind_param("i", $copy_id);
   $update_stmt->execute();
  }

  $conn->commit();
  $success = true;
 } catch (Exception $e) {
  $conn->rollback();
  $errors[] = $e->getMessage();
 }
}

// Получаем параметры поиска
$search_title = $_GET['title'] ?? '';
$search_author = $_GET['author'] ?? '';
$search_genre = $_GET['genre'] ?? '';

// Получаем списки для фильтров
$genres_query = "SELECT * FROM Genres ORDER BY Name";
$genres_result = mysqli_query($conn, $genres_query);
$all_genres = mysqli_fetch_all($genres_result, MYSQLI_ASSOC);

$authors_query = "SELECT AuthorID, LastName, FirstName FROM Authors ORDER BY LastName, FirstName";
$authors_result = mysqli_query($conn, $authors_query);
$all_authors = mysqli_fetch_all($authors_result, MYSQLI_ASSOC);

// Формируем SQL-запрос с фильтрами
$query = "SELECT
        c.CopyID,
        b.Title,
        b.Description,
        GROUP_CONCAT(DISTINCT CONCAT(a.LastName, ' ', a.FirstName) SEPARATOR ', ') AS Authors,
        GROUP_CONCAT(DISTINCT g.Name SEPARATOR ', ') AS Genres,
        p.Name AS Publisher,
        b.YearPublished
    FROM Copies c
    JOIN Books b ON c.ISBN = b.ISBN
    LEFT JOIN BookAuthors ba ON b.ISBN = ba.ISBN
    LEFT JOIN Authors a ON ba.AuthorID = a.AuthorID
    LEFT JOIN BookGenres bg ON b.ISBN = bg.ISBN
    LEFT JOIN Genres g ON bg.GenreID = g.GenreID
    LEFT JOIN Publishers p ON b.PublisherID = p.PublisherID
    WHERE c.Status = 'Available'
";

$conditions = [];
$params = [];
$types = '';

if (!empty($search_title)) {
 $conditions[] = "b.Title LIKE ?";
 $params[] = "%$search_title%";
 $types .= 's';
}

if (!empty($search_author)) {
 $conditions[] = "a.AuthorID = ?";
 $params[] = $search_author;
 $types .= 'i';
}

if (!empty($search_genre)) {
 $conditions[] = "g.GenreID = ?";
 $params[] = $search_genre;
 $types .= 'i';
}

if (!empty($conditions)) {
 $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " GROUP BY c.CopyID ORDER BY b.Title";

// Выполняем подготовленный запрос
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
 die("Ошибка подготовки запроса: " . mysqli_error($conn));
}

if (!empty($params)) {
 mysqli_stmt_bind_param($stmt, $types, ...$params);
}

if (!mysqli_stmt_execute($stmt)) {
 die("Ошибка выполнения запроса: " . mysqli_stmt_error($stmt));
}

$result = mysqli_stmt_get_result($stmt);
$available_books = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
 <meta charset="UTF-8">
 <title>Бронирование книг</title>
 <style>
  body {
   font-family: Arial, sans-serif;
   max-width: 1200px;
   margin: 20px auto;
   padding: 20px;
  }

  .back-btn {
   display: inline-block;
   padding: 10px 20px;
   background: #4CAF50;
   color: white;
   text-decoration: none;
   border-radius: 4px;
   margin-bottom: 20px;
  }

  .search-form {
   margin-bottom: 30px;
   padding: 20px;
   background: #f8f8f8;
   border-radius: 8px;
  }

  .search-row {
   display: grid;
   grid-template-columns: repeat(3, 1fr);
   gap: 15px;
   margin-bottom: 15px;
  }

  .search-control {
   padding: 8px;
   border: 1px solid #ddd;
   border-radius: 4px;
   width: 100%;
  }

  .book-list {
   display: grid;
   grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
   gap: 20px;
   margin-top: 20px;
  }

  .book-card {
   border: 1px solid #ddd;
   padding: 15px;
   border-radius: 8px;
   background: #f9f9f9;
  }

  .success {
   color: green;
  }

  .error {
   color: red;
  }

  button {
   padding: 10px 20px;
   background: #4CAF50;
   color: white;
   border: none;
   border-radius: 4px;
   cursor: pointer;
  }

  .btn-secondary {
   background: #6c757d;
   color: white;
   border: 1px solid #5a6268;
   padding: 10px 20px;
   border-radius: 4px;
   text-decoration: none;
   display: inline-flex;
   align-items: center;
   gap: 5px;
   transition: all 0.3s ease;
  }

  .btn-secondary:hover {
   background: #5a6268;
   transform: translateY(-1px);
   box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  .btn-secondary i {
   font-size: 0.9em;
  }

  .book-description {
   color: #666;
   font-size: 0.9em;
   margin-top: 8px;
   line-height: 1.4;
  }
 </style>
</head>

<body>
 <a href="index.php" class="back-btn">На главную</a>
 <h1>Бронирование книг</h1>

 <?php if ($success): ?>
  <div class="success">Книги успешно забронированы!</div>
 <?php endif; ?>

 <?php if (!empty($errors)): ?>
  <div class="error">
   <?php foreach ($errors as $error): ?>
    <div><?= htmlspecialchars($error) ?></div>
   <?php endforeach; ?>
  </div>
 <?php endif; ?>

 <form method="get" class="search-form">
  <div class="search-row">
   <input type="text" name="title" class="search-control" placeholder="Поиск по названию"
    value="<?= htmlspecialchars($search_title) ?>">

   <select name="author" class="search-control">
    <option value="">Все авторы</option>
    <?php foreach ($all_authors as $author): ?>
     <option value="<?= $author['AuthorID'] ?>" <?= $author['AuthorID'] == $search_author ? 'selected' : '' ?>>
      <?= htmlspecialchars($author['LastName'] . ' ' . $author['FirstName']) ?>
     </option>
    <?php endforeach; ?>
   </select>

   <select name="genre" class="search-control">
    <option value="">Все жанры</option>
    <?php foreach ($all_genres as $genre): ?>
     <option value="<?= $genre['GenreID'] ?>" <?= $genre['GenreID'] == $search_genre ? 'selected' : '' ?>>
      <?= htmlspecialchars($genre['Name']) ?>
     </option>
    <?php endforeach; ?>
   </select>
  </div>
  <button type="submit" class="btn btn-primary">
   <i class="fas fa-search"></i> Найти
  </button>
  <a href="borrow.php" class="btn-secondary">
   <i class="fas fa-undo"></i> Сбросить
  </a>
 </form>

 <form method="post">
  <div class="book-list">
   <?php foreach ($available_books as $book): ?>
    <div class="book-card">
     <label>
      <input type="checkbox" name="copy_ids[]" value="<?= $book['CopyID'] ?>">
      <strong><?= htmlspecialchars($book['Title']) ?></strong>
      <div>Авторы: <?= htmlspecialchars($book['Authors']) ?></div>
      <div>Жанры: <?= htmlspecialchars($book['Genres']) ?></div>
      <div>Издательство: <?= htmlspecialchars($book['Publisher']) ?></div>
      <div>Год издания: <?= $book['YearPublished'] ?></div>
      <div class="book-description">
       <?= !empty($book['Description'])
        ? htmlspecialchars($book['Description'])
        : 'Описание отсутствует' ?>
      </div>
     </label>
    </div>
   <?php endforeach; ?>
  </div>

  <?php if (!empty($available_books)): ?>
   <button type="submit">Забронировать выбранные книги</button>
  <?php else: ?>
   <div>Нет доступных книг для бронирования</div>
  <?php endif; ?>
 </form>
</body>

</html>