<?php
require("setup2.php");

// Включение отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Функции
function calculateFine($dueDate)
{
    $diff = time() - strtotime($dueDate);
    return $diff > 0 ? floor($diff / 86400) * 50 : 0;
}

function updateFines($conn)
{
    $result = mysqli_query(
        $conn,
        "SELECT BorrowID, DueDate 
        FROM Borrowings 
        WHERE ReturnDate IS NULL"
    ) or die(mysqli_error($conn));

    while ($row = mysqli_fetch_assoc($result)) {
        $fine = calculateFine($row['DueDate']);
        mysqli_query(
            $conn,
            "UPDATE Borrowings SET Fine = $fine 
            WHERE BorrowID = {$row['BorrowID']}"
        ) or die(mysqli_error($conn));
    }
}

// Добавленная функция для сохранения истории
function saveHistory($conn, $borrowId)
{
    // Получаем текущие данные о выдаче
    $borrowing = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT DueDate, ReturnDate 
         FROM Borrowings 
         WHERE BorrowID = $borrowId"
    )) or die(mysqli_error($conn));

    // Обрабатываем NULL для ReturnDate
    $dueDate = $borrowing['DueDate'];
    $returnDate = $borrowing['ReturnDate'];

    // Формируем SQL-значение для ReturnDate
    $returnDateSQL = $returnDate ? "'$returnDate'" : "NULL"; // NULL без кавычек!

    // Вставляем запись в историю
    mysqli_query(
        $conn,
        "INSERT INTO BorrowingsHistory 
            (BorrowID, DueDate, ReturnDate, ChangedAt)
         VALUES 
            ($borrowId, '$dueDate', $returnDateSQL, NOW())"
    ) or die(mysqli_error($conn));
}

// Обработка операций
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка резервации
    if (isset($_POST['reserve'])) {
        $isbn = mysqli_real_escape_string($conn, $_POST['isbn']);
        $readerId = (int) $_POST['reader'];

        $copy = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT CopyID FROM Copies 
             WHERE ISBN = '$isbn' AND Status = 'Available' 
             LIMIT 1"
        )) or die(mysqli_error($conn));

        if ($copy) {
            $copyId = $copy['CopyID'];
            mysqli_query(
                $conn,
                "INSERT INTO Borrowings 
                 (CopyID, ReaderID, BorrowDate, DueDate)
                 VALUES ($copyId, $readerId, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY))"
            ) or die(mysqli_error($conn));

            mysqli_query(
                $conn,
                "UPDATE Copies 
                 SET Status = 'Borrowed' 
                 WHERE CopyID = $copyId"
            ) or die(mysqli_error($conn));
        }
    } elseif (isset($_POST['return'])) {
        $borrowId = (int) $_POST['borrow_id'];
        saveHistory($conn, $borrowId);
        mysqli_query(
            $conn,
            "UPDATE Borrowings SET ReturnDate = CURDATE() 
             WHERE BorrowID = $borrowId"
        );
    } elseif (isset($_POST['prolong'])) {
        $borrowId = (int) $_POST['borrow_id'];
        saveHistory($conn, $borrowId);
        $newDate = mysqli_real_escape_string($conn, $_POST['new_date']);
        mysqli_query(
            $conn,
            "UPDATE Borrowings SET DueDate = '$newDate' 
             WHERE BorrowID = $borrowId"
        );
    } elseif (isset($_POST['cancel'])) {
        $borrowId = (int) $_POST['borrow_id'];
        $history = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT * FROM BorrowingsHistory 
             WHERE BorrowID = $borrowId 
             ORDER BY ChangedAt DESC LIMIT 1"
        ));

        if ($history) {
            // Восстановление из истории
            mysqli_query(
                $conn,
                "UPDATE Borrowings 
                 SET DueDate = '{$history['DueDate']}',
                     ReturnDate = '{$history['ReturnDate']}'
                 WHERE BorrowID = $borrowId"
            );

            mysqli_query(
                $conn,
                "DELETE FROM BorrowingsHistory 
                 WHERE HistoryID = {$history['HistoryID']}"
            );

            $newStatus = $history['ReturnDate'] ? 'Available' : 'Borrowed';
            $copyId = mysqli_fetch_assoc(mysqli_query(
                $conn,
                "SELECT CopyID FROM Borrowings 
                 WHERE BorrowID = $borrowId"
            ))['CopyID'];

            mysqli_query(
                $conn,
                "UPDATE Copies SET Status = '$newStatus' 
                 WHERE CopyID = $copyId"
            );
        } else {
            // Полная отмена
            $copyId = mysqli_fetch_assoc(mysqli_query(
                $conn,
                "SELECT CopyID FROM Borrowings 
                 WHERE BorrowID = $borrowId"
            ))['CopyID'];

            mysqli_query(
                $conn,
                "DELETE FROM Borrowings WHERE BorrowID = $borrowId"
            );
            mysqli_query(
                $conn,
                "UPDATE Copies SET Status = 'Available' 
                 WHERE CopyID = $copyId"
            );
        }
        updateFines($conn);
    }
}

// Получение данных
updateFines($conn);

// Получение читателей
$readers_result = mysqli_query($conn, "SELECT * FROM Readers")
    or die("Ошибка получения читателей: " . mysqli_error($conn));
$readers = [];
while ($row = mysqli_fetch_assoc($readers_result)) {
    $readers[] = $row;
}

// Получение книг
$books_result = mysqli_query($conn, "SELECT * FROM Books")
    or die("Ошибка получения книг: " . mysqli_error($conn));
$books = [];
while ($row = mysqli_fetch_assoc($books_result)) {
    $books[] = $row;
}

// Получение истории
$history = mysqli_query(
    $conn,
    "SELECT b.*, bk.Title, 
     CONCAT(r.LastName, ' ', r.FirstName) AS ReaderName,
     c.ISBN
     FROM Borrowings b
     JOIN Copies c ON b.CopyID = c.CopyID
     JOIN Books bk ON c.ISBN = bk.ISBN
     JOIN Readers r ON b.ReaderID = r.ReaderID
     ORDER BY b.BorrowDate DESC"
) or die(mysqli_error($conn));
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Библиотечная система</title>
    <style>
        :root {
            --primary: #2ecc71;
            --secondary: #3498db;
            --danger: #e74c3c;
            --background: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', system-ui;
            margin: 0;
            background: var(--background);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgb(8, 202, 56);
            padding: 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-radius: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
            color: white;
            margin: 5px;
        }

        .btn-primary {
            background: var(--secondary);
        }

        .btn-success {
            background: var(--primary);
        }

        .btn-danger {
            background: var(--danger);
        }

        .operation-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .operation-table th,
        .operation-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .operation-table th {
            background: var(--secondary);
            color: white;
            position: sticky;
            top: 0;
        }

        .form-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 2px solid #ddd;
            border-radius: 5px;
            background: white;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Управление бронированием</h1>
        <a href="index.php" class="btn btn-primary">Главная</a>
    </div>

    <div class="container">
        <!-- Форма резервации -->
        <div class="form-section">
            <h2>Новая резервация</h2>
            <form method="POST">
                <select name="reader" required>
                    <option value="">Выберите читателя</option>
                    <?php if (count($readers) > 0): ?>
                        <?php foreach ($readers as $reader): ?>
                            <option value="<?= $reader['ReaderID'] ?>">
                                <?= htmlspecialchars($reader['LastName'] ?? '') ?>
                                <?= htmlspecialchars($reader['FirstName'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Нет доступных читателей</option>
                    <?php endif; ?>
                </select>

                <select name="isbn" required>
                    <option value="">Выберите книгу</option>
                    <?php if (count($books) > 0): ?>
                        <?php foreach ($books as $book): ?>
                            <option value="<?= $book['ISBN'] ?>">
                                <?= htmlspecialchars($book['Title'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Нет доступных книг</option>
                    <?php endif; ?>
                </select>

                <button type="submit" name="reserve" class="btn btn-success">
                    Зарезервировать
                </button>
            </form>
        </div>

        <!-- История операций -->
        <div class="form-section">
            <h2>История операций</h2>
            <table class="operation-table">
                <thead>
                    <tr>
                        <th>Книга</th>
                        <th>Читатель</th>
                        <th>Дата выдачи</th>
                        <th>Срок возврата</th>
                        <th>Статус</th>
                        <th>Штраф</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($history)): ?>
                        <?php
                        $isOverdue = !$row['ReturnDate'] &&
                            strtotime($row['DueDate']) < time();
                        $status = $row['ReturnDate']
                            ? 'Возвращено: ' . date('d.m.Y', strtotime($row['ReturnDate']))
                            : ($isOverdue ? 'Просрочено' : 'Активно');
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Title']) ?></td>
                            <td><?= htmlspecialchars($row['ReaderName']) ?></td>
                            <td><?= date('d.m.Y', strtotime($row['BorrowDate'])) ?></td>
                            <td class="<?= $isOverdue ? 'overdue' : '' ?>">
                                <?= date('d.m.Y', strtotime($row['DueDate'])) ?>
                            </td>
                            <td><?= $status ?></td>
                            <td><?= $row['Fine'] ?> ₽</td>
                            <td>
                                <form method="POST" style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <input type="hidden" name="borrow_id" value="<?= $row['BorrowID'] ?>">

                                    <?php if (!$row['ReturnDate']): ?>
                                        <input type="date" name="new_date" min="<?= date('Y-m-d') ?>"
                                            value="<?= date('Y-m-d', strtotime($row['DueDate'])) ?>">
                                        <button type="submit" name="prolong" class="btn btn-primary">
                                            Продлить
                                        </button>
                                        <button type="submit" name="return" class="btn btn-success">
                                            Вернуть
                                        </button>
                                    <?php endif; ?>

                                    <button type="submit" name="cancel" class="btn btn-danger">
                                        Отменить
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>

<?php
mysqli_close($conn);
?>