<?php
session_start();

// Установите идентификатор пользователя и роль администратора
$admin_user_id = 1; // Можно использовать любой уникальный идентификатор
$admin_role = 'admin';

// Установите сессию для администратора
$_SESSION['user_id'] = $admin_user_id;
$_SESSION['role'] = $admin_role;

// Перенаправьте на главную страницу или другую страницу после входа
header("Location: index.php");
exit();
?>