<?php
session_start();
require_once("setup2.php");

// Проверка авторизации
if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];
  $query = "SELECT role FROM readers WHERE ReaderID = ?";
  $stmt = mysqli_prepare($conn, $query);
  mysqli_stmt_bind_param($stmt, "i", $user_id);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $user = mysqli_fetch_assoc($result);
  //$is_admin = $user['role'] === 'admin';
  $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
?>

<!DOCTYPE html>
<html lang='ru'>

<head>
  <meta charset='UTF-8'>
  <title>Библиотека Сумеру</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Roboto', sans-serif;
    }

    .hero {
      height: 70vh;
      background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
        url('https://avatars.mds.yandex.net/i?id=68d24b4cdd600408f8d644c7fdbbf3d4_l-3597607-images-thumbs&n=13');
      background-size: cover;
      background-position: center;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
      position: relative;
    }

    .hero-content {
      max-width: 800px;
      padding: 20px;
    }

    .hero h1 {
      font-size: 3.5rem;
      margin-bottom: 20px;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }

    .hero p {
      font-size: 1.2rem;
      margin-bottom: 30px;
      line-height: 1.6;
    }

    .nav-menu {
      position: fixed;
      top: 0;
      width: 100%;
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      padding: 15px 0;
    }

    .nav-links {
      display: flex;
      justify-content: center;
      gap: 30px;
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
      background: rgb(76, 175, 80);
      color: white;
      transform: translateY(-2px);
    }

    .content-section {
      padding: 80px 20px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
      margin-top: 50px;
    }

    .feature-card {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
      text-decoration: none;
      color: #333;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
    }

    .feature-card:hover {
      transform: translateY(-5px);
    }

    .feature-content h3 {
      font-size: 1.4em;
      margin-bottom: 10px;
      color: #2c3e50;
    }

    .feature-content p {
      font-size: 1em;
      color: #666;
      line-height: 1.6;
    }

    }
  </style>
</head>

<body>
  <nav class="nav-menu">
    <ul class="nav-links">
      <?php if (isset($_SESSION['user_id'])): ?>
        <?php if ($is_admin): ?>
          <li><a href="temp.php">Управление бронированием</a></li>
          <li><a href="user.php">Управление пользователями</a></li>
          <li><a href="admin_books.php">Управление книгами</a></li>
        <?php else: ?>
          <li><a href="profile.php">Личный кабинет</a></li>
          <li><a href="about.php">О библиотеке</a></li>
          <li><a href="contact.php">Контакты</a></li>
        <?php endif; ?>
        <li><a href="logout.php">Выйти</a></li>
      <?php else: ?>
        <li><a href="register.php">Регистрация</a></li>
        <li class="auth-dropdown">
          <a href="#auth" class="auth-trigger">Авторизация</a>
          <div class="auth-form-wrapper">
            <form class="auth-form" method="POST" action="login.php">
              <div class="form-group">
                <label>Логин:</label>
                <input type="text" name="login" required>
              </div>
              <div class="form-group">
                <label>Пароль:</label>
                <input type="password" name="password" required>
              </div>
              <button type="submit" class="auth-submit">Войти</button>
              <div class="auth-links">
                <a href="register.php">Регистрация</a>
                <a href="parol_email.php">Забыли пароль?</a>
              </div>
            </form>
          </div>
        </li>
        <li><a href="about.php">О библиотеке</a></li>
        <li><a href="contact.php">Контакты</a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <section class="hero">
    <div class="hero-content">
      <h1>Добро пожаловать в библиотеку Сумеру</h1>
      <p>Колоссальная библиотека, расположенная в центре Академии Сумеру.
        Когда-то она считалась хранилищем всех знаний в мире благодаря своей беспрецедентной, всеобъемлющей коллекции
        книг, но теперь её заменила Система Акаша.
        Тем не менее, эта древняя библиотека по-прежнему является символом Сумеру, «Страны мудрости».</p>
    </div>
  </section>

  <section class="content-section">
    <h2 style="text-align: center; margin-bottom: 40px; color: #333;">Наши возможности</h2>

    <div class="features">
      <a href="books2.php" class="feature-card">
        <div class="feature-content">
          <h3>Собрание книг</h3>
          <p>Доступ к тысячам книг в различных форматах</p>
        </div>
      </a>

      <a href="borrow.php" class="feature-card">
        <div class="feature-content">
          <h3>Онлайн-бронирование</h3>
          <p>Заказывайте книги не выходя из дома</p>
        </div>
      </a>

      <a href="history.php" class="feature-card">
        <div class="feature-content">
          <h3>Персональная история</h3>
          <p>Отслеживайте свои путешествия по мирам</p>
        </div>
      </a>
    </div>
  </section>

  <style>
    /* Стили для выпадающей формы */
    .auth-dropdown {
      position: relative;
    }

    .auth-form-wrapper {
      position: absolute;
      top: 100%;
      right: 0;
      width: 300px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
      transform: translateY(10px);
      z-index: 1000;
    }

    .auth-dropdown:hover .auth-form-wrapper {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .auth-form {
      padding: 25px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      color: #333;
      font-size: 0.9em;
    }

    .form-group input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 1em;
    }

    .auth-submit {
      width: 100%;
      padding: 12px;
      background: #4CAF50;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .auth-submit:hover {
      background: #45a049;
    }

    .auth-links {
      margin-top: 15px;
      display: flex;
      justify-content: space-between;
      font-size: 0.9em;
    }

    .auth-links a {
      color: #4CAF50;
      text-decoration: none;
    }

    .auth-links a:hover {
      text-decoration: underline;
    }

    @media (max-width: 768px) {
      .auth-form-wrapper {
        width: 250px;
        right: -50px;
      }
    }

    .features-vertical {
      max-width: 800px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 20px;
      padding: 0 20px;
    }

    .feature-button {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 25px 30px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      text-decoration: none;
      color: #333;
      transition: all 0.3s ease;
      border: 2px solid transparent;
    }

    .feature-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
      border-color: #4CAF50;
    }

    .feature-content {
      flex: 1;
    }

    .feature-content h3 {
      color: #2c3e50;
      margin-bottom: 10px;
      font-size: 1.4em;
    }

    .feature-content p {
      color: #666;
      line-height: 1.6;
    }

    .arrow {
      font-size: 1.8em;
      color: #4CAF50;
      margin-left: 30px;
      transition: transform 0.3s;
    }

    .feature-button:hover .arrow {
      transform: translateX(5px);
    }

    @media (max-width: 768px) {
      .feature-button {
        padding: 20px;
        flex-direction: column;
        align-items: flex-start;
      }

      .arrow {
        margin-left: 0;
        margin-top: 15px;
        align-self: flex-end;
      }
    }
  </style>

  <section class="book-carousel">
    <div class="carousel-container">
      <div class="carousel-track">
        <?php for ($i = 0; $i < 5; $i++): ?>
          <div class="carousel-item">
            <img src="images/books/Sokol.png" alt="Сокол и Ворон">
            <div class="book-info">
              <h3>Сокол и ворон</h3>
              <p>Ульяна Черкасова</p>
            </div>
          </div>
          <div class="carousel-item">
            <img src="images/books/Code.jpg" alt="Сокол и Ворон">
            <div class="book-info">
              <h3>Code. Носители</h3>
              <p>Джон Маррс</p>
            </div>
          </div>
          <div class="carousel-item">
            <img src="images/books/НебожителиТ1.webp" alt="Сокол и Ворон">
            <div class="book-info">
              <h3>Благославение небожителей. Том 1</h3>
              <p>Мосян Тунсю</p>
            </div>
          </div>
          <div class="carousel-item">
            <img src="images/books/Killer.webp" alt="Сокол и Ворон">
            <div class="book-info">
              <h3>Внутри убийцы</h3>
              <p>Майк Омер</p>
            </div>
          </div>
          <div class="carousel-item">
            <img src="images/books/Nez.webp" alt="Сокол и Ворон">
            <div class="book-info">
              <h3>Любимый незнакомец</h3>
              <p>Эми Хармон</p>
            </div>
          </div>
          <div class="carousel-item">
            <img src="images/books/PDR.webp" alt="Сокол и Ворон">
            <div class="book-info">
              <h3>Потерянные девушки Рима</h3>
              <p>Донато Карризи</p>
            </div>
          </div>
          <div class="carousel-item">
            <img src="images/books/Razdor.webp" alt="Сокол и Ворон">
            <div class="book-info">
              <h3>Во главе раздора</h3>
              <p>Лия Арден</p>
            </div>
          </div>
          <div class="carousel-item">
            <img src="images/books/Two.webp" alt="Сокол и Ворон">
            <div class="book-info">
              <h3>Двойник Запада</h3>
              <p>Лия Арден</p>
            </div>
          </div>
        <?php endfor; ?>
      </div>
    </div>
  </section>

  <style>
    /* Основные стили секции */
    .book-carousel {
      padding: 80px 0 60px;
      background: #f8f9fa;
      overflow: hidden;
      position: relative;
    }

    /* Стили заголовка */
    .carousel-header {
      width: 100%;
      display: flex;
      justify-content: center;
      margin: 0 0 40px;
      padding: 0 20px;
      box-sizing: border-box;
    }

    .carousel-header h2 {
      font-size: 2.4em;
      color: #2c3e50;
      position: relative;
      padding-bottom: 15px;
      margin: 0;
      font-weight: 600;
      text-align: center;
      line-height: 1.3;
    }

    .carousel-header h2::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 4px;
      background: #4CAF50;
      border-radius: 2px;
      transition: width 0.3s ease;
    }

    .carousel-header h2:hover::after {
      width: 120px;
    }

    /* Стили карусели */
    .carousel-container {
      overflow: hidden;
      position: relative;
      width: 100%;
    }

    .carousel-track {
      display: flex;
      animation: carousel-scroll 60s linear infinite;
      gap: 30px;
      padding: 20px 0;
      width: max-content;
      will-change: transform;
    }

    /* Анимация */
    @keyframes carousel-scroll {
      0% {
        transform: translateX(0);
      }

      100% {
        transform: translateX(-50%);
      }
    }

    /* Стили элементов карусели */
    .carousel-item {
      flex: 0 0 200px;
      /* Уменьшенный размер */
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      background: white;
    }

    .carousel-item:hover {
      transform: scale(1.1) rotateZ(1deg);
      /* Увеличение при наведении */
      z-index: 2;
    }

    .carousel-item img {
      width: 100%;
      height: 280px;
      /* Уменьшенный размер */
      object-fit: cover;
      display: block;
    }

    /* Информация о книге */
    .book-info {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
      color: white;
      padding: 25px 20px;
      opacity: 0;
      transition: opacity 0.3s ease;
      backdrop-filter: blur(3px);
    }

    .carousel-item:hover .book-info {
      opacity: 1;
    }

    .book-info h3 {
      font-size: 1.2em;
      margin: 0 0 8px;
      color: #fff;
    }

    .book-info p {
      font-size: 0.9em;
      margin: 0;
      opacity: 0.9;
    }

    /* Пауза при наведении */
    .carousel-track:hover {
      animation-play-state: paused;
    }

    /* Адаптивность */
    @media (max-width: 1200px) {
      .carousel-item {
        flex: 0 0 180px;
        /* Уменьшенный размер */
      }

      .carousel-item img {
        height: 250px;
        /* Уменьшенный размер */
      }
    }

    @media (max-width: 768px) {
      .book-carousel {
        padding: 60px 0 40px;
      }

      .carousel-header h2 {
        font-size: 1.8em;
      }

      .carousel-item {
        flex: 0 0 160px;
        /* Уменьшенный размер */
      }

      .carousel-item img {
        height: 220px;
        /* Уменьшенный размер */
      }

      .book-info {
        padding: 15px;
      }

      .book-info h3 {
        font-size: 1em;
      }
    }

    @media (max-width: 480px) {
      .carousel-header h2 {
        font-size: 1.5em;
        padding-bottom: 10px;
      }

      .carousel-header h2::after {
        width: 60px;
        height: 3px;
      }

      .carousel-item {
        flex: 0 0 140px;
        /* Уменьшенный размер */
      }

      .carousel-item img {
        height: 200px;
        /* Уменьшенный размер */
      }
    }
  </style>

  <!-- Футер -->
  <style>
    .footer {
      background: #333;
      color: white;
      padding: 40px 20px;
      margin-top: auto;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 25px;
      font-family: Arial, sans-serif;
    }

    .social-links {
      display: flex;
      gap: 35px;
      flex-wrap: wrap;
      justify-content: center;
    }

    .social-link {
      color: white;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: transform 0.3s ease;
      padding: 8px 15px;
      border-radius: 8px;
    }

    .social-link:hover {
      transform: translateY(-3px);
      background: rgba(255, 255, 255, 0.1);
    }

    .social-icon {
      width: 28px;
      height: 28px;
    }

    .copyright {
      text-align: center;
      font-size: 0.9em;
      opacity: 0.8;
      margin-top: 15px;
    }

    @media (max-width: 480px) {
      .social-links {
        gap: 20px;
      }

      .social-link {
        font-size: 0.9em;
      }
    }
  </style>

  <footer class="footer">
    <div class="social-links">
      <a href="https://t.me/Symery" class="social-link" target="_blank">
        <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor">
          <path
            d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.14-.26.26-.429.26l.213-3.05 5.56-5.022c.24-.213-.054-.333-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z" />
        </svg>
        Telegram
      </a>

      <a href="https://vk.com/Symery" class="social-link" target="_blank">
        <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor">
          <path
            d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm6.344 16.163h-2.118v-3.278c0-.782-.167-1.316-1.014-1.316-.542 0-.913.364-1.063.715-.056.134-.07.32-.07.507v3.372h-2.12v-4.5c0-.782-.215-1.323-.753-1.323-.385 0-.657.25-.77.647-.04.114-.056.25-.056.387v3.79h-2.12V9.5h2.12v1.015c.31-.47.835-1.15 2.013-1.15 1.5 0 2.565.99 2.565 3.114v3.695z" />
        </svg>
        ВКонтакте
      </a>

      <a href="https://dzen.ru/Symery" class="social-link" target="_blank">
        <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor">
          <path
            d="M13.466 4.209c1.213-.388 2.443-.669 3.686-.842 1.247-.173 2.504-.239 3.772-.196 1.266.042 2.465.224 3.598.547.188.05.345.166.47.346.126.18.19.39.19.628v3.43c0 .393-.143.724-.428.993-.285.27-.64.404-1.064.404-.804 0-1.575-.07-2.313-.21a16.03 16.03 0 0 0-2.25-.367 20.97 20.97 0 0 0-2.52-.158c-.77 0-1.51.038-2.223.114a17.22 17.22 0 0 0-2.115.302c-.045.012-.12.03-.224.054l-.047.012-.024.006-.01.002h-.003l-.001.001h-.5v9.063h.5c.323 0 .613-.073.87-.218.258-.145.46-.345.607-.6a2.04 2.04 0 0 0-.87-.219h-.5v-.03c0-.01 0-.02.002-.03h.5z" />
        </svg>
        Дзен
      </a>
    </div>

    <div class="copyright">
      ©Сумеру, 2023<br>
      Все права защищены
    </div>
  </footer>
</body>

</html>