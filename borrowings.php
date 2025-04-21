<?php
require("setup2.php");

// Получаем фильтры
$reader_id = $_GET['reader'] ?? 0;
$status = $_GET['status'] ?? 'all';

// Базовый запрос
$query = "SELECT 
            br.BorrowID,
            CONCAT(r.LastName, ' ', r.FirstName) AS Reader,
            b.Title,
            b.ISBN,
            br.BorrowDate,
            br.DueDate,
            br.ReturnDate,
            br.Fine
          FROM Borrowings br
          JOIN Readers r ON br.ReaderID = r.ReaderID
          JOIN Copies c ON br.CopyID = c.CopyID
          JOIN Books b ON c.ISBN = b.ISBN
          WHERE 1=1";

// Применяем фильтры
if($reader_id > 0) $query .= " AND r.ReaderID = $reader_id";
if($status == 'active') $query .= " AND br.ReturnDate IS NULL";
if($status == 'returned') $query .= " AND br.ReturnDate IS NOT NULL";

$readers = mysqli_query($conn, "SELECT ReaderID, CONCAT(LastName, ' ', FirstName) AS Name FROM Readers");
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <title>История выдачи</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .menu { 
            margin-bottom: 30px; 
        }
        .menu a { 
            display: inline-block; 
            margin-right: 15px; 
            padding: 10px 20px; 
            background: #4CAF50; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px;
            transition: background 0.3s;
        }
        .menu a:hover {
            background: #45a049;
        }
        .filter-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .filter-form label {
            display: inline-block;
            margin-right: 10px;
            font-weight: bold;
            min-width: 80px;
        }
        .filter-form select, 
        .filter-form input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 15px;
        }
        .filter-form button {
            padding: 8px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .filter-form button:hover {
            background: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
            position: sticky;
            top: 0;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.9em;
        }
        .active {
            background: #ffd700;
            color: #000;
        }
        .returned {
            background: #90EE90;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="menu">
            <a href="index.php">Главная</a>
        </div>

        <h2>История выдачи книг</h2>
        
        <div class="filter-form">
            <form method="get">
                <label>Читатель:</label>
                <select name="reader">
                    <option value="0">Все читатели</option>
                    <?php while($row = mysqli_fetch_assoc($readers)): ?>
                    <option value="<?= $row['ReaderID'] ?>" 
                        <?= $row['ReaderID'] == $reader_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['Name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                
                <label>Статус:</label>
                <select name="status">
                    <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>Все записи</option>
                    <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>Активные</option>
                    <option value="returned" <?= $status == 'returned' ? 'selected' : '' ?>>Возвращенные</option>
                </select>
                
                <button type="submit">Применить фильтр</button>
            </form>
        </div>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Читатель</th>
                        <th>Книга</th>
                        <th>ISBN</th>
                        <th>Дата выдачи</th>
                        <th>Срок возврата</th>
                        <th>Статус</th>
                        <th>Штраф</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Reader']) ?></td>
                        <td><?= htmlspecialchars($row['Title']) ?></td>
                        <td><?= htmlspecialchars($row['ISBN']) ?></td>
                        <td><?= date('d.m.Y', strtotime($row['BorrowDate'])) ?></td>
                        <td><?= date('d.m.Y', strtotime($row['DueDate'])) ?></td>
                        <td>
                            <?php if($row['ReturnDate']): ?>
                                <span class="status-badge returned">
                                    Возвращено <?= date('d.m.Y', strtotime($row['ReturnDate'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge active">На руках</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($row['Fine'], 2, ',', ' ') ?> руб.</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #666; margin: 20px 0;">Нет данных по выбранным критериям</p>
        <?php endif; ?>
    </div>
</body>
</html>