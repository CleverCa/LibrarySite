<?php
require("setup2.php");

// Проверка авторизации
session_start();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT role FROM readers WHERE ReaderID = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

if (!isset($_GET['title']) || trim($_GET['title']) === '') {
    die("Название книги не указано");
}

$title = trim($_GET['title']);

$query = "SELECT
        b.Title,
        b.YearPublished,
        b.Description,
        p.Name AS Publisher,
        GROUP_CONCAT(
            CONCAT(
                a.LastName, ' ',
                a.FirstName,
                IF(a.MiddleName IS NOT NULL, CONCAT(' ', a.MiddleName), '')
            ) SEPARATOR ', '
        ) AS Authors,
        GROUP_CONCAT(DISTINCT g.Name SEPARATOR ', ') AS Genres
    FROM Books b
    LEFT JOIN Publishers p ON b.PublisherID = p.PublisherID
    LEFT JOIN BookAuthors ba ON b.ISBN = ba.ISBN
    LEFT JOIN Authors a ON ba.AuthorID = a.AuthorID
    LEFT JOIN BookGenres bg ON b.ISBN = bg.ISBN
    LEFT JOIN Genres g ON bg.GenreID = g.GenreID
    WHERE b.Title = ?
    GROUP BY b.ISBN
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    die("Ошибка подготовки запроса: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "s", $title);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("Ошибка выполнения запроса: " . mysqli_error($conn));
}

if ($result->num_rows > 1) {
    echo "Найдено несколько книг с таким названием!";
}

$book = mysqli_fetch_assoc($result);

if (!$book) {
    die("Книга '" . htmlspecialchars($_GET['title']) . "' не найдена");
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang='ru'>

<head>
    <style>
        body {
            font-family: Arial;
            margin: 20px;
        }

        .details {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .detail-item {
            margin: 10px 0;
        }

        .description {
            text-align: justify;
        }
    </style>
</head>

<body>
    <a href="<?= $is_admin ? 'admin_books.php' : 'books2.php' ?>" class="back-link">К каталогу</a>

    <div class="details">
        <h1><?= htmlspecialchars($book['Title']) ?></h1>

        <div class="detail-item">
            <strong>Авторы:</strong>
            <?= htmlspecialchars($book['Authors'] ?? '—') ?>
        </div>

        <div class="detail-item">
            <strong>Издательство:</strong>
            <?= htmlspecialchars($book['Publisher'] ?? '—') ?>
        </div>

        <div class="detail-item">
            <strong>Год издания:</strong>
            <?= htmlspecialchars($book['YearPublished'] ?? '—') ?>
        </div>

        <div class="detail-item">
            <strong>Жанры:</strong>
            <?= htmlspecialchars($book['Genres'] ?? '—') ?>
        </div>

        <?php if (!empty($book['Description'])): ?>
            <div class="detail-item description">
                <strong>Описание:</strong><br>
                <?= nl2br(htmlspecialchars($book['Description'])) ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>