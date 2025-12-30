<?php
include(__DIR__ . '/../include/db_connect.php');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// === RETRIEVE DATA VIA POST OR SESSION TO SURVIVE REFRESH ===
session_start();

// If POST, store in session
if($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)){
    $_SESSION['payment_data'] = $_POST;
}

// Load data from session
$data = $_SESSION['payment_data'] ?? [];

// Basic info
$username    = trim($data['username'] ?? '');
$user_id     = (int)($data['user_id'] ?? 0);
$bus_name    = trim($data['bus_name'] ?? '');
$bus_number  = trim($data['bus_number'] ?? '');
$route       = trim($data['route'] ?? '');
$schedule_id = (int)($data['schedule_id'] ?? 0);
$travel_date = trim($data['travel_date'] ?? '');
$departure   = trim($data['departure'] ?? '');
$arrival     = trim($data['arrival'] ?? '');
$seats       = trim($data['seats'] ?? '');
$seat_type   = trim($data['seat_type'] ?? 'Seater');
$fare        = (float)($data['fare'] ?? 0);
$base_total  = (float)($data['base_total'] ?? 0);
$gst         = (float)($data['gst'] ?? 0);
$discount    = (float)($data['discount'] ?? 0);
$total_amount= (float)($data['total_amount'] ?? 0);
$coupon      = trim($data['coupon'] ?? 'None');

// Multiple travellers
$travellersArray = [];
if(isset($data['travellers']) && is_array($data['travellers'])){
    foreach($data['travellers'] as $traveller){
        $travellersArray[] = [
            'name'   => trim($traveller['name'] ?? ''),
            'email'  => trim($traveller['email'] ?? ''),
            'phone'  => trim($traveller['phone'] ?? ''),
            'gender' => trim($traveller['gender'] ?? '')
        ];
    }
}

// Seats array
$seatArray = array_filter(array_map('trim', explode(',', $seats)));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment</title>
<style>
/* KEEP YOUR EXISTING CSS + added table styles & responsiveness */
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;background:#111;color:#fff;overflow-x:hidden;}
.bg-video{position:fixed;top:0;left:0;width:100%;height:100%;object-fit:cover;z-index:-1;}
/* Layout: grid -> responsive */
.main-container{display:grid;grid-template-columns:45% 55%;gap:20px;min-height:80vh;padding:140px 40px 60px 40px;}
@media(max-width:992px){ .main-container{padding:120px 20px 40px 20px;} }
@media(max-width:768px){ .main-container{grid-template-columns:1fr; padding:100px 12px 30px 12px;} }

/* Boxes */
.left-section{display:flex;flex-direction:column;gap:20px;}
.box{background:rgba(0,0,0,0.6);padding:20px;border-radius:10px;}
.box h2{color:#ffde59;margin-bottom:10px;}
.right-section{background:rgba(0,0,0,0.6);padding:20px;border-radius:10px;overflow-y:auto;}
.payment-methods{display:flex;flex-direction:column;gap:15px;}

/* TABLE STYLES */
.details-table, .traveller-table, .payment-table, .card-table {
    width:100%;
    border-collapse:collapse;
    margin-top:8px;
    font-size:0.95rem;
}
.details-table th, .details-table td,
.traveller-table th, .traveller-table td,
.payment-table th, .payment-table td,
.card-table th, .card-table td {
    border:1px solid #333;
    padding:8px 10px;
    text-align:left;
    vertical-align:middle;
    background:transparent;
    color:#fff;
}
.details-table th, .traveller-table th, .payment-table th, .card-table th {
    background:rgba(255,222,89,0.07);
    color:#ffde59;
    font-weight:600;
}
.details-two-col { display:flex; gap:10px; align-items:center; }
.upi-row { display:flex; gap:8px; align-items:center; }
.upi-row input { flex:1; padding:8px; border-radius:5px; border:none; }

/* Buttons & inputs */
input[type="text"], input[type="tel"], input[type="email"], select {
    width:100%;
    padding:8px 10px;
    border-radius:6px;
    border:none;
    font-size:1rem;
    color:#000;
}
.btn{background:linear-gradient(90deg,#ff512f,#dd2476);color:#fff;font-weight:bold;cursor:pointer;transition:0.2s;margin-top:5px;padding:10px;border-radius:6px;border:none;}
.btn:hover{transform:scale(1.02);}
.btn.small{padding:8px 10px;font-size:0.95rem;}
.status-message{margin-top:10px;font-size:1rem;color:#0ff;}
.timer{position:fixed;top:70px;left:50%;transform:translateX(-50%);font-size:1.2rem;background:rgba(0,0,0,0.7);padding:10px 20px;border-radius:8px;color:#ffde59;font-weight:bold;z-index:2100;}
.loader { border:4px solid #f3f3f3; border-top:4px solid #ff512f; border-radius:50%; width:30px; height:30px; animation:spin 1s linear infinite; margin:10px auto; display:none; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* QR popup & modal - keep styles */
.qr-popup {display:none;position:fixed;top:0;left:0;right:0;bottom:0;background: rgba(0,0,0,0.8);justify-content:center;align-items:center;z-index:3000;}
.qr-content{background:#fff;padding:20px;border-radius:10px;text-align:center;color:#000;width:300px;}
.qr-content img{width:200px;margin-bottom:10px;}
.qr-buttons button{margin:8px;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;font-weight:bold;}
.qr-back{background:#444;color:#fff;}
.qr-pay{background:#28a745;color:#fff;}
.qr-timer{font-weight:bold;margin:10px 0;color:#d00;}
.modal{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);display:none;justify-content:center;align-items:center;z-index:5000;}
.modal-content{background:#fff;color:#000;padding:20px;border-radius:10px;width:300px;text-align:center;}
.modal-buttons{margin-top:15px;display:flex;justify-content:space-around;}
.modal-buttons button{padding:8px 16px;border:none;border-radius:5px;cursor:pointer;font-weight:bold;}
.modal-buttons button:first-child{background:#444;color:#fff;}
.modal-buttons button:last-child{background:#d33;color:#fff;}

#loader {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 9999;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    color: #fff;
    font-size: 1.5rem;
    text-align: center;
}
#loader img { width: 150px; margin-bottom: 20px; }

</style>
</head>
<body>
<video autoplay muted loop playsinline class="bg-video">
    <source src="videos/bus.mp4" type="video/mp4">
</video>
<!-- Loader overlay -->
<div id="loader">
    <img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif" alt="Loading..." />
    <p id="loaderMsg">Hold your breath...</p>
</div>
<div class="timer" id="timer">08:00</div>

<div class="main-container">
    <div class="left-section">
        <!-- Bus & Seats: converted into table -->
        <div class="box">
            <h2>Bus Details & Seats</h2>
            <table class="details-table">
                <tr><th>Bus</th><td><?=htmlspecialchars($bus_name)?> (<?=htmlspecialchars($bus_number)?>)</td></tr>
                <tr><th>Route</th><td><?=htmlspecialchars($route)?></td></tr>
                <tr><th>Travel Date</th><td><?=htmlspecialchars($travel_date)?></td></tr>
                <tr><th>Departure</th><td><?=htmlspecialchars($departure)?></td></tr>
                <tr><th>Arrival</th><td><?=htmlspecialchars($arrival)?></td></tr>
                <tr><th>Seats</th><td><?=htmlspecialchars($seats)?></td></tr>
                <tr><th>Seat Type</th><td><?=htmlspecialchars($seat_type)?></td></tr>
                <tr><th>Coupon</th><td><?=htmlspecialchars($coupon)?></td></tr>
                <tr><th>User ID</th><td><?= $user_id ?></td></tr>
            </table>
        </div>

        <!-- Traveller Details: Option A - single combined table -->
        <div class="box">
            <h2>Traveller Details</h2>

            <table class="traveller-table">
                <thead>
                    <tr>
                        <th>Seat</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Gender</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($travellersArray)): ?>
                        <?php foreach($travellersArray as $i => $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($seatArray[$i] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($t['name']) ?></td>
                                <td><?= htmlspecialchars($t['email']) ?></td>
                                <td><?= htmlspecialchars($t['phone']) ?></td>
                                <td><?= htmlspecialchars($t['gender']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;color:#f44;">No traveller data found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="right-section">
        <!-- Payment Details table -->
        <h2 style="margin-top:0;">Payment Summary</h2>
        <table class="payment-table">
            <tr><th>Base Fare</th><td>₹<?= number_format($base_total,2) ?></td></tr>
            <tr><th>GST (5%)</th><td>₹<?= number_format($gst,2) ?></td></tr>
            <tr><th>Discount</th><td>₹<?= number_format($discount,2) ?></td></tr>
            <tr><th>Total Amount</th><td>₹<?= number_format($total_amount,2) ?></td></tr>
            <tr><th>Coupon</th><td><?= htmlspecialchars($coupon) ?></td></tr>
        </table>

        <div style="height:12px;"></div>

        <!-- UPI: single-row with input + buttons -->
        <div class="box" style="padding:12px;">
            <h3 style="color:#ffde59;margin:0 0 8px 0;">UPI Payment</h3>
            <table class="payment-table">
                <tr>
                    <th style="width:30%;">UPI ID</th>
                    <td>
                        <div class="upi-row">
                            <input type="text" id="upiId" placeholder="Enter UPI ID (e.g., name@upi)">
                            <button class="btn small" onclick="verifyUPI()" type="button">Verify UPI</button>
                            <button class="btn small" onclick="openQRPopup()" type="button">Generate QR</button>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Card Details as table -->
        <div class="box" style="padding:12px;">
            <h3 style="color:#ffde59;margin:0 0 8px 0;">Credit / Debit Card</h3>
            <table class="card-table">
                <tr>
                    <th style="width:30%;">Card Number</th>
                    <td><input type="text" id="cardNumber" placeholder="Card Number" maxlength="16"></td>
                </tr>
                <tr>
                    <th>Card Holder</th>
                    <td><input type="text" id="cardName" placeholder="Card Holder Name"></td>
                </tr>
                <tr>
                    <th>Expiry</th>
                    <td><input type="text" id="cardExpiry" placeholder="MM/YY" maxlength="5"></td>
                </tr>
                <tr>
                    <th>CVV</th>
                    <td><input type="text" id="cardCVV" placeholder="CVV" maxlength="3"></td>
                </tr>
            </table>

            <div style="margin-top:12px;">
                <button class="btn" id="payBtn" onclick="payNow()" disabled>Pay Now</button>
                <div class="status-message" id="statusMsg"></div>
                <div class="loader" id="loader"></div>
            </div>
        </div>
    </div>
</div>

<!-- QR POPUP -->
<div id="qrPopup" class="qr-popup">
    <div class="qr-content">
        <img src="/y22cm171/bus_booking/images/frame.png" alt="QR Code">
        <div class="qr-timer" id="qrTimer">05:00</div>
        <div class="qr-buttons">
            <button class="qr-back" onclick="closeQRPopup()">Back</button>
            <button class="qr-pay" onclick="qrPaymentSuccess()">Pay</button>
        </div>
    </div>
</div>

<!-- PAYMENT CONFIRM MODAL -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <h3>Are you sure you want to cancel?</h3>
        <div class="modal-buttons">
            <button onclick="closeModal()">Cancel</button>
            <button onclick="goToFailed()">OK</button>
        </div>
    </div>
</div>

<form id="paymentForm" method="POST" action="set_payment_session.php" style="display:none;">
    <input type="hidden" name="user_id" value="<?= $user_id ?>">
    <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
    <input type="hidden" name="bus_name" value="<?=htmlspecialchars($bus_name)?>">
    <input type="hidden" name="bus_number" value="<?=htmlspecialchars($bus_number)?>">
    <input type="hidden" name="route" value="<?=htmlspecialchars($route)?>">
    <input type="hidden" name="travel_date" value="<?=htmlspecialchars($travel_date)?>">
    <input type="hidden" name="departure" value="<?=htmlspecialchars($departure)?>">
    <input type="hidden" name="arrival" value="<?=htmlspecialchars($arrival)?>">
    <input type="hidden" name="seats" value="<?=htmlspecialchars($seats)?>">
    <input type="hidden" name="seat_type" value="<?=htmlspecialchars($seat_type)?>">
    <input type="hidden" name="fare" value="<?=htmlspecialchars($fare)?>">
    <input type="hidden" name="schedule_id" value="<?= $schedule_id ?>">
    <input type="hidden" name="payment_method" id="hiddenPaymentMethod" value="">
    <input type="hidden" name="total_amount" id="hiddenTotalAmount" value="<?=$total_amount?>">
    <input type="hidden" name="gst" value="<?= $gst ?>">
    <input type="hidden" name="discount" id="hiddenDiscount" value="<?=$discount?>">
    <input type="hidden" name="coupon" value="<?= htmlspecialchars($coupon) ?>">
    <input type="hidden" name="transaction_id" id="transaction_id" value="">
    <?php foreach($travellersArray as $i=>$traveller): ?>
        <input type="hidden" name="travellers[<?=$i?>][name]" value="<?=htmlspecialchars($traveller['name'])?>">
        <input type="hidden" name="travellers[<?=$i?>][email]" value="<?=htmlspecialchars($traveller['email'])?>">
        <input type="hidden" name="travellers[<?=$i?>][phone]" value="<?=htmlspecialchars($traveller['phone'])?>">
        <input type="hidden" name="travellers[<?=$i?>][gender]" value="<?=htmlspecialchars($traveller['gender'])?>">
    <?php endforeach; ?>
</form>

<script>
// ================= TIMER =================
const TIMER_DURATION = 8*60;
let paymentTimer = localStorage.getItem('payment_timer') ? parseInt(localStorage.getItem('payment_timer')) : TIMER_DURATION;
const timerElement = document.getElementById('timer');
function updateTimerDisplay(){ const m=Math.floor(paymentTimer/60), s=paymentTimer%60; timerElement.textContent=`${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`; }
const timerInterval = setInterval(()=>{
    paymentTimer--; localStorage.setItem('payment_timer',paymentTimer); updateTimerDisplay();
    if(paymentTimer<=0){ clearInterval(timerInterval); disablePayment(); redirectFailed('timeout'); }
},1000);
updateTimerDisplay();
function disablePayment(){ document.getElementById('payBtn').disabled=true; document.querySelectorAll('.payment-methods input,.payment-methods button, .box button').forEach(el=>el.disabled=true); }

// ================= QR =================
let qrInterval, qrTimeLeft;
function openQRPopup(){
    qrTimeLeft = 5*60;
    document.getElementById('qrPopup').style.display='flex';
    updateQRTimerDisplay();
    history.pushState({qr:true}, null, location.href);
    qrInterval = setInterval(()=>{
        qrTimeLeft--;
        updateQRTimerDisplay();
        if(qrTimeLeft<=0){ clearInterval(qrInterval); document.getElementById('qrPopup').style.display='none'; redirectFailed('qr_timeout'); }
    },1000);
}
function closeQRPopup(){ document.getElementById('qrPopup').style.display='none'; clearInterval(qrInterval); history.pushState({ page: "payment" }, "", location.href); }
function updateQRTimerDisplay(){ const m=Math.floor(qrTimeLeft/60), s=qrTimeLeft%60; document.getElementById('qrTimer').textContent=`${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`; }
function qrPaymentSuccess() {
    clearInterval(qrInterval); 
    selectPayment('UPI'); 
    submitPayment(); // loader now shows automatically
    document.getElementById('qrPopup').style.display = 'none';
}


// ================= PAYMENT =================
let selectedPaymentMethod=''; 
function selectPayment(method){ selectedPaymentMethod=method; document.getElementById('hiddenPaymentMethod').value=method; }

function verifyUPI(){
    const upi=document.getElementById('upiId').value.trim();
    if(/^[\w.-]+@[\w.-]+$/.test(upi)){ document.getElementById('statusMsg').textContent="UPI ID verified."; selectPayment('UPI'); document.getElementById('payBtn').disabled=false; }
    else{ document.getElementById('statusMsg').textContent="Enter a valid UPI ID."; }
}

['cardNumber','cardName','cardExpiry','cardCVV'].forEach(id=>{
    const el = document.getElementById(id);
    if(el){
        el.addEventListener('input',()=>{
            const filled=['cardNumber','cardName','cardExpiry','cardCVV'].every(i=>{
                const e = document.getElementById(i);
                return e && e.value.trim()!=='';
            });
            const payBtn = document.getElementById('payBtn');
            if(payBtn) payBtn.disabled = !filled;
            if(filled) selectPayment('CARD');
        });
    }
});

function payNow(){ if(selectedPaymentMethod==='') return alert('Please select/verify a payment method.'); submitPayment(); }
function showLoader(message="Processing your payment...") {
    const loader = document.getElementById('loader');
    const loaderMsg = document.getElementById('loaderMsg');
    loaderMsg.textContent = message;
    loader.style.display = 'flex';
}

// Update submitPayment
function submitPayment() {
    const txnId = 'TXN' + Date.now();
    const txnEl = document.getElementById('transaction_id');
    if(txnEl) txnEl.value = txnId;
    document.querySelectorAll('.payment-methods input,.payment-methods button, .box button').forEach(el => el.disabled = true);
    showLoader("Processing your payment...");
    clearInterval(timerInterval); 
    localStorage.removeItem('payment_timer');
    document.getElementById('paymentForm').submit();
}

// Update redirectFailed
function redirectFailed(reason) {
    clearInterval(timerInterval); 
    clearInterval(qrInterval); 
    localStorage.removeItem('payment_timer'); 
    disablePayment();
    showLoader("Redirecting to failed page...");
    
    const form = document.getElementById('paymentForm');
    const params = new URLSearchParams();
    Array.from(form.elements).forEach(el => { if (el.name) params.append(el.name, el.value); });
    params.append('payment_status', 'failed'); 
    params.append('reason', reason);
    
    setTimeout(() => {
        window.location.href = 'payment_failed.php?' + params.toString();
    }, 500); // small delay so loader appears
}


let backAlertShown = false;

window.addEventListener("load", () => {
    history.replaceState({ page: "payment" }, "", location.href); // initial state
    history.pushState({ page: "payment" }, "", location.href); // extra state to trap back
});

window.onpopstate = function (event) {
    if (!backAlertShown) {
        showConfirmModal();
        backAlertShown = true;
        history.pushState({ page: "payment" }, "", location.href); // prevent actual back
    } else {
        history.back();
    }
};

function goToFailed() {
    showLoader("Redirecting to failed page...");
    redirectFailed("user_cancelled");
}

function closeModal() {
    document.getElementById("confirmModal").style.display = 'none';
    history.pushState({ page: "payment" }, "", location.href);
}

function showConfirmModal(){ document.getElementById("confirmModal").style.display='flex'; }
</script>
</body>
</html>
