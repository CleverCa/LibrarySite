<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ru">

<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Контакты</title>
 <style>
  body {
   display: flex;
   flex-direction: column;
   min-height: 100vh;
   margin: 0;
   font-family: Arial, sans-serif;
  }

  .header {
   background: #4CAF50;
   padding: 15px 20px;
   display: flex;
   justify-content: space-between;
   align-items: center;
   box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  }

  .header a {
   color: white;
   text-decoration: none;
   margin: 0 15px;
   transition: opacity 0.3s;
  }

  .header a:hover {
   opacity: 0.8;
  }

  .logo {
   font-weight: bold;
   font-size: 1.2em;
  }

  .content {
   flex: 1;
   padding: 20px 0;
  }

  .container {
   max-width: 800px;
   margin: 20px auto;
   padding: 20px;
   display: flex;
   gap: 30px;
  }

  .about-card {
   flex: 1;
   padding: 25px;
   background: #f8f9fa;
   border-radius: 10px;
   box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }

  .contacts__textarea {
   width: 100%;
   padding: 12px;
   border: 1px solid #ddd;
   border-radius: 8px;
   margin: 10px 0;
  }

  .button {
   background: #4CAF50;
   color: white;
   padding: 12px 25px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   transition: background 0.3s;
  }

  .button:hover {
   background: #45a049;
  }

  .alert {
   padding: 15px;
   margin: 20px 0;
   border-radius: 5px;
  }

  .alert-success {
   background: #d4edda;
   color: #155724;
  }

  .alert-error {
   background: #f8d7da;
   color: #721c24;
  }

  .footer {
   background: #333;
   color: white;
   padding: 25px 20px;
   margin-top: auto;
   display: flex;
   flex-direction: column;
   align-items: center;
   gap: 15px;
  }

  .social-links {
   display: flex;
   gap: 25px;
  }

  .social-links a {
   color: white;
   text-decoration: none;
   display: flex;
   align-items: center;
   gap: 8px;
   transition: opacity 0.3s;
  }

  .social-links a:hover {
   opacity: 0.8;
  }
 </style>
</head>

<body>
 <div class="header">
  <div class="left-header">
   <a href="index.php" class="logo">Сумеру</a>
   <a href="index.php">Главная</a>
  </div>
 </div>

 <div class="content">
  <div class="container">
   <div class="about-card">
    <h1>Контактная информация</h1>
    <p>Телефон: 8(800)132-32-54</p>
    <p>Email: keyboardgeeks@mail.com</p>
    <p>Адрес: 198261, Санкт-Петербург, Лучший офис, д.1</p>
    <h3>Часы работы</h3>
    <p>Пн-Пт: 10:00 - 20:00</p>
    <p>Сб-Вс: 11:00 - 18:00</p>
   </div>

   <div class="about-card">
    <?php
    $showForm = true;
    if (isset($_POST["sendComm"])) {
     $to = "kostrm030102@gmail.com";
     $subject = "Сообщение с сайта Сумеру";
     $message = "Сообщение от: " . $_POST['email'] . "\n\n" . $_POST["sendComm"];
     $headers = "From: webmaster@klava-rf.ru\r\n";

     if (mail($to, $subject, $message, $headers)) {
      echo '<div class="alert alert-success">Сообщение успешно отправлено!</div>';
      $showForm = false;
     } else {
      echo '<div class="alert alert-error">Ошибка при отправке сообщения!</div>';
     }
    }
    ?>

    <?php if ($showForm): ?>
     <form method="post">
      <h2>Форма обратной связи</h2>
      <div>
       <input type="email" name="email" placeholder="Ваш email" required class="contacts__textarea">
      </div>
      <div>
       <textarea name="sendComm" class="contacts__textarea" placeholder="Ваше сообщение" rows="5" required></textarea>
      </div>
      <button type="submit" class="button">Отправить сообщение</button>
     </form>
    <?php endif; ?>
   </div>
  </div>
 </div>

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
      d="M13.466 4.209c1.213-.388 2.443-.669 3.686-.842 1.247-.173 2.504-.239 3.772-.196 1.266.042 2.465.224 3.598.547.188.05.345.166.47.346.126.18.19.39.19.628v3.43c0 .393-.143.724-.428.993-.285.27-.64.404-1.064.404-.804 0-1.575-.07-2.313-.21a16.03 16.03 0 0 0-2.25-.367 20.97 20.97 0 0 0-2.52-.158c-.77 0-1.51.038-2.223.114a17.22 17.22 0 0 0-2.115.302c-.045.012-.12.03-.224.054l-.047.012-.024.006-.01.002h-.003l-.001.001h-.5v9.063h.5c.323 0 .613-.073.87-.218.258-.145.46-.345.607-.6.148-.256.222-.54.222-.853V5.915c0-.314-.074-.6-.222-.856-.147-.256-.35-.456-.608-.6a2.04 2.04 0 0 0-.87-.219h-.5v-.03c0-.01 0-.02.002-.03h.5z" />
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