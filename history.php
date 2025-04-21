<?php
session_start();
require_once("setup2.php");

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем данные пользователя
$reader_id = $_SESSION['user_id'];
$history = [];
$errors = [];
$total_records = 0;
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'BorrowDate';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

try {
    // Запрос для получения общего количества записей
    $count_query = "
        SELECT COUNT(*) AS total
        FROM Borrowings br
        JOIN Copies c ON br.CopyID = c.CopyID
        JOIN Books b ON c.ISBN = b.ISBN
        LEFT JOIN BookAuthors ba ON b.ISBN = ba.ISBN
        LEFT JOIN Authors a ON ba.AuthorID = a.AuthorID
        LEFT JOIN Publishers p ON b.PublisherID = p.PublisherID
        WHERE br.ReaderID = ?
    ";

    if (!empty($status_filter)) {
        $count_query .= " AND CASE
                WHEN br.ReturnDate IS NULL AND br.DueDate < CURDATE() THEN 'Просрочено'
                WHEN br.ReturnDate IS NULL THEN 'На руках'
                ELSE 'Возвращено'
            END = ?";
    }

    $count_stmt = mysqli_prepare($conn, $count_query);
    if (!empty($status_filter)) {
        mysqli_stmt_bind_param($count_stmt, "is", $reader_id, $status_filter);
    } else {
        mysqli_stmt_bind_param($count_stmt, "i", $reader_id);
    }
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_records = mysqli_fetch_assoc($count_result)['total'];

    // Запрос для получения истории бронирований
    $query = "
        SELECT
            b.Title,
            GROUP_CONCAT(DISTINCT CONCAT(a.LastName, ' ', a.FirstName) SEPARATOR ', ') AS Authors,
            br.BorrowDate,
            br.DueDate,
            br.ReturnDate,
            CASE
                WHEN br.ReturnDate IS NULL AND br.DueDate < CURDATE() THEN 'Просрочено'
                WHEN br.ReturnDate IS NULL THEN 'На руках'
                ELSE 'Возвращено'
            END AS Status,
            c.CopyID,
            p.Name AS Publisher,
            DATEDIFF(CURDATE(), br.DueDate) AS OverdueDays
        FROM Borrowings br
        JOIN Copies c ON br.CopyID = c.CopyID
        JOIN Books b ON c.ISBN = b.ISBN
        LEFT JOIN BookAuthors ba ON b.ISBN = ba.ISBN
        LEFT JOIN Authors a ON ba.AuthorID = a.AuthorID
        LEFT JOIN Publishers p ON b.PublisherID = p.PublisherID
        WHERE br.ReaderID = ?
    ";

    if (!empty($status_filter)) {
        $query .= " AND CASE
                WHEN br.ReturnDate IS NULL AND br.DueDate < CURDATE() THEN 'Просрочено'
                WHEN br.ReturnDate IS NULL THEN 'На руках'
                ELSE 'Возвращено'
            END = ?";
    }

    $query .= " GROUP BY br.BorrowID ORDER BY $sort_column $sort_order LIMIT ?, ?";

    $stmt = mysqli_prepare($conn, $query);
    if (!empty($status_filter)) {
        mysqli_stmt_bind_param($stmt, "isii", $reader_id, $status_filter, $offset, $records_per_page);
    } else {
        mysqli_stmt_bind_param($stmt, "iii", $reader_id, $offset, $records_per_page);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $history = mysqli_fetch_all($result, MYSQLI_ASSOC);

} catch (Exception $e) {
    $errors[] = "Ошибка при получении данных: " . $e->getMessage();
}

// Обработка продления срока
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['extend'])) {
    $copy_id = $_POST['copy_id'];
    try {
        $update_query = "UPDATE Borrowings SET DueDate = DATE_ADD(DueDate, INTERVAL 14 DAY) WHERE CopyID = ? AND ReturnDate IS NULL";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "i", $copy_id);
        mysqli_stmt_execute($update_stmt);
        header("Location: history.php");
        exit();
    } catch (Exception $e) {
        $errors[] = "Ошибка при продлении срока: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>История бронирования</title>
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
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .status {
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: bold;
            text-align: center;
        }

        .returned {
            background-color: #d4edda;
            color: #155724;
        }

        .overdue {
            background-color: #f8d7da;
            color: #721c24;
        }

        .active {
            background-color: #fff3cd;
            color: #856404;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .back-btn:hover {
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

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a {
            padding: 10px 15px;
            margin: 0 5px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .pagination a:hover {
            background: #45a049;
        }

        .filter {
            margin-bottom: 20px;
        }

        .filter label {
            margin-right: 10px;
        }

        .filter select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .extend-btn {
            padding: 5px 10px;
            background: #ffc107;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .extend-btn:hover {
            background: #e0a800;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="back-btn">На главную</a>
        <a href="profile.php" class="back-btn">Личный кабинет</a>
        <h1>История ваших бронирований</h1>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="filter">
            <label for="status-filter">Фильтр по статусу:</label>
            <select id="status-filter" onchange="filterStatus()">
                <option value="">Все</option>
                <option value="Просрочено" <?= $status_filter == 'Просрочено' ? 'selected' : '' ?>>Просрочено</option>
                <option value="На руках" <?= $status_filter == 'На руках' ? 'selected' : '' ?>>На руках</option>
                <option value="Возвращено" <?= $status_filter == 'Возвращено' ? 'selected' : '' ?>>Возвращено</option>
            </select>
        </div>

        <?php if (!empty($history)): ?>
            <table>
                <thead>
                    <tr>
                        <th onclick="sortTable('Title')">Название</th>
                        <th onclick="sortTable('Authors')">Авторы</th>
                        <th onclick="sortTable('BorrowDate')">Дата выдачи</th>
                        <th onclick="sortTable('DueDate')">Срок возврата</th>
                        <th onclick="sortTable('ReturnDate')">Дата возврата</th>
                        <th onclick="sortTable('Status')">Статус</th>
                        <th onclick="sortTable('Publisher')">Издательство</th>
                        <th>Штраф</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $record): ?>
                        <tr>
                            <td><?= htmlspecialchars($record['Title']) ?></td>
                            <td><?= htmlspecialchars($record['Authors']) ?></td>
                            <td><?= date('d.m.Y', strtotime($record['BorrowDate'])) ?></td>
                            <td><?= date('d.m.Y', strtotime($record['DueDate'])) ?></td>
                            <td>
                                <?= $record['ReturnDate']
                                    ? date('d.m.Y', strtotime($record['ReturnDate']))
                                    : '—' ?>
                            </td>
                            <td>
                                <span class="status <?= strtolower($record['Status']) ?>">
                                    <?= $record['Status'] ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($record['Publisher']) ?></td>
                            <td>
                                <?php if ($record['Status'] == 'Просрочено'): ?>
                                    <?= $record['OverdueDays'] * 10 ?> руб.
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($record['Status'] == 'На руках'): ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="copy_id" value="<?= $record['CopyID'] ?>">
                                        <button type="submit" name="extend" class="extend-btn">Продлить</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?= $current_page - 1 ?>&status=<?= $status_filter ?>&sort=<?= $sort_column ?>&order=<?= $sort_order ?>">Предыдущая</a>
                <?php endif; ?>
                <?php if ($current_page < ceil($total_records / $records_per_page)): ?>
                    <a href="?page=<?= $current_page + 1 ?>&status=<?= $status_filter ?>&sort=<?= $sort_column ?>&order=<?= $sort_order ?>">Следующая</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>У вас пока нет истории бронирований</p>
        <?php endif; ?>
    </div>

    <script>
        function filterStatus() {
            const status = document.getElementById('status-filter').value;
            window.location.href = `?status=${status}&sort=<?= $sort_column ?>&order=<?= $sort_order ?>`;
        }

        function sortTable(column) {
            const order = document.querySelector(`th[onclick="sortTable('${column}')"]`).getAttribute('data-order') === 'asc' ? 'desc' : 'asc';
            window.location.href = `?status=<?= $status_filter ?>&sort=${column}&order=${order}`;
        }

        document.querySelectorAll('th').forEach(th => {
            th.setAttribute('data-order', 'asc');
            th.addEventListener('click', function () {
                const order = this.getAttribute('data-order') === 'asc' ? 'desc' : 'asc';
                this.setAttribute('data-order', order);
                sortTable(this.textContent.trim());
            });
        });
    </script>
</body>

</html>
