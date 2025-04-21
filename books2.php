<?php
require("setup2.php");

// Параметры сортировки
$order = $_GET['order'] ?? 'Title';
$direction = $_GET['dir'] ?? 'ASC';

// Разрешенные поля для сортировки
$allowed_orders = ['Title', 'Authors', 'Publisher', 'Genres'];
$order = in_array($order, $allowed_orders) ? $order : 'Title';
$direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

// Основной запрос
$query = "SELECT 
            b.Title,
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
          GROUP BY b.ISBN
          ORDER BY ";

// Обработка сортировки
switch ($order) {
  case 'Authors':
    $query .= "Authors $direction";
    break;
  case 'Publisher':
    $query .= "Publisher $direction";
    break;
  case 'Genres':
    $query .= "Genres $direction";
    break;
  default:
    $query .= "b.Title $direction";
}

$result = mysqli_query($conn, $query);

if (!$result) {
  die("Ошибка выполнения запроса: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang='ru'>

<head>
  <style>
    body {
      font-family: Arial;
      margin: 20px;
    }

    .menu {
      margin-bottom: 30px;
    }

    .menu a {
      display: inline-block;
      margin-right: 20px;
      padding: 10px;
      background: #4CAF50;
      color: white;
      text-decoration: none;
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
  </style>
</head>

<body>
  <div class="menu">
    <a href="index.php">Главная</a>
  </div>

  <h2>Каталог книг</h2>

  <form method="get">
    <label>Сортировать по:</label>
    <select name="order">
      <option value="Title" <?= $order == 'Title' ? 'selected' : '' ?>>Названию</option>
      <option value="Authors" <?= $order == 'Authors' ? 'selected' : '' ?>>Авторам</option>
      <option value="Publisher" <?= $order == 'Publisher' ? 'selected' : '' ?>>Издательству</option>
      <option value="Genres" <?= $order == 'Genres' ? 'selected' : '' ?>>Жанру</option>
    </select>

    <select name="dir">
      <option value="ASC" <?= $direction == 'ASC' ? 'selected' : '' ?>>По возрастанию</option>
      <option value="DESC" <?= $direction == 'DESC' ? 'selected' : '' ?>>По убыванию</option>
    </select>

    <button type="submit">Применить</button>
  </form>

  <?php if (mysqli_num_rows($result) > 0): ?>
    <table>
      <tr>
        <th>Название</th>
        <th>Авторы</th>
        <th>Издательство</th>
        <th>Жанры</th>
        <th>Подробно</th>
      </tr>
      <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
          <td><?= htmlspecialchars($row['Title']) ?></td>
          <td><?= htmlspecialchars($row['Authors'] ?? 'Авторы не указаны') ?></td>
          <td><?= htmlspecialchars($row['Publisher'] ?? '—') ?></td>
          <td><?= htmlspecialchars($row['Genres'] ?? '—') ?></td>
          <td><a href="details.php?title=<?= urlencode($row['Title']) ?>">Подробно</a></td>
        </tr>
      <?php endwhile; ?>
    </table>
  <?php else: ?>
    <p>В каталоге нет книг</p>
  <?php endif; ?>

</body>

</html>
<?php
mysqli_close($conn);
?>