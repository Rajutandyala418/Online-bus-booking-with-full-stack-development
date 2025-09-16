<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome to Varahi Bus</title>
  <style>
    body, html {
      margin: 0;
      padding: 0;
      height: 100%;
      width: 100%;
      font-family: Arial, sans-serif;
      overflow: hidden;
      background: linear-gradient(to top, #cceeff, #87ceeb);
    }

    .content {
      position: absolute;
      top: 25%;
      left: 50%;
      transform: translateX(-50%);
      text-align: center;
      color: #fff;
    }

    .content h1 {
      font-size: 3rem;
      font-weight: bold;
      color: #FFD54F;
      margin-bottom: 15px;
    }

    .content p {
      font-size: 1.3rem;
      font-style: italic;
      margin-bottom: 30px;
    }

    .buttons {
      position: absolute;
      top: 20px;
      right: 20px;
      display: flex;
      gap: 20px;
    }

    .btn {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      border: none;
      cursor: pointer;
      color: white;
      font-weight: bold;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      transition: transform 0.3s ease, opacity 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      text-align: center;
    }

    .btn-admin { background: #e74c3c; }
    .btn-traveller { background: #3498db; }

    .btn:hover {
      transform: scale(1.15);
      opacity: 0.9;
    }

    .chatbot {
      width: 120px;
      position: absolute;
      bottom: 20px;
      right: 20px;
      cursor: pointer;
      transition: transform 0.3s ease;
    }
    .chatbot:hover { transform: scale(1.1); }

    .support {
      display: none;
      position: absolute;
      bottom: 150px;
      right: 40px;
      background: white;
      padding: 15px 20px;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      text-align: left;
      font-size: 0.95rem;
      animation: fadeIn 0.5s ease;
    }

    .support h3 { margin: 0 0 10px 0; font-size: 1rem; color: #333; }
    .support p { margin: 8px 0; color: #333; }

    .support a {
      text-decoration: none;
      color: #333;
    }
    .support a:hover { color: #3498db; font-weight: bold; }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

  <div class="content">
    <h1>Welcome to Varahi Bus</h1>
    <p>"Travel smart, travel safe. Book your bus journey with ease!"</p>
  </div>

  <div class="buttons">
    <button class="btn btn-admin" onclick="location.href='admin/login.php'">Admin</button>
    <button class="btn btn-traveller" onclick="location.href='customer/login.php'">Traveller</button>
  </div>

  <!-- Support box -->
  <div class="support" id="supportBox">
    <h3>Support</h3>
    <p>📞 <a href="https://wa.me/917569398385" target="_blank">7569398385</a></p>
    <p>✉️ <a href="mailto:y22cm171@rvrjc.ac.in">y22cm171@rvrjc.ac.in</a></p>
    <p class="chat">💬 <a href="https://wa.me/917569398385" target="_blank">Chat Now</a></p>
  </div>

  <!-- Chatbot -->
  <img src="https://as2.ftcdn.net/v2/jpg/05/65/06/85/1000_F_565068563_jSzYovhlcrwcVTOm05akpqVdZXdoOaNE.jpg"
       class="chatbot" alt="Chatbot" onclick="toggleSupport()">

  <script>
    function toggleSupport() {
      const box = document.getElementById("supportBox");
      box.style.display = (box.style.display === "block") ? "none" : "block";
    }
  </script>

</body>
</html>
