<?php
session_start();
header("Content-Type: text/plain");

$user = trim($_POST['message']);

if (!isset($_SESSION['chat'])) {
    $_SESSION['chat'] = [];
}

$_SESSION['chat'][] = ["user" => $user];

// Real support-like dynamic flow
$last = strtolower($user);
$reply = "";

// === Real-Time Conversational Flow ===
if ($last == "hi" || $last == "hello") {
    $reply = "Hello 👋 Welcome to Varahi Support. How can I assist you?";
}
else if (strpos($last, "book") !== false) {
    $reply = "Sure! Please share your travel date 🗓️";
}
else if (preg_match("/\b\d{2}-\d{2}-\d{4}\b/", $last)) {
    $reply = "Thank you. Now please provide starting point & destination 🚍";
}
else if (strpos($last, "guntur") !== false) {
    $reply = "Perfect! How many seats would you like to book?";
}
else if (is_numeric($last)) {
    $reply = "Seats confirmed! Would you like to proceed with payment?";
}
else if (strpos($last, "payment") !== false) {
    $reply = "Payments can be made via UPI, Card & Wallet. Shall I open payment page?";
}
else {
    $reply = "I understand 👍 Please tell me more so I can assist further.";
}

$_SESSION['chat'][] = ["bot" => $reply];

echo $reply;
?>
