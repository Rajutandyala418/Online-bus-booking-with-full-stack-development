<?php
include('./include/db_connect.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Varahi Bus Support Chat</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

<style>
body { font-family: 'Poppins', sans-serif; background: #f1f1f1; margin: 0; padding: 20px; }
.container { max-width: 900px; margin: auto; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.15); }
h2 { text-align: center; font-weight: 700; color: #007bff; margin-bottom: 20px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; font-size: 14px; }
th { background: #007bff; color: white; }
.view-btn { background: #007bff; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; display: inline-block; }
.view-btn:hover { background: #0056b3; }
.unread { background: #ff3b3b; color: white; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: bold; }
.count-normal { background: #28a745; color: white; padding: 4px 8px; border-radius: 6px; font-size: 12px; }
@media (max-width: 768px) {
table, thead, tbody, th, td, tr { display: block; }
thead tr { display: none; }
tr { margin-bottom: 14px; background: white; border-radius: 10px; padding: 12px; box-shadow: 0 0 6px rgba(0,0,0,0.1); }
td { text-align: right; padding-left: 50%; position: relative; }
td::before { content: attr(data-label); position: absolute; left: 12px; font-weight: bold; text-align: left; }
.view-btn { width: 100%; margin-top: 8px; }
}
</style>
</head>
<body>

<div class="container">
    <h2>Varahi Bus Support Chat</h2>

    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Username</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Messages</th>
            <th>View</th>
        </tr>
        </thead>
        <tbody id="chatTableBody">
            <!-- Dynamic load here -->
        </tbody>
    </table>
</div>

<script>
let lastUnreadCount = 0;

function loadChatList() {
    fetch("load_chat_list.php")
        .then(res => res.text())
        .then(html => {
            document.getElementById("chatTableBody").innerHTML = html;
            const unreadBadges = document.querySelectorAll(".unread");
            let currentUnread = unreadBadges.length;

            // sound + popup only if new unread arrives
            if (currentUnread > lastUnreadCount) {

                document.getElementById("notifySound").play();

                if (Notification.permission === "granted") {
                    new Notification("📩 New Support Message", {
                        body: "You have an unread customer message.",
                        icon: "https://cdn-icons-png.flaticon.com/512/4712/4712028.png"
                    });
                }
            }

            lastUnreadCount = currentUnread;
        });
}

// Request permission once
if (Notification.permission !== "granted") {
    Notification.requestPermission();
}

setInterval(loadChatList, 1500);
loadChatList();

// enable sound on first click (Chrome autoplay fix)
document.addEventListener("click", () => {
    document.getElementById("notifySound").play().catch(()=>{});
}, { once: true });
</script>

<audio id="notifySound" src="notify.mp3" preload="auto"></audio>


</body>
</html>
