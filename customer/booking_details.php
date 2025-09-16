<?php
include(__DIR__ . '/../include/db_connect.php');

$username = isset($_GET['username']) ? trim($_GET['username']) : '';
$user_id  = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;


// ✅ Get all bus & seat details from GET
$schedule_id    = $_GET['schedule_id'] ?? '';
$bus_name       = $_GET['bus_name'] ?? '';
$bus_number     = $_GET['bus_number'] ?? '';
$route          = $_GET['route'] ?? '';
$travel_date    = $_GET['travel_date'] ?? '';
$departure      = $_GET['departure'] ?? '';
$arrival        = $_GET['arrival'] ?? '';
$selected_seats = $_GET['seats'] ?? '';
$fare           = $_GET['fare'] ?? 0;

// Determine seat type
$seat_list = array_filter(array_map('trim', explode(',', $selected_seats)));
$seat_type = 'Seater';
foreach ($seat_list as $seat) {
    if (stripos($seat, 'S') !== false || stripos($seat, 'U') !== false) {
        $seat_type = 'Sleeper';
        break;
    }
}

// Calculate fare with GST (before coupon)
$total_seats = count($seat_list);
$base_total = $total_seats * $fare;
$gst = round($base_total * 0.05, 2);
$total_amount = $base_total + $gst;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking Details - <?=htmlspecialchars($bus_name)?></title>
<style>
body { margin: 0; font-family: 'Poppins', sans-serif; background: #111; color: #fff; }
.container { display: flex; flex-wrap: wrap; justify-content: space-around; padding: 30px; }
.box { background: rgba(0,0,0,0.6); padding: 20px; border-radius: 10px; width: 45%; min-width: 320px; margin: 10px; }
h2 { color: #ffde59; text-align: center; }
label { display: block; margin-top: 10px; color: #ffde59; }
input, select, button { width: 100%; padding: 10px; margin: 5px 0 15px 0; border: none; border-radius: 5px; font-size: 1rem; }
input, select { background: #fff; color: #333; }
button { background: linear-gradient(90deg,#ff512f,#dd2476); color:#fff; cursor:pointer; font-size:1.1rem; }
button:hover { background: linear-gradient(90deg,#dd2476,#ff512f); }
#summaryModal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); justify-content:center; align-items:center; z-index:1000; }
.modal-content { background:#222; padding:20px; border-radius:10px; max-width:600px; width:90%; color:#fff; }
.modal-content h3 { color:#ffde59; text-align:center; }
.modal-content table { width:100%; margin-top:10px; border-collapse:collapse; }
.modal-content th, .modal-content td { padding:8px; border:1px solid #555; text-align:left; }
.modal-buttons { display:flex; justify-content:space-between; margin-top:15px; }
.modal-buttons button { width:48%; }
</style>
</head>
<body>

<div class="container">
    <!-- Bus & Seat Details + Coupon -->
    <div class="box">
        <h2>Bus & Seat Details</h2>
        <p><strong>Bus:</strong> <?=htmlspecialchars($bus_name)?> (<?=htmlspecialchars($bus_number)?>)</p>
        <p><strong>Route:</strong> <?=htmlspecialchars($route)?></p>
        <p><strong>Date:</strong> <?=htmlspecialchars($travel_date)?></p>
        <p><strong>Departure:</strong> <?=htmlspecialchars($departure)?></p>
        <p><strong>Arrival:</strong> <?=htmlspecialchars($arrival)?></p>
        <hr>
        <p><strong>Seat Type:</strong> <?=htmlspecialchars($seat_type)?></p>
        <p><strong>Selected Seats:</strong> <?=htmlspecialchars($selected_seats)?></p>
        <hr>
        <label for="coupon">Coupon Code</label>
        <input type="text" id="coupon" name="coupon" autocomplete="off" maxlength="8" placeholder="Enter coupon code">
        <button type="button" id="applyCouponBtn">Apply Coupon</button>
        <p id="couponMsg"></p>
    </div>

    <!-- Payment Details + Traveller Details -->
    <div class="box">
        <h2>Payment Details</h2>
        <p><strong>Fare (Base):</strong> ₹<span id="baseTotal"><?=$base_total?></span></p>
        <p><strong>GST (5%):</strong> ₹<span id="gstAmount"><?=$gst?></span></p>
        <p><strong>Discount:</strong> ₹<span id="discountAmount">0.00</span></p>
        <p><strong>Total Amount:</strong> ₹<span id="totalAmount"><?=$total_amount?></span></p>
        <hr>
        <h2>Traveller Details</h2>
        <label for="traveller_name">Full Name</label>
        <input type="text" id="traveller_name" placeholder="Enter full name" required>

        <label for="traveller_email">Email</label>
        <input type="email" id="traveller_email" placeholder="Enter email" required>

        <label for="traveller_phone">Phone</label>
        <input type="tel" id="traveller_phone" placeholder="10-digit phone" pattern="[0-9]{10}" required>

        <label for="traveller_gender">Gender</label>
        <select id="traveller_gender" required>
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
        </select>

        <button type="button" onclick="showSummary()">Proceed to Pay</button>
    </div>
</div>

<!-- Modal -->
<div id="summaryModal">
    <div class="modal-content">
        <h3>Booking Summary</h3>
        <table>
            <tr><th>Bus</th><td id="m_bus"></td></tr>
            <tr><th>Route</th><td id="m_route"></td></tr>
            <tr><th>Date</th><td id="m_date"></td></tr>
            <tr><th>Departure</th><td id="m_departure"></td></tr>
            <tr><th>Arrival</th><td id="m_arrival"></td></tr>
            <tr><th>Seats</th><td id="m_seats"></td></tr>
            <tr><th>Seat Type</th><td id="m_seat_type"></td></tr>
            <tr><th>Traveller</th><td id="m_name"></td></tr>
            <tr><th>Email</th><td id="m_email"></td></tr>
            <tr><th>Phone</th><td id="m_phone"></td></tr>
            <tr><th>Gender</th><td id="m_gender"></td></tr>
            <tr><th>Coupon</th><td id="m_coupon"></td></tr>
            <tr><th>Total Amount</th><td id="m_amount"></td></tr>
        </table>
        <div class="modal-buttons">
            <button onclick="closeModal()">Cancel</button>
            <button onclick="proceedToPayment()">Pay Now</button>
        </div>
    </div>
</div>

<script>
let baseTotal = Number(<?=$base_total?>);
let gst = Number(<?=$gst?>);
let discount = 0;

function applyCoupon(){
    let code = document.getElementById('coupon').value.trim();
    const msg = document.getElementById('couponMsg');
    const pattern = /^y22cm(\d{3})$/i;
    const match = code.match(pattern);

    if(match){
        let num = parseInt(match[1]);
        if(num>=1 && num<=216){
            discount = Number(((baseTotal + gst) * 0.3).toFixed(2));
            msg.textContent = `Coupon Applied! 30% Discount: ₹${discount}`;
            msg.style.color = '#0f0';
        } else {
            discount = 0;
            msg.textContent = 'Invalid coupon code.';
            msg.style.color = '#f44';
        }
    } else {
        discount = 0;
        msg.textContent = 'Invalid coupon code.';
        msg.style.color = '#f44';
    }
    updatePaymentDetails();
}

function updatePaymentDetails(){
    document.getElementById('discountAmount').textContent = discount.toFixed(2);
    const total = (baseTotal + gst - discount).toFixed(2);
    document.getElementById('totalAmount').textContent = total;
}

document.getElementById('applyCouponBtn').addEventListener('click', applyCoupon);

function showSummary(){
    const name = document.getElementById('traveller_name').value.trim();
    const email = document.getElementById('traveller_email').value.trim();
    const phone = document.getElementById('traveller_phone').value.trim();
    const gender = document.getElementById('traveller_gender').value.trim();

    if(!name || !email || !phone || !gender){
        alert("Please fill all traveller details.");
        return;
    }

    document.getElementById('m_bus').textContent = "<?=htmlspecialchars($bus_name)?> (<?=htmlspecialchars($bus_number)?>)";
    document.getElementById('m_route').textContent = "<?=htmlspecialchars($route)?>";
    document.getElementById('m_date').textContent = "<?=htmlspecialchars($travel_date)?>";
    document.getElementById('m_departure').textContent = "<?=htmlspecialchars($departure)?>";
    document.getElementById('m_arrival').textContent = "<?=htmlspecialchars($arrival)?>";
    document.getElementById('m_seats').textContent = "<?=htmlspecialchars($selected_seats)?>";
    document.getElementById('m_seat_type').textContent = "<?=htmlspecialchars($seat_type)?>";
    document.getElementById('m_name').textContent = name;
    document.getElementById('m_email').textContent = email;
    document.getElementById('m_phone').textContent = phone;
    document.getElementById('m_gender').textContent = gender;
    document.getElementById('m_coupon').textContent = discount>0 ? document.getElementById('coupon').value.trim() : "None";
    document.getElementById('m_amount').textContent = "₹"+((baseTotal+gst-discount).toFixed(2));

    document.getElementById('summaryModal').style.display = 'flex';
}

function closeModal(){ document.getElementById('summaryModal').style.display = 'none'; }

function proceedToPayment(){
    const params = new URLSearchParams();
    params.append('schedule_id', "<?=urlencode($schedule_id)?>");
    params.append('bus_name', "<?=urlencode($bus_name)?>");
    params.append('bus_number', "<?=urlencode($bus_number)?>");
    params.append('route', "<?=urlencode($route)?>");
    params.append('travel_date', "<?=urlencode($travel_date)?>");
    params.append('departure', "<?=urlencode($departure)?>");
    params.append('arrival', "<?=urlencode($arrival)?>");
    params.append('seats', "<?=urlencode($selected_seats)?>");
    params.append('fare', "<?=urlencode($fare)?>");
    params.append('traveller_name', document.getElementById('m_name').textContent);
    params.append('traveller_email', document.getElementById('m_email').textContent);
    params.append('traveller_phone', document.getElementById('m_phone').textContent);
    params.append('traveller_gender', document.getElementById('m_gender').textContent);
    params.append('base_total', baseTotal.toFixed(2));
    params.append('gst', gst.toFixed(2));
    params.append('discount', discount.toFixed(2));
    params.append('total_amount', (baseTotal + gst - discount).toFixed(2));
    params.append('coupon', document.getElementById('coupon').value.trim());
    
    // ✅ Add user details
    params.append('user_id', "<?=$user_id?>");
    params.append('username', "<?=urlencode($username)?>");

    window.location.href = "payment.php?" + params.toString();
}
</script>

</body>
</html>
