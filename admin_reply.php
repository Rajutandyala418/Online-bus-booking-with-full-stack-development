<?php
session_start();
include('./include/db_connect.php');

$username = $_GET['username'] ?? '';

$conn->query("UPDATE support_chat 
              SET is_read=1, msg_status='seen', seen_time=NOW() 
              WHERE username='$username' AND sender='user' AND message!='typing...'");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Chat with <?php echo $username; ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body{
    font-family:'Poppins',sans-serif;
    background:#f1f1f1;
    margin:0;
    padding:0;
}
.chat-container{
    width:450px;
    margin:40px auto;
    background:white;
    border-radius:12px;
    box-shadow:0 0 10px rgba(0,0,0,0.2);
    overflow:hidden;
}
.chat-header{
    background:#007bff;
    padding:15px;
    color:white;
    font-weight:bold;
    text-align:center;
    position:relative;
}
.back-btn{
    position:absolute;
    left:10px;
    top:10px;
    background:white;
    color:#007bff;
    border:none;
    padding:6px 10px;
    border-radius:6px;
    font-size:13px;
    cursor:pointer;
    font-weight:bold;
}
.chat-body{
    height:430px;
    overflow-y:auto;
    padding:15px;
    background:#e8e8e8;
}
.message{
    margin:8px 0;
    padding:12px;
    border-radius:8px;
    font-size:14px;
    max-width:75%;
}
.user-msg{
    background:white;
    color:black;
    border-left:4px solid #007bff;
}
.admin-msg{
    background:#007bff;
    color:white;
    margin-left:auto;
    border-right:4px solid white;
}
.timestamp{
    font-size:10px;
    color:#333;
    margin-top:3px;
}
.typing-box{
    text-align:center;
    padding:6px;
    color:gray;
    font-size:12px;
}
.chat-footer{
    display:flex;
    gap:10px;
    padding:10px;
    background:#ddd;
    border-top:1px solid #bbb;
}
.chat-footer input{
    flex:1;
    padding:10px;
    border-radius:6px;
    border:none;
}
.chat-footer button{
    background:#007bff;
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:6px;
    cursor:pointer;
    font-weight:bold;
}
@media(max-width:550px){
    .chat-container{ width:95%; }
    .chat-body{ height:380px; }
    .chat-footer input{ padding:8px; }
    .chat-footer button{ padding:8px 14px; }
}
</style>
</head>
<body>

<div class="chat-container">

<div class="chat-header">
<button class="back-btn" onclick="window.location='admin_chat.php'">&#8592; Back</button>
Chat with <?php echo $username; ?>
<div id="statusBar" style="font-size:12px;margin-top:6px;"></div>
</div>

<div class="chat-body" id="chatBody"></div>

<div id="typingIndicator" style="text-align:center;display:none;color:gray;font-size:12px;">
✍️ typing...
</div>

<div class="chat-footer">
<input type="text" id="replyMsg" placeholder="Type a message...">
<button onclick="sendReply()">Send</button>
</div>

<audio id="sendSound" src="send.mp3" preload="auto"></audio>
<audio id="receiveSound" src="receive.mp3" preload="auto"></audio>

</div>

<script>
// ✔ Correct check mark formatting
function renderTicks(status){
    if(status=="sent") return "✓";
    if(status=="delivered") return "✓✓";
    if(status=="seen") return "<span style='color:#2196f3;'>✓✓</span>";
}

// ✔ Live message load + receive sound fix
let lastMessageCount = 0;

function loadChat(){
fetch("fetch_chat_admin.php?username=<?php echo $username;?>")
.then(r=>r.json())
.then(d=>{
    let box=document.getElementById("chatBody");
    box.innerHTML="";
    let typing=false;

    d.forEach(m=>{
        if(m.sender=='user' && m.message=='typing...'){
            typing=true;
        } else {
            box.innerHTML+=`
            <div class="message ${m.sender=='admin'?'admin-msg':'user-msg'}">
                ${m.message}
                <div class="timestamp">${m.timestamp} ${m.sender=='admin'?renderTicks(m.msg_status):""}</div>
            </div>`;
        }
    });

    document.getElementById("typingIndicator").style.display = typing ? 'block' : 'none';
    box.scrollTop = box.scrollHeight;

    // ✔ receive sound only when new user message arrives
    if (d.length > lastMessageCount && !typing) {
        document.getElementById("receiveSound").play();
    }

    lastMessageCount = d.length;
});
}

// ✔ Play typing ONLY while typing, not focus
function checkStatus(){
fetch("fetch_user_status.php?username=<?php echo $username;?>")
.then(r=>r.json())
.then(d=>{
    if(d.typing){
        statusBar.innerHTML="✍️ Typing...";
    } else if(d.online){
        statusBar.innerHTML="🟢 Online";
    } else {
        statusBar.innerHTML="Last seen: "+d.last_seen;
    }
});
}

// ✔ Admin send
function sendReply(){
let reply=document.getElementById("replyMsg").value;
if(!reply.trim()) return;

document.getElementById("sendSound").play();

fetch("send_admin_reply.php",{
method:"POST",
headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:`username=<?php echo $username;?>&message=${reply}`
});
document.getElementById("replyMsg").value="";
loadChat();
}

setInterval(loadChat,1000);
setInterval(checkStatus,1000);
loadChat();
checkStatus();

// ✔ first click enables audio auto-play
document.addEventListener("click", () => {
    document.getElementById("receiveSound").play().catch(()=>{});
    document.getElementById("sendSound").play().catch(()=>{});
}, { once:true });

</script>

</body>
</html>