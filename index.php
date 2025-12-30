<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Varahi Bus – Booking Platform</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}

html,body{
  width:100%;
  height:100%;
}

body{
  min-height:100svh;
  background-image:url('https://www.appikr.com/blog/wp-content/uploads/2022/11/Build-Your-Own-Online-Bus-Ticket-Booking-Ap.jpg');
  background-repeat:no-repeat;
  background-position:center center;
  background-size:cover;
  background-attachment:fixed;
  color:#fff;
  display:flex;
  flex-direction:column;
}

.header{
  text-align:center;
  padding:60px 12px 25px;
  text-shadow:0 2px 6px rgba(0,0,0,0.6);
}
.header h1{
  font-size:2.7rem;
  font-weight:900;

}
.header p{
  font-size:1.2rem;
  margin-top:8px;
}

.buttons{
  display:flex;
  justify-content:center;
  gap:25px;
  margin-top:60px;
  flex-wrap:wrap;
}

.btn{
  width:150px;
  height:150px;
  border-radius:18px;
  border:none;
  cursor:pointer;
  font-weight:800;
  font-size:30px;
  background:rgba(255,255,255,0.95);
  transition:0.3s;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  color:green;
}
.btn:hover{
  transform:scale(1.06);
}
.btn img{
  width:75px;
  height:75px;
  margin-bottom:10px;
}

#chatSupport{
  position:fixed;
  bottom:20px;
  left:20px;
  z-index:1000;
cursor:pointer;
}

.icon-circle{
  width:60px;
  height:60px;
  border-radius:50%;
  background:#fff;
  display:flex;
  align-items:center;
  justify-content:center;
}
.icon-circle img{
  width:32px;
}

#supportBox{
  width:260px;
  background:#fff;
  color:#222;
  border-radius:12px;
  position:fixed;
  bottom:95px;
  left:18px;
  display:none;
  box-shadow:0 0 12px rgba(0,0,0,0.3);
  z-index:1000;
}

.chat-header{
  background:#222;
  color:#fff;
  padding:9px;
  font-size:0.9rem;
  text-align:center;
  border-radius:12px 12px 0 0;
}
.chat-body{
  padding:10px;
  font-size:0.9rem;
  line-height:1.6;
}
.chat-body a{
  color:#005c99;
  text-decoration:none;
  font-weight:600;
}
@keyframes moveBus{
  0%{ left:-80px; }
  100%{ left:100%; }
}

footer{
  margin-top:auto;
  text-align:center;
  padding:15px 10px;
  font-size:0.8rem;
  background:rgba(0,0,0,0.55);
}

@media (max-width:768px){
  .header h1{font-size:4rem;}
  .btn{width:140px;height:140px;}
  .btn img{width:58px;height:58px;}
}

@media (max-width:480px){
html, body{
  width:100%;
  height:100%;
}

body{
  width:100vw;
  height:100svh;
  background-image:url('https://www.appikr.com/blog/wp-content/uploads/2022/11/Build-Your-Own-Online-Bus-Ticket-Booking-Ap.jpg');
  background-repeat:no-repeat;
  background-position:center center;
  background-size:cover;
  background-attachment:scroll;
  color:#fff;
  display:flex;
  flex-direction:column;
}
.buttons{
margin-top:100px;
}


  .header h1{font-size:1.7rem;}
  .header p{font-size:0.9rem;}

  .btn{width:125px;height:105px;font-size:0.9rem;}
  .btn img{width:50px;height:50px;}

  #supportBox{
    width:90%;
    left:50%;
    transform:translateX(-50%);
  }

  #chatSupport{
    bottom:15px;
    left:15px;
  }
}

</style>
</head>

<body>

<section class="header">
  <h1>Varahi Bus Booking Platform</h1>
  <p>Book. Travel. Experience Comfort.</p>
</section>

<div class="buttons">
  <button class="btn" onclick="location.href='admin/login.php'">
    <img src="https://cdn-icons-png.flaticon.com/512/456/456212.png">
    Admin
  </button>

  <button class="btn" onclick="location.href='customer/login.php'">
    <img src="https://cdn-icons-png.flaticon.com/512/1077/1077012.png">
    Traveller
  </button>
</div>

<div id="chatSupport">
  <div class="icon-circle">
    <img src="https://cdn-icons-png.flaticon.com/512/561/561127.png">
  </div>
</div>

<div id="supportBox">
  <div class="chat-header">Support Contact</div>
  <div class="chat-body">
    📞 <b>Mobile:</b> <a href="tel:+917569398385">7569398385</a><br><br>
    📧 <b>Email:</b> <a href="mailto:rajutandyala369@gmail.com">rajutandyala369@gmail.com</a><br><br>
    💬 <b>WhatsApp:</b><br>
    <a href="https://wa.me/917569398385" target="_blank">Tap to Chat on WhatsApp</a>
  </div>
</div>
<footer>
  © 2024 Varahi Bus Booking Platform | Tandyala Raju
</footer>

<script>
const chatBtn = document.getElementById("chatSupport");
const supportBox = document.getElementById("supportBox");
chatBtn.addEventListener("click", (e) => {
  e.stopPropagation();
  supportBox.style.display =
    supportBox.style.display === "block" ? "none" : "block";
});
supportBox.addEventListener("click", (e) => {
  e.stopPropagation();
});
document.addEventListener("click", () => {
  supportBox.style.display = "none";
});
</script>

</body>
</html>