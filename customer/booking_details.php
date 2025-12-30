<?php
include(__DIR__ . '/../include/db_connect.php');

$username = isset($_GET['username']) ? trim($_GET['username']) : '';
$user_id  = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// GET details
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

// Fare Calculation
$total_seats = count($seat_list);
$base_total = $total_seats * $fare;
$gst = round($base_total * 0.05, 2);
$total_amount = $base_total + $gst;
?>

<!DOCTYPE html>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
<meta charset="UTF-8">
<title>Booking Details - <?=htmlspecialchars($bus_name)?></title>

<style>
body { margin:0; font-family:'Poppins',sans-serif; background:#111; color:#fff; }
.container { display:flex; flex-wrap:wrap; justify-content:space-around; padding:20px; }

.box { background:rgba(0,0,0,0.6); padding:20px; border-radius:10px; width:45%; min-width:330px; margin:10px; }
h2 { text-align:center; color:#ffde59; }

@media(max-width:768px) {
    .container {
        flex-direction: column;
        padding: 10px;
        align-items: center;
    }

    .box {
        width: 100%!important;
        min-width: auto;
        padding: 15px;
    }

    .table-box th, 
    .table-box td {
        font-size: 13px;
        padding: 6px;
    }

    button, input, select {
        width: 100%!important;
        font-size: 15px;
    }

    h2 { font-size: 20px; }
}

/* TABLE STYLES */
/* 📱 Mobile Horizontal Scroll Fix */
.table-box {
    overflow-x: auto;
    display: block;
}

.table-box th, .table-box td {
    border:1px solid #444;
    padding:8px;
    text-align:left;
}
.table-box th {
    background:#222;
    color:#ffde59;
}
.table-box td {
    background:#111;
}

/* Buttons */
label { color:#ffde59; margin-top:10px; display:block; }
input,select,button { width:90%; padding:10px; margin:5px 0 15px; border:none; border-radius:5px; }
button { background:linear-gradient(90deg,#ff512f,#dd2476); color:#fff; cursor:pointer; }
button:hover { background:linear-gradient(90deg,#dd2476,#ff512f); }

/* Modal */
#summaryModal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); justify-content:center; align-items:center; z-index:1000; }
.modal-content { background:#222; padding:20px; border-radius:10px; width:90%; max-width:650px; }
.modal-content h3 { text-align:center; color:#ffde59; }
.modal-buttons { display:flex; justify-content:space-between; margin-top:10px; }
.modal-buttons button { width:48%; }
</style>
</head>

<body>

<form id="travellersForm" method="POST" action="payment.php"></form>

<div class="container">

    <!-- BUS DETAILS TABLE -->
    <div class="box">
        <h2>Bus & Seat Details</h2>

        <table class="table-box">
            <tr><th>Bus Name</th><td><?=htmlspecialchars($bus_name)?></td></tr>
            <tr><th>Bus Number</th><td><?=htmlspecialchars($bus_number)?></td></tr>
            <tr><th>Route</th><td><?=htmlspecialchars($route)?></td></tr>
            <tr><th>Date of Journey</th><td><?=htmlspecialchars($travel_date)?></td></tr>
            <tr><th>Departure</th><td><?=htmlspecialchars($departure)?></td></tr>
            <tr><th>Arrival</th><td><?=htmlspecialchars($arrival)?></td></tr>
            <tr><th>Seat Type</th><td><?=htmlspecialchars($seat_type)?></td></tr>
            <tr><th>Selected Seats</th><td><?=htmlspecialchars($selected_seats)?></td></tr>

            <!-- Coupon row 2-column -->
            <tr>
                <th>Coupon Code</th>
                <td>
                    <input type="text" id="coupon" placeholder="Enter coupon code">
                    <button type="button" id="applyCouponBtn">Apply</button>
                    <p id="couponMsg"></p>
                </td>
            </tr>
        </table>
    </div>

    <!-- PAYMENT DETAILS TABLE -->
    <div class="box">
        <h2>Payment Details</h2>

        <table class="table-box">
            <tr><th>Base Fare</th><td>₹<span id="baseTotal"><?=$base_total?></span></td></tr>
            <tr><th>GST (5%)</th><td>₹<span id="gstAmount"><?=$gst?></span></td></tr>
            <tr><th>Discount</th><td>₹<span id="discountAmount">0.00</span></td></tr>
            <tr><th>Total Amount</th><td>₹<span id="totalAmount"><?=$total_amount?></span></td></tr>
        </table>

        <!-- TRAVELLER TABLE TITLE -->
        <h2>Traveller Details</h2>

        <!-- TRAVELLER TABLE (Option A) -->
        <table class="table-box" id="travellerTable">
            <thead>
                <tr>
                    <th>Seat</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Gender</th>
                </tr>
            </thead>
            <tbody id="travellersContainer"></tbody>
        </table>

        <button type="button" onclick="showSummary()">Proceed to Pay</button>
    </div>
</div>

<!-- SUMMARY MODAL -->
<div id="summaryModal">
    <div class="modal-content">
        <h3>Booking Summary</h3>

        <table class="table-box">
            <tr><th>Bus</th><td id="m_bus"></td></tr>
            <tr><th>Route</th><td id="m_route"></td></tr>
            <tr><th>Date</th><td id="m_date"></td></tr>
            <tr><th>Departure</th><td id="m_departure"></td></tr>
            <tr><th>Arrival</th><td id="m_arrival"></td></tr>
            <tr><th>Seats</th><td id="m_seats"></td></tr>
            <tr><th>Seat Type</th><td id="m_seat_type"></td></tr>
            <tr><th>Travellers</th><td id="m_name"></td></tr>
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
// Existing calculation logic preserved
let baseTotal = Number(<?=$base_total?>);
let gst = Number(<?=$gst?>);
let discount = 0;
const seatList = <?=json_encode($seat_list)?>;

/* ---------------- COUPON ---------------- */
function applyCoupon() {
    let code = document.getElementById('coupon').value.trim();
    const msg = document.getElementById('couponMsg');
    const pattern = /^y22cm(\d{3})$/i;
    const match = code.match(pattern);

    if (match) {
        let num = parseInt(match[1]);
        if (num >= 1 && num <= 216) {
            discount = Number(((baseTotal + gst) * 0.3).toFixed(2));
            msg.textContent = `30% Discount Applied: ₹${discount}`;
            msg.style.color = '#0f0';
        } else {
            discount = 0;
            msg.textContent = "Invalid coupon.";
            msg.style.color = '#f44';
        }
    } else {
        discount = 0;
        msg.textContent = "Invalid coupon.";
        msg.style.color = '#f44';
    }

    updatePaymentDetails();
}

document.getElementById('applyCouponBtn').addEventListener('click', applyCoupon);

function updatePaymentDetails() {
    document.getElementById('discountAmount').textContent = discount.toFixed(2);
    document.getElementById('totalAmount').textContent = (baseTotal + gst - discount).toFixed(2);
}

/* ----------- TRAVELLER TABLE GENERATION (Option A) ----------- */
function createTravellerFields() {
    const tbody = document.getElementById('travellersContainer');
    tbody.innerHTML = "";

    seatList.forEach((seat, i) => {
        tbody.insertAdjacentHTML(
            "beforeend",
            `
            <tr>
                <td>${seat}</td>
                <td><input type="text" name="traveller_name[]" placeholder="enter name" required></td>
                <td><input type="email" name="traveller_email[]" ${i===0?"":"readonly"} placeholder="enter email" required></td>
                <td><input type="tel" name="traveller_phone[]" pattern="[0-9]{10}" ${i===0?"":"readonly"} placeholder="enter phone" required></td>
                <td>
                    <select name="traveller_gender[]" required>
                        <option value="">Select</option>
                        <option>Male</option>
                        <option>Female</option>
                        <option>Other</option>
                    </select>
                </td>
            </tr>
            `
        );
    });

    // Auto-fill same email/phone
    const emails = document.getElementsByName('traveller_email[]');
    const phones = document.getElementsByName('traveller_phone[]');

    emails[0].addEventListener("input", () => {
        for (let i = 1; i < emails.length; i++) emails[i].value = emails[0].value;
    });

    phones[0].addEventListener("input", () => {
        for (let i = 1; i < phones.length; i++) phones[i].value = phones[0].value;
    });
}

createTravellerFields();

/* -------------- SUMMARY MODAL -------------- */
function showSummary() {
    const names = document.getElementsByName('traveller_name[]');
    const emails = document.getElementsByName('traveller_email[]');
    const phones = document.getElementsByName('traveller_phone[]');
    const genders = document.getElementsByName('traveller_gender[]');

    let travellersData = [];

    for (let i = 0; i < seatList.length; i++) {
        if (!names[i].value.trim() || !emails[i].value.trim() || !phones[i].value.trim() || !genders[i].value.trim()) {
            alert(`Fill details for seat ${seatList[i]}`);
            return;
        }
        travellersData.push({
            seat: seatList[i],
            name: names[i].value.trim(),
            email: emails[i].value.trim(),
            phone: phones[i].value.trim(),
            gender: genders[i].value.trim()
        });
    }

    document.getElementById('m_bus').textContent = "<?=$bus_name?> (<?=$bus_number?>)";
    document.getElementById('m_route').textContent = "<?=$route?>";
    document.getElementById('m_date').textContent = "<?=$travel_date?>";
    document.getElementById('m_departure').textContent = "<?=$departure?>";
    document.getElementById('m_arrival').textContent = "<?=$arrival?>";
    document.getElementById('m_seats').textContent = "<?=$selected_seats?>";
    document.getElementById('m_seat_type').textContent = "<?=$seat_type?>";

    document.getElementById('m_name').textContent =
        travellersData.map(t => `${t.seat}: ${t.name}`).join(", ");

    document.getElementById('m_email').textContent = travellersData.map(t => t.email).join(", ");
    document.getElementById('m_phone').textContent = travellersData.map(t => t.phone).join(", ");
    document.getElementById('m_gender').textContent = travellersData.map(t => t.gender).join(", ");

    document.getElementById('m_coupon').textContent =
        discount > 0 ? document.getElementById('coupon').value : "None";

    document.getElementById('m_amount').textContent =
        "₹" + (baseTotal + gst - discount).toFixed(2);

    document.getElementById('summaryModal').style.display = "flex";
}

function closeModal() {
    document.getElementById('summaryModal').style.display = "none";
}

/* -------------- PAYMENT SUBMISSION -------------- */
function proceedToPayment() {
    const form = document.getElementById('travellersForm');
    form.innerHTML = "";

    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="schedule_id" value="<?=$schedule_id?>">`);
    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="bus_name" value="<?=$bus_name?>">`);
    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="bus_number" value="<?=$bus_number?>">`);
    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="route" value="<?=$route?>">`);
    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="travel_date" value="<?=$travel_date?>">`);
    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="departure" value="<?=$departure?>">`);
    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="arrival" value="<?=$arrival?>">`);
    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="seats" value="<?=$selected_seats?>">`);
    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="fare" value="<?=$fare?>">`);
    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="user_id" value="<?=$user_id?>">`);
    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="username" value="<?=$username?>">`);

    const names = document.getElementsByName('traveller_name[]');
    const emails = document.getElementsByName('traveller_email[]');
    const phones = document.getElementsByName('traveller_phone[]');
    const genders = document.getElementsByName('traveller_gender[]');

    for (let i = 0; i < seatList.length; i++) {
        form.insertAdjacentHTML('beforeend', `<input type="hidden" name="travellers[${i}][name]" value="${names[i].value}">`);
        form.insertAdjacentHTML('beforeend', `<input type="hidden" name="travellers[${i}][email]" value="${emails[i].value}">`);
        form.insertAdjacentHTML('beforeend', `<input type="hidden" name="travellers[${i}][phone]" value="${phones[i].value}">`);
        form.insertAdjacentHTML('beforeend', `<input type="hidden" name="travellers[${i}][gender]" value="${genders[i].value}">`);
    }

    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="discount" value="${discount.toFixed(2)}">`);
    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="total_amount" value="${(baseTotal + gst - discount).toFixed(2)}">`);
    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="coupon" value="${document.getElementById('coupon').value.trim()}">`);

    form.submit();
}
</script>

</body>
</html>
