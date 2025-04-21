<?php
require("setup2.php");

// Проверяем наличие ID выдачи
if (!isset($_GET['borrow_id']) || !is_numeric($_GET['borrow_id'])) {
    die("Неверный ID выдачи");
}

$borrow_id = (int)$_GET['borrow_id'];

// Получаем данные о выдаче
$query = "SELECT 
            b.BorrowID,
            CONCAT(r.LastName, ' ', r.FirstName) AS ReaderName,
            r.Phone AS ReaderPhone,
            r.Email AS ReaderEmail,
            bk.Title AS BookTitle,
            bk.ISBN,
            c.CopyID,
            c.Status AS CopyStatus,
            b.BorrowDate,
            b.DueDate,
            b.ReturnDate,
            b.Fine,
            p.Name AS PublisherName,
            GROUP_CONCAT(CONCAT(a.LastName, ' ', a.FirstName) SEPARATOR ', ') AS Authors
          FROM Borrowings b
          LEFT JOIN Readers r ON b.ReaderID = r.ReaderID
          LEFT JOIN Copies c ON b.CopyID = c.CopyID
          LEFT JOIN Books bk ON c.ISBN = bk.ISBN
          LEFT JOIN Publishers p ON bk.PublisherID = p.PublisherID
          LEFT JOIN BookAuthors ba ON bk.ISBN = ba.ISBN
          LEFT JOIN Authors a ON ba.AuthorID = a.AuthorID
          WHERE b.BorrowID = ?
          GROUP BY b.BorrowID";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $borrow_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Выдача не найдена");
}

$borrowing = $result->fetch_assoc();

// Обработка возврата книги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $return_date = date('Y-m-d');
    $fine = 0;
    
    // Рассчитываем штраф при просрочке
    if (strtotime($return_date) > strtotime($borrowing['DueDate'])) {
        $days_late = ceil((strtotime($return_date) - strtotime($borrowing['DueDate'])) / 86400);
        $fine = $days_late * 50; // 50 руб./день просрочки
    }
    
    // Обновляем запись о выдаче
    $update_query = "UPDATE Borrowings 
                    SET ReturnDate = ?, Fine = ?
                    WHERE BorrowID = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sdi", $return_date, $fine, $borrow_id);
    
    if ($stmt->execute()) {
        // Обновляем статус экземпляра
        $conn->query("UPDATE Copies SET Status = 'Available' WHERE CopyID = {$borrowing['CopyID']}");
        header("Refresh:0"); // Обновляем страницу
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Детали выдачи #<?= $borrow_id ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .section h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .info-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .info-table td { padding: 10px; border: 1px solid #eee; }
        .info-table td:first-child { width: 40%; font-weight: bold; background-color: #f9f9f9; }
        .status-returned { color: #27ae60; }
        .status-overdue { color: #e74c3c; }
        button { 
            background-color: #3498db; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
        }
        button:hover { background-color: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Детали выдачи #<?= $borrow_id ?></h1>
        
        <!-- Информация о выдаче -->
        <div class="section">
            <h2>Основная информация</h2>
            <table class="info-table">
                <tr>
                    <td>Читатель:</td>
                    <td><?= htmlspecialchars($borrowing['ReaderName']) ?></td>
                </tr>
                <tr>
                    <td>Книга:</td>
                    <td>
                        <?= htmlspecialchars($borrowing['BookTitle']) ?><br>
                        <small>ISBN: <?= htmlspecialchars($borrowing['ISBN']) ?></small>
                    </td>
                </tr>
                <tr>
                    <td>Авторы:</td>
                    <td><?= htmlspecialchars($borrowing['Authors']) ?></td>
                </tr>
                <tr>
                    <td>Издательство:</td>
                    <td><?= htmlspecialchars($borrowing['PublisherName']) ?></td>
                </tr>
                <tr>
                    <td>Экземпляр:</td>
                    <td>№<?= htmlspecialchars($borrowing['CopyID']) ?> 
                        (Статус: <?= htmlspecialchars($borrowing['CopyStatus']) ?>)
                    </td>
                </tr>
            </table>
        </div>

        <!-- Даты и штрафы -->
        <div class="section">
            <h2>Сроки и оплата</h2>
            <table class="info-table">
                <tr>
                    <td>Дата выдачи:</td>
                    <td><?= htmlspecialchars($borrowing['BorrowDate']) ?></td>
                </tr>
                <tr>
                    <td>Срок возврата:</td>
                    <td class="<?= (strtotime(date('Y-m-d')) > strtotime($borrowing['DueDate']) && !$borrowing['ReturnDate']) ? 'status-overdue' : '' ?>">
                        <?= htmlspecialchars($borrowing['DueDate']) ?>
                    </td>
                </tr>
                <tr>
                    <td>Факт возврата:</td>
                    <td class="<?= $borrowing['ReturnDate'] ? 'status-returned' : '' ?>">
                        <?= $borrowing['ReturnDate'] ? htmlspecialchars($borrowing['ReturnDate']) : 'Не возвращено' ?>
                    </td>
                </tr>
                <tr>
                    <td>Штраф:</td>
                    <td><?= $borrowing['Fine'] ? htmlspecialchars($borrowing['Fine']) . ' руб.' : 'Нет' ?></td>
                </tr>
            </table>
        </div>

        <!-- Форма возврата -->
        <?php if (!$borrowing['ReturnDate']): ?>
        <div class="section">
            <h2>Возврат книги</h2>
            <form method="POST">
                <p>Отметить книгу как возвращенную:</p>
                <button type="submit" name="return_book">Подтвердить возврат</button>
                <p><small>При наличии просрочки будет автоматически рассчитан штраф (50 руб./день)</small></p>
            </form>
        </div>
        <?php endif; ?>

        <!-- Контакты читателя -->
        <div class="section">
            <h2>Контактная информация</h2>
            <table class="info-table">
                <tr>
                    <td>Телефон:</td>
                    <td><?= htmlspecialchars($borrowing['ReaderPhone']) ?></td>
                </tr>
                <tr>
                    <td>Email:</td>
                    <td><?= htmlspecialchars($borrowing['ReaderEmail']) ?></td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>