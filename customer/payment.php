<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../include/db_connect.php');

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'User';

// --- Fetch booking and traveller details from GET parameters ---
$booking_id      = $_GET['booking_id'] ?? rand(1000, 9999);
$schedule_id     = $_GET['schedule_id'] ?? 0;
$bus_name        = $_GET['bus_name'] ?? 'Express Travels';
$bus_number      = $_GET['bus_number'] ?? 'TN-10-1234';
$route           = $_GET['route'] ?? 'Chennai → Bangalore';
$travel_date     = $_GET['travel_date'] ?? '2025-07-30';
$departure       = $_GET['departure'] ?? '08:00 AM';
$arrival         = $_GET['arrival'] ?? '02:00 PM';
$seats           = $_GET['seats'] ?? 'A1, A2';
$seat_type       = $_GET['seat_type'] ?? 'Seater';

// Traveller details
$traveller_name  = $_GET['name'] ?? 'John Doe';
$traveller_email = $_GET['email'] ?? 'john@example.com';
$traveller_phone = $_GET['phone'] ?? '9876543210';

// Fare and GST calculation
$fare = (float) ($_GET['fare'] ?? 0);
$base_fare = round($fare / 1.05, 2);
$gst = round($fare - $base_fare, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #111;
            color: #fff;
            overflow-x: hidden;
        }
        .bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        .top-nav {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 20px;
            padding: 20px 30px;
            background: rgba(0, 0, 0, 0.6);
            z-index: 2000;
        }
        .welcome-text { color: #0ff; font-weight: bold; }
        .dashboard-btn {
            text-decoration: none;
            background: rgba(0,0,0,0.5);
            padding: 10px 15px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            transition: background 0.3s;
        }
        .dashboard-btn:hover { background: rgba(0,0,0,0.8); }
        .profile-btn {
            background: #ffde59;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-weight: bold;
            cursor: pointer;
            font-size: 1rem;
            color: #111;
        }
        .dropdown {
            position: absolute;
            top: 60px;
            right: 30px;
            background: rgba(0,0,0,0.8);
            border-radius: 8px;
            display: none;
            flex-direction: column;
            padding: 10px;
            min-width: 180px;
            z-index: 2000;
        }
        .dropdown a {
            color: #0ff;
            padding: 10px;
            text-decoration: none;
            text-align: left;
            border-radius: 5px;
            display: block;
            transition: background 0.3s;
        }
        .dropdown a:hover { background: rgba(255,255,255,0.2); color: #fff; }

        .main-container {
            display: grid;
            grid-template-columns: 30% 70%;
            gap: 20px;
            min-height: 100vh;
            padding: 140px 40px 60px 40px;
        }
        .left-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .box {
            background: rgba(0, 0, 0, 0.6);
            padding: 20px;
            border-radius: 10px;
        }
        .box h2 { color: #ffde59; margin-bottom: 10px; }
        .right-section {
            background: rgba(0, 0, 0, 0.6);
            padding: 20px;
            border-radius: 10px;
            overflow-y: auto;
        }
        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .payment-methods input, .payment-methods button {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: none;
            font-size: 1rem;
        }
        .payment-methods input { background: #fff; color: #000; }
        .btn {
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: #fff;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover { transform: scale(1.05); }
        .btn:disabled { background: #444; cursor: not-allowed; }
        .status-message { margin-top: 10px; font-size: 1rem; color: #0ff; }

        .timer {
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.2rem;
            background: rgba(0,0,0,0.7);
            padding: 10px 20px;
            border-radius: 8px;
            color: #ffde59;
            font-weight: bold;
            z-index: 2100;
        }
    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="videos/bus.mp4" type="video/mp4">
</video>

<div class="top-nav">
    <div class="welcome-text">Welcome, <?php echo htmlspecialchars($username); ?></div>
    <a class="dashboard-btn" href="#" onclick="cancelAndRedirect()">Dashboard</a>
    <button class="profile-btn" id="profileBtn"><?php echo strtoupper(substr($username, 0, 1)); ?></button>
    <div class="dropdown" id="profileMenu">
        <a href="profile.php">profile details</a>
        <a href="booking_history.php">View Bookings</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="timer" id="timer">08:00</div>

<div class="main-container">
    <div class="left-section">
        <div class="box">
            <h2>Bus Details</h2>
            <p><strong>Bus:</strong> <?php echo htmlspecialchars($bus_name); ?> (<?php echo htmlspecialchars($bus_number); ?>)</p>
            <p><strong>Route:</strong> <?php echo htmlspecialchars($route); ?></p>
            <p><strong>Travel Date:</strong> <?php echo htmlspecialchars($travel_date); ?></p>
            <p><strong>Departure:</strong> <?php echo htmlspecialchars($departure); ?></p>
            <p><strong>Arrival:</strong> <?php echo htmlspecialchars($arrival); ?></p>
            <p><strong>Seats:</strong> <?php echo htmlspecialchars($seats); ?></p>
            <p><strong>Seat Type:</strong> <?php echo htmlspecialchars($seat_type); ?></p>
        </div>
        <div class="box">
            <h2>Traveller Details</h2>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($traveller_name); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($traveller_email); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($traveller_phone); ?></p>
        </div>
    </div>

    <div class="right-section">
        <h2>Payment</h2>
        <p><strong>Base Fare:</strong> ₹<?php echo htmlspecialchars($base_fare); ?></p>
        <p><strong>GST (5%):</strong> ₹<?php echo htmlspecialchars($gst); ?></p>
        <p><strong>Total Amount:</strong> ₹<?php echo htmlspecialchars($fare); ?></p>

        <div class="payment-methods">
            <h3>UPI Payment</h3>
            <input type="text" id="upiId" placeholder="Enter UPI ID (e.g., name@upi)">
            <button class="btn" onclick="selectPayment('UPI'); verifyUPI()">Verify UPI</button>
            <button class="btn" onclick="selectPayment('UPI'); generateQR()">Generate QR</button>

            <h3>Credit/Debit Card</h3>
            <input type="text" id="cardNumber" placeholder="Card Number" maxlength="16">
            <input type="text" id="cardName" placeholder="Card Holder Name">
            <input type="text" id="cardExpiry" placeholder="MM/YY" maxlength="5">
            <input type="text" id="cardCVV" placeholder="CVV" maxlength="3">

            <button class="btn" id="payBtn" onclick="completePayment()" disabled>Pay Now</button>
            <div class="status-message" id="statusMsg"></div>
        </div>
    </div>
</div>

<script>
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');
    profileBtn.addEventListener('click', () => {
        profileMenu.style.display = (profileMenu.style.display === 'flex') ? 'none' : 'flex';
    });

    let selectedPaymentMethod = '';

    function selectPayment(method) {
        selectedPaymentMethod = method;
    }

    // Timer persistence using sessionStorage
    let timeLeft = sessionStorage.getItem('paymentTimer') 
                   ? parseInt(sessionStorage.getItem('paymentTimer')) 
                   : 8 * 60;

    const timerElement = document.getElementById('timer');
    const timerInterval = setInterval(() => {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerElement.textContent = `${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`;
        timeLeft--;
        sessionStorage.setItem('paymentTimer', timeLeft);
        if (timeLeft < 0) {
            clearInterval(timerInterval);
            sessionStorage.removeItem('paymentTimer');
            disablePayment();
            alert('Payment time expired! Redirecting...');
            window.location.href = 'payment_failed.php?reason=timeout';
        }
    }, 1000);

    function disablePayment() {
        document.getElementById('payBtn').disabled = true;
        document.querySelectorAll('.payment-methods input, .payment-methods button').forEach(el => el.disabled = true);
    }

    function verifyUPI() {
        const upi = document.getElementById('upiId').value.trim();
        if (/^[\w.-]+@[\w.-]+$/.test(upi)) {
            document.getElementById('statusMsg').textContent = "UPI ID verified. You can proceed to pay.";
            document.getElementById('payBtn').disabled = false;
        } else {
            document.getElementById('statusMsg').textContent = "Enter a valid UPI ID.";
        }
    }

    function generateQR() {
        document.getElementById('statusMsg').textContent = "QR Code generated for payment.";
        document.getElementById('payBtn').disabled = false;
    }

    function completePayment() {
        const btn = document.getElementById('payBtn');
        btn.textContent = "Processing...";
        btn.disabled = true;
        document.getElementById('statusMsg').textContent = "Processing Payment...";

        setTimeout(() => {
            let success = Math.random() > 0.2;
            if (success) {
                sessionStorage.removeItem('paymentTimer');
                savePaymentSession();
            } else {
                alert('Payment Failed! Redirecting...');
                sessionStorage.removeItem('paymentTimer');
                window.location.href = 'payment_failed.php?reason=failed';
            }
        }, 2000);
    }

    function savePaymentSession() {
        fetch('save_payment_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                bus_details: {
                    bus_name: "<?php echo htmlspecialchars($bus_name); ?>",
                    bus_number: "<?php echo htmlspecialchars($bus_number); ?>",
                    route: "<?php echo htmlspecialchars($route); ?>",
                    travel_date: "<?php echo htmlspecialchars($travel_date); ?>",
                    departure: "<?php echo htmlspecialchars($departure); ?>",
                    arrival: "<?php echo htmlspecialchars($arrival); ?>",
                    seats: "<?php echo htmlspecialchars($seats); ?>",
                    seat_type: "<?php echo htmlspecialchars($seat_type); ?>",
                    fare: "<?php echo htmlspecialchars($fare); ?>",
                    schedule_id: "<?php echo htmlspecialchars($schedule_id); ?>"
                },
                traveller_details: {
                    name: "<?php echo htmlspecialchars($traveller_name); ?>",
                    email: "<?php echo htmlspecialchars($traveller_email); ?>",
                    phone: "<?php echo htmlspecialchars($traveller_phone); ?>"
                },
                payment_method: selectedPaymentMethod
            })
        }).then(() => {
            window.location.href = 'booking_success.php';
        });
    }

    const cardInputs = ['cardNumber', 'cardName', 'cardExpiry', 'cardCVV'];
    cardInputs.forEach(id => document.getElementById(id).addEventListener('input', () => {
        const filled = cardInputs.every(id => document.getElementById(id).value.trim() !== '');
        if (filled) selectPayment('CARD');
        document.getElementById('payBtn').disabled = !filled;
    }));

    function cancelAndRedirect() {
        // Directly go to payment_failed page without alert
        sessionStorage.removeItem('paymentTimer');
        window.location.href = 'payment_failed.php?reason=dashboard_click';
    }

    // Show alert on browser back arrow
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.pushState(null, null, location.href);
        alert("⚠ Back navigation will cancel your payment!");
        sessionStorage.removeItem('paymentTimer');
        window.location.href = 'payment_failed.php?reason=back_navigation';
    };
</script>
</body>
</html>
