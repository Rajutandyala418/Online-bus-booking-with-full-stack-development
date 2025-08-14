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

// Ensure username in session
if (!isset($_SESSION['username'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($username);
    if ($stmt->fetch()) {
        $_SESSION['username'] = $username;
    } else {
        $_SESSION['username'] = "User";
    }
    $stmt->close();
}

$username = htmlspecialchars($_SESSION['username']); // Safe output

// Retrieve booking data from booking_form.php
$schedule_id    = $_GET['schedule_id'] ?? '';
$bus_name       = $_GET['bus_name'] ?? '';
$bus_number     = $_GET['bus_number'] ?? '';
$route          = $_GET['route'] ?? '';
$travel_date    = $_GET['travel_date'] ?? '';
$fare           = $_GET['fare'] ?? 0;
$departure      = $_GET['departure'] ?? '';
$arrival        = $_GET['arrival'] ?? '';
$selected_seats = $_GET['seats'] ?? '';

// Determine seat type
$seat_type = 'Seater';
$seat_list = array_filter(array_map('trim', explode(',', $selected_seats)));
foreach ($seat_list as $seat) {
    if (stripos($seat, 'S') !== false || stripos($seat, 'U') !== false) {
        $seat_type = 'Sleeper';
        break;
    }
}

// Calculate fare with GST
$total_seats = count($seat_list);
$base_total = $total_seats * $fare;
$gst = round($base_total * 0.05, 2); // 5% GST
$total_amount = $base_total + $gst;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Details</title>
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #111;
            color: #fff;
            overflow-x: hidden;
        }
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background: rgba(0,0,0,0.7);
        }
        .profile-section {
            position: relative;
            display: flex;
            align-items: center;
        }
        .welcome-text {
            color: #0ff;
            margin-right: 10px;
        }
        .profile-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ffde59;
            color: #111;
            font-weight: bold;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
        }
        .dropdown {
            position: absolute;
            right: 0;
            top: 50px;
            background: rgba(0,0,0,0.8);
            border-radius: 8px;
            display: none;
            flex-direction: column;
            padding: 10px;
            min-width: 180px;
            z-index: 100;
        }
        .dropdown a {
            color: #0ff;
            padding: 10px;
            text-decoration: none;
            border-radius: 5px;
            display: block;
            transition: background 0.3s;
        }
        .dropdown a:hover {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }
        .container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            padding: 30px;
        }
        .box {
            background: rgba(0, 0, 0, 0.6);
            padding: 20px;
            border-radius: 10px;
            width: 45%;
            min-width: 320px;
            margin: 10px;
        }
        h2 {
            color: #ffde59;
            text-align: center;
        }
        label {
            display: block;
            margin-top: 10px;
            font-size: 0.9rem;
            color: #ffde59;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin: 5px 0 15px 0;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
        }
        input, select {
            background: #fff;
            color: #333;
        }
        button {
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: #fff;
            cursor: pointer;
            font-size: 1.1rem;
        }
        button:hover {
            background: linear-gradient(90deg, #dd2476, #ff512f);
        }
        #backBtn {
            display: block;
            margin: 20px auto;
            padding: 15px 30px;
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
            text-decoration: none;
            text-align: center;
        }
        #backBtn:hover {
            transform: scale(1.05);
        }
        #couponSection {
            margin-top: 15px;
            border-top: 1px solid #444;
            padding-top: 15px;
        }
        #couponMsg {
            margin-top: 5px;
            font-weight: bold;
            color: #0f0;
        }
        #couponMsg.error {
            color: #f44;
        }

        /* Modal Popup */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #222;
            padding: 20px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
        }
        .modal-content h3 {
            text-align: center;
            color: #ffde59;
        }
        .modal-content table {
            width: 100%;
            margin: 10px 0;
            border-collapse: collapse;
        }
        .modal-content th, .modal-content td {
            border: 1px solid #555;
            padding: 8px;
            text-align: left;
        }
        .modal-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        .modal-buttons button {
            width: 48%;
        }
    </style>
</head>
<body>
<div class="top-nav">
    <div class="logo">🚌 BusBooking</div>
    <div class="profile-section">
        <span class="welcome-text">Welcome, <?php echo $username; ?></span>
        <button class="profile-btn" id="profileBtn"><?php echo strtoupper(substr($username, 0, 1)); ?></button>
        <div class="dropdown" id="profileMenu">
            <a href="profile.php">profile details</a>
            <a href="booking_history.php">Booking History</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="box" id="busDetailsBox">
        <h2>Bus Details</h2>
        <p><strong>Bus:</strong> <span id="busName"><?php echo htmlspecialchars($bus_name); ?></span> (<span id="busNumber"><?php echo htmlspecialchars($bus_number); ?></span>)</p>
        <p><strong>Route:</strong> <span id="routeName"><?php echo htmlspecialchars($route); ?></span></p>
        <p><strong>Travel Date:</strong> <span id="travelDate"><?php echo htmlspecialchars($travel_date); ?></span></p>
        <p><strong>Departure:</strong> <span id="departureTime"><?php echo htmlspecialchars($departure); ?></span></p>
        <p><strong>Arrival:</strong> <span id="arrivalTime"><?php echo htmlspecialchars($arrival); ?></span></p>
        <p><strong>Seat Type:</strong> <span id="seatType"><?php echo htmlspecialchars($seat_type); ?></span></p>
        <p><strong>Selected Seats:</strong> <span id="selectedSeats"><?php echo htmlspecialchars($selected_seats); ?></span></p>
        <p><strong>Fare:</strong> ₹<span id="fareAmount"><?php echo htmlspecialchars($base_total); ?></span></p>
        <p><strong>GST (5%):</strong> ₹<span id="gstAmount"><?php echo htmlspecialchars($gst); ?></span></p>

        <!-- Coupon section -->
        <div id="couponSection">
            <label for="couponInput">Apply Coupon Code</label>
            <input type="text" id="couponInput" maxlength="8" placeholder="Enter coupon code">
            <button type="button" id="applyCouponBtn">Apply Coupon</button>
            <p id="couponMsg"></p>
        </div>

        <p><strong>Discount:</strong> ₹<span id="discountAmount">0.00</span></p>
        <p><strong>Total Amount:</strong> ₹<span id="totalAmount"><?php echo htmlspecialchars($total_amount); ?></span></p>
    </div>

    <div class="box">
        <h2>Traveller Details</h2>
        <form id="travellerForm">
            <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($schedule_id); ?>">
            <input type="hidden" name="bus_name" value="<?php echo htmlspecialchars($bus_name); ?>">
            <input type="hidden" name="bus_number" value="<?php echo htmlspecialchars($bus_number); ?>">
            <input type="hidden" name="route" value="<?php echo htmlspecialchars($route); ?>">
            <input type="hidden" name="travel_date" value="<?php echo htmlspecialchars($travel_date); ?>">
            <input type="hidden" name="departure" value="<?php echo htmlspecialchars($departure); ?>">
            <input type="hidden" name="arrival" value="<?php echo htmlspecialchars($arrival); ?>">
            <input type="hidden" name="seats" value="<?php echo htmlspecialchars($selected_seats); ?>">
            <!-- We will update this dynamically -->
            <input type="hidden" name="fare" id="formFare" value="<?php echo htmlspecialchars($total_amount); ?>">

            <label for="traveller_name">Full Name</label>
            <input type="text" id="traveller_name" name="name" placeholder="Enter full name" required>

            <label for="traveller_email">Email Address</label>
            <input type="email" id="traveller_email" name="email" placeholder="Enter email" required>

            <label for="traveller_phone">Phone Number</label>
            <input type="tel" id="traveller_phone" name="phone" placeholder="Enter 10-digit phone" pattern="[0-9]{10}" required>

            <label for="traveller_gender">Gender</label>
            <select id="traveller_gender" name="gender" required>
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
            <button type="button" onclick="showModal()">Proceed to Pay</button>
        </form>
    </div>
</div>

<a href="booking_form.php?<?php 
    echo http_build_query(array_merge($_GET, [
        'source' => $_GET['source'] ?? '',
        'destination' => $_GET['destination'] ?? '',
        'search_date' => $_GET['search_date'] ?? ''
    ])); 
?>" id="backBtn">⬅ Back</a>


<!-- Modal -->
<div class="modal" id="summaryModal">
    <div class="modal-content">
        <h3>Confirm Booking</h3>
        <h4>Bus Details</h4>
        <table>
            <tr><th>Bus</th><td id="m_bus"></td></tr>
            <tr><th>Route</th><td id="m_route"></td></tr>
            <tr><th>Travel Date</th><td id="m_date"></td></tr>
            <tr><th>Departure</th><td id="m_departure"></td></tr>
            <tr><th>Arrival</th><td id="m_arrival"></td></tr>
            <tr><th>Seats</th><td id="m_seats"></td></tr>
            <tr><th>Total Amount</th><td id="m_amount"></td></tr>
            <tr><th>Coupon Applied</th><td id="m_coupon">None</td></tr>
            <tr><th>Discount</th><td id="m_discount">₹0.00</td></tr>
        </table>

        <h4>Traveller Details</h4>
        <table>
            <tr><th>Name</th><td id="m_name"></td></tr>
            <tr><th>Email</th><td id="m_email"></td></tr>
            <tr><th>Phone</th><td id="m_phone"></td></tr>
            <tr><th>Gender</th><td id="m_gender"></td></tr>
        </table>

        <div class="modal-buttons">
            <button onclick="closeModal()">Back</button>
            <button onclick="proceedPayment()">Continue to Pay</button>
        </div>
    </div>
</div>

<script>
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');
    profileBtn.addEventListener('click', () => {
        profileMenu.style.display = (profileMenu.style.display === 'flex') ? 'none' : 'flex';
    });

    // Variables with original amounts from PHP
    const baseTotal = <?php echo json_encode($base_total); ?>;
    const gst = <?php echo json_encode($gst); ?>;
    let discount = 0;
    let couponApplied = false;
    let appliedCouponCode = '';

    const fareAmountElem = document.getElementById('fareAmount');
    const gstAmountElem = document.getElementById('gstAmount');
    const discountAmountElem = document.getElementById('discountAmount');
    const totalAmountElem = document.getElementById('totalAmount');
    const couponMsgElem = document.getElementById('couponMsg');
    const couponInput = document.getElementById('couponInput');
    const formFare = document.getElementById('formFare');

    document.getElementById('applyCouponBtn').addEventListener('click', () => {
        const coupon = couponInput.value.trim();
        if (!validateCoupon(coupon)) {
            couponMsgElem.textContent = "Invalid coupon code.";
            couponMsgElem.classList.add('error');
            resetDiscount();
            return;
        }
        // Valid coupon - apply 30% discount on total amount (base + gst)
        discount = ((baseTotal + gst) * 0.30).toFixed(2);
        couponMsgElem.textContent = `Coupon applied! 30% discount: ₹${discount}`;
        couponMsgElem.classList.remove('error');
        couponApplied = true;
        appliedCouponCode = coupon.toUpperCase();
        updateAmounts();
    });

    function resetDiscount() {
        discount = 0;
        couponApplied = false;
        appliedCouponCode = '';
        couponMsgElem.textContent = '';
        updateAmounts();
    }

    function updateAmounts() {
        discountAmountElem.textContent = discount ? discount : '0.00';

        const newTotal = (baseTotal + gst - discount).toFixed(2);
        totalAmountElem.textContent = newTotal;

        // Also update hidden form field fare to new total for payment
        formFare.value = newTotal;
    }

    // Coupon validation function
    function validateCoupon(code) {
        if (code.length !== 8) return false;
        // Case-insensitive check for first 5 chars = y22cm
        const prefix = code.substring(0, 5).toLowerCase();
        if (prefix !== 'y22cm') return false;
        // Last 3 chars numeric between 001 and 216 inclusive
        const suffix = code.substring(5);
        if (!/^\d{3}$/.test(suffix)) return false;
        const num = parseInt(suffix, 10);
        if (num < 1 || num > 216) return false;
        return true;
    }

    function showModal() {
        const name = document.getElementById('traveller_name').value.trim();
        const email = document.getElementById('traveller_email').value.trim();
        const phone = document.getElementById('traveller_phone').value.trim();
        const gender = document.getElementById('traveller_gender').value.trim();
        if (!name || !email || !phone || !gender) {
            alert("Please fill all traveller details.");
            return;
        }

        // Populate modal fields
        document.getElementById('m_bus').textContent = document.getElementById('busName').textContent + " (" + document.getElementById('busNumber').textContent + ")";
        document.getElementById('m_route').textContent = document.getElementById('routeName').textContent;
        document.getElementById('m_date').textContent = document.getElementById('travelDate').textContent;
        document.getElementById('m_departure').textContent = document.getElementById('departureTime').textContent;
        document.getElementById('m_arrival').textContent = document.getElementById('arrivalTime').textContent;
        document.getElementById('m_seats').textContent = document.getElementById('selectedSeats').textContent;
        document.getElementById('m_amount').textContent = "₹" + totalAmountElem.textContent;

        document.getElementById('m_name').textContent = name;
        document.getElementById('m_email').textContent = email;
        document.getElementById('m_phone').textContent = phone;
        document.getElementById('m_gender').textContent = gender;

        document.getElementById('m_coupon').textContent = couponApplied ? appliedCouponCode : 'None';
        document.getElementById('m_discount').textContent = "₹" + (discount ? discount : "0.00");

        document.getElementById('summaryModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('summaryModal').style.display = 'none';
    }

    function proceedPayment() {
        const form = document.getElementById('travellerForm');
        form.action = "payment.php";
        form.method = "get";
        form.submit();
    }
</script>
</body>
</html>
