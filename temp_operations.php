<?php
require("setup2.php");

// Функции для работы с БД
function calculateFine($dueDate) {
    $currentDate = time();
    $dueTimestamp = strtotime($dueDate);
    $diff = $currentDate - $dueTimestamp;
    
    return $diff > 0 ? floor($diff / 86400) * 50 : 0;
}

function updateFines($conn) {
    $result = mysqli_query($conn, 
        "SELECT BorrowID, DueDate 
         FROM Borrowings 
         WHERE ReturnDate IS NULL");
    
    while($row = mysqli_fetch_assoc($result)) {
        $fine = calculateFine($row['DueDate']);
        mysqli_query($conn, 
            "UPDATE Borrowings 
             SET Fine = $fine 
             WHERE BorrowID = {$row['BorrowID']}");
    }
}

// Основная обработка операций
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка резервации
    if (isset($_POST['reserve'])) {
        $isbn = mysqli_real_escape_string($conn, $_POST['isbn']);
        $readerId = (int)$_POST['reader'];
        
        $copy = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT CopyID FROM Copies 
             WHERE ISBN = '$isbn' AND Status = 'Available' 
             LIMIT 1"));
        
        if ($copy) {
            $copyId = $copy['CopyID'];
            mysqli_query($conn, 
                "INSERT INTO Borrowings 
                 (CopyID, ReaderID, BorrowDate, DueDate)
                 VALUES ($copyId, $readerId, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY))");
            
            mysqli_query($conn, 
                "UPDATE Copies 
                 SET Status = 'Borrowed' 
                 WHERE CopyID = $copyId");
        }
    }
    
    // Обработка возврата
    elseif (isset($_POST['return'])) {
        $borrowId = (int)$_POST['borrow_id'];
        mysqli_query($conn, 
            "UPDATE Borrowings 
             SET ReturnDate = CURDATE() 
             WHERE BorrowID = $borrowId");
    }
    
    // Обработка продления
    elseif (isset($_POST['prolong'])) {
        $borrowId = (int)$_POST['borrow_id'];
        $newDate = mysqli_real_escape_string($conn, $_POST['new_date']);
        mysqli_query($conn, 
            "UPDATE Borrowings 
             SET DueDate = '$newDate' 
             WHERE BorrowID = $borrowId");
    }
    
    // Обработка отмены
    elseif (isset($_POST['cancel'])) {
        $borrowId = (int)$_POST['borrow_id'];
        $copyId = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT CopyID FROM Borrowings 
             WHERE BorrowID = $borrowId"))['CopyID'];
        
        mysqli_query($conn, 
            "DELETE FROM Borrowings 
             WHERE BorrowID = $borrowId");
        mysqli_query($conn, 
            "UPDATE Copies 
             SET Status = 'Available' 
             WHERE CopyID = $copyId");
    }
}

// Обновление штрафов и получение данных
updateFines($conn);
$history = mysqli_query($conn, 
    "SELECT b.*, bk.Title, 
     CONCAT(r.LastName, ' ', r.FirstName) AS ReaderName,
     c.ISBN
     FROM Borrowings b
     JOIN Copies c ON b.CopyID = c.CopyID
     JOIN Books bk ON c.ISBN = bk.ISBN
     JOIN Readers r ON b.ReaderID = r.ReaderID
     ORDER BY b.BorrowDate DESC");

// Получение списков
$readers = mysqli_query($conn, "SELECT * FROM Readers");
$books = mysqli_query($conn, "SELECT * FROM Books");
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
            background: #2c3e50;
            padding: 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
            color: white;
        }
        
        .btn-primary { background: var(--secondary); }
        .btn-success { background: var(--primary); }
        .btn-danger { background: var(--danger); }
        
        .operation-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }
        
        .overdue { color: var(--danger); }
    </style>
</head>
<body>
    <div class="header">
        <h1>Управление библиотекой</h1>
        <a href="index.php" class="btn btn-primary">Главная</a>
    </div>

    <div class="container">
        <!-- Форма резервации -->
        <div class="reservation-form">
            <h2>Новая резервация</h2>
            <form method="POST">
                <select name="reader" required>
                    <option value="">Выберите читателя</option>
                    <?php while($r = mysqli_fetch_assoc($readers)): ?>
                    <option value="<?= $r['ReaderID'] ?>">
                        <?= "{$r['LastName']} {$r['FirstName']}" ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                
                <select name="isbn" required>
                    <option value="">Выберите книгу</option>
                    <?php while($b = mysqli_fetch_assoc($books)): ?>
                    <option value="<?= $b['ISBN'] ?>">
                        <?= $b['Title'] ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                
                <button type="submit" name="reserve" class="btn btn-success">
                    Зарезервировать
                </button>
            </form>
        </div>

        <!-- История операций -->
        <div class="operations-history">
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
                    <?php while($row = mysqli_fetch_assoc($history)): ?>
                    <?php
                        $isOverdue = !$row['ReturnDate'] && 
                                    strtotime($row['DueDate']) < time();
                        $status = $row['ReturnDate'] ? 
                                 'Возвращено: ' . date('d.m.Y', strtotime($row['ReturnDate'])) : 
                                 ($isOverdue ? 'Просрочено' : 'Активно');
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
                            <form method="POST" style="display: flex; gap: 5px;">
                                <input type="hidden" name="borrow_id" value="<?= $row['BorrowID'] ?>">
                                
                                <?php if(!$row['ReturnDate']): ?>
                                <input type="date" 
                                       name="new_date" 
                                       min="<?= date('Y-m-d') ?>" 
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