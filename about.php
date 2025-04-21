<?php
require("setup2.php"); // Подключение к БД при необходимости
?>
<!DOCTYPE html>
<html lang='ru'>

<head>
 <meta charset='UTF-8'>
 <title>О библиотеке Сумеру</title>
 <style>
  /* Общие стили */
  * {
   margin: 0;
   padding: 0;
   box-sizing: border-box;
   font-family: 'Roboto', sans-serif;
  }

  .about-container {
   max-width: 1200px;
   margin: 100px auto 50px;
   padding: 0 20px;
  }

  .about-section {
   background: white;
   padding: 40px;
   border-radius: 15px;
   box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
   margin-bottom: 40px;
  }

  .timeline {
   position: relative;
   padding: 40px 0;
  }

  .timeline-item {
   padding: 20px;
   margin: 20px 0;
   background: #f8f9fa;
   border-left: 4px solid #4CAF50;
   position: relative;
  }

  /* Стили из главной страницы */
  .nav-menu {
   background: rgba(255, 255, 255, 0.95);
   box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
   padding: 15px 0;
   position: fixed;
   width: 100%;
   top: 0;
   z-index: 1000;
  }

  .nav-links {
   display: flex;
   justify-content: center;
   gap: 40px;
   list-style: none;
  }

  .nav-links a {
   color: #333;
   text-decoration: none;
   font-weight: 500;
   padding: 10px 15px;
   border-radius: 25px;
   transition: all 0.3s ease;
  }

  .nav-links a:hover {
   background: #4CAF50;
   color: white;
  }

  h1 {
   font-size: 2.5em;
   color: #2c3e50;
   margin-bottom: 30px;
   text-align: center;
  }

  h2 {
   color: #4CAF50;
   margin-bottom: 20px;
  }

  p {
   line-height: 1.8;
   color: #555;
   margin-bottom: 20px;
  }

  .stats-grid {
   display: grid;
   grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
   gap: 30px;
   margin: 40px 0;
  }

  .stat-card {
   text-align: center;
   padding: 30px;
   background: #f8f9fa;
   border-radius: 10px;
  }

  .stat-number {
   font-size: 2.5em;
   color: #4CAF50;
   font-weight: 700;
  }

  @media (max-width: 768px) {
   .nav-links {
    gap: 15px;
    flex-wrap: wrap;
   }

   .about-section {
    padding: 20px;
   }
  }
 </style>
</head>

<body>
 <nav class="nav-menu">
  <ul class="nav-links">
   <li><a href="index.php">Назад</a></li>
  </ul>
 </nav>

 <div class="about-container">
  <h1>Цифровая библиотека Сумеру</h1>

  <div class="about-section">
   <h2>📖 Наша миссия</h2>
   <p>Мы создаем доступную цифровую среду для любителей чтения, объединяя традиционные библиотечные ценности с
    современными технологиями. Наша коллекция включает более 50 000 изданий на 15 языках мира.</p>

   <div class="stats-grid">
    <div class="stat-card">
     <div class="stat-number">50 000+</div>
     <div class="stat-text">Электронных книг</div>
    </div>
    <div class="stat-card">
     <div class="stat-number">120+</div>
     <div class="stat-text">Языковых направлений</div>
    </div>
    <div class="stat-card">
     <div class="stat-number">24/7</div>
     <div class="stat-text">Доступ к ресурсам</div>
    </div>
   </div>
  </div>

  <div class="about-section">
   <h2>🕰 История создания</h2>
   <div class="timeline">
    <div class="timeline-item">
     <h3>2015 - Основание</h3>
     <p>Старт проекта как университетской цифровой библиотеки</p>
    </div>
    <div class="timeline-item">
     <h3>2018 - Расширение</h3>
     <p>Включение в национальную программу цифровизации культуры</p>
    </div>
    <div class="timeline-item">
     <h3>2023 - Новый формат</h3>
     <p>Запуск мобильного приложения и AI-рекомендаций</p>
    </div>
   </div>
  </div>

  <div class="about-section">
   <h2>📚 Особенности коллекции</h2>
   <ul style="list-style: none;">
    <li>✅ Редкие архивные издания</li>
    <li>✅ Аудиокниги и подкасты</li>
    <li>✅ Интерактивные учебные материалы</li>
    <li>✅ Еженедельное пополнение коллекции</li>
   </ul>
  </div>
 </div>
</body>

</html>