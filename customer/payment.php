<?php
// payment.php
include(__DIR__ . '/../include/db_connect.php');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$username = isset($_GET['username']) ? trim($_GET['username']) : '';
$user_id  = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Get data from GET (from booking_details.php)
$bus_name    = isset($_GET['bus_name']) ? urldecode($_GET['bus_name']) : '';
$bus_number  = isset($_GET['bus_number']) ? urldecode($_GET['bus_number']) : '';
$route       = isset($_GET['route']) ? urldecode($_GET['route']) : '';
$travel_date = isset($_GET['travel_date']) ? urldecode($_GET['travel_date']) : '';
$departure   = isset($_GET['departure']) ? urldecode($_GET['departure']) : '';
$arrival     = isset($_GET['arrival']) ? urldecode($_GET['arrival']) : '';
$seats       = isset($_GET['seats']) ? urldecode($_GET['seats']) : '';
$seat_type   = isset($_GET['seat_type']) ? urldecode($_GET['seat_type']) : 'Seater';
$fare        = isset($_GET['fare']) ? (float)$_GET['fare'] : 0;
$base_total   = isset($_GET['base_total']) ? (float)$_GET['base_total'] : 0;
$gst          = isset($_GET['gst']) ? (float)$_GET['gst'] : 0;
$discount     = isset($_GET['discount']) ? (float)$_GET['discount'] : 0;
$total_amount = isset($_GET['total_amount']) ? (float)$_GET['total_amount'] : 0;
$coupon       = isset($_GET['coupon']) ? $_GET['coupon'] : 'None';

// Traveller details
$traveller_name  = isset($_GET['traveller_name']) ? urldecode($_GET['traveller_name']) : '';
$traveller_email = isset($_GET['traveller_email']) ? urldecode($_GET['traveller_email']) : '';
$traveller_phone = isset($_GET['traveller_phone']) ? urldecode($_GET['traveller_phone']) : '';
$traveller_gender= isset($_GET['traveller_gender']) ? urldecode($_GET['traveller_gender']) : '';

// Make seats array
$seatArray = explode(',', $seats);
$travellersArray = [[
    'name'=>$traveller_name,
    'email'=>$traveller_email,
    'phone'=>$traveller_phone,
    'gender'=>$traveller_gender
]];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;background:#111;color:#fff;overflow-x:hidden;}
.bg-video{position:fixed;top:0;left:0;width:100%;height:100%;object-fit:cover;z-index:-1;}
.main-container{display:grid;grid-template-columns:30% 70%;gap:20px;min-height:100vh;padding:140px 40px 60px 40px;}
.left-section{display:flex;flex-direction:column;gap:20px;}
.box{background:rgba(0,0,0,0.6);padding:20px;border-radius:10px;}
.box h2{color:#ffde59;margin-bottom:10px;}
.right-section{background:rgba(0,0,0,0.6);padding:20px;border-radius:10px;overflow-y:auto;}
.payment-methods{display:flex;flex-direction:column;gap:15px;}
.payment-methods input,.payment-methods button{width:100%;padding:10px;border-radius:5px;border:none;font-size:1rem;}
.payment-methods input{background:#fff;color:#000;}
.btn{background:linear-gradient(90deg,#ff512f,#dd2476);color:#fff;font-weight:bold;cursor:pointer;transition:0.2s;margin-top:5px;}
.btn:hover{transform:scale(1.05);}
.btn:disabled{background:#444;cursor:not-allowed;}
.status-message{margin-top:10px;font-size:1rem;color:#0ff;}
.timer{position:fixed;top:70px;left:50%;transform:translateX(-50%);font-size:1.2rem;background:rgba(0,0,0,0.7);padding:10px 20px;border-radius:8px;color:#ffde59;font-weight:bold;z-index:2100;}
.qr-popup {
    display:none;
    position:fixed;
    top:0;
    left:0;
    right:0;
    bottom:0;
    background: rgba(0,0,0,0.8);
    justify-content:center;
    align-items:center;
    z-index:3000;
}

.qr-content{background:#fff;padding:20px;border-radius:10px;text-align:center;color:#000;width:300px;}
.qr-content img{width:200px;margin-bottom:10px;}
.qr-buttons button{margin:8px;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;font-weight:bold;}
.qr-back{background:#444;color:#fff;}
.qr-pay{background:#28a745;color:#fff;}
.qr-timer{font-weight:bold;margin:10px 0;color:#d00;}
.modal{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);display:flex;justify-content:center;align-items:center;z-index:5000;}
.modal-content{background:#fff;color:#000;padding:20px;border-radius:10px;width:300px;text-align:center;}
.modal-buttons{margin-top:15px;display:flex;justify-content:space-around;}
.modal-buttons button{padding:8px 16px;border:none;border-radius:5px;cursor:pointer;font-weight:bold;}
.modal-buttons button:first-child{background:#444;color:#fff;}
.modal-buttons button:last-child{background:#d33;color:#fff;}
</style>
</head>
<body>
<video autoplay muted loop playsinline class="bg-video">
    <source src="videos/bus.mp4" type="video/mp4">
</video>

<div class="timer" id="timer">08:00</div>

<div class="main-container">
    <div class="left-section">
        <div class="box">
            <h2>Bus Details & Seats</h2>
            <p><strong>Bus:</strong> <?=htmlspecialchars($bus_name)?> (<?=htmlspecialchars($bus_number)?>)</p>
            <p><strong>Route:</strong> <?=htmlspecialchars($route)?></p>
            <p><strong>Travel Date:</strong> <?=htmlspecialchars($travel_date)?></p>
            <p><strong>Departure:</strong> <?=htmlspecialchars($departure)?></p>
            <p><strong>Arrival:</strong> <?=htmlspecialchars($arrival)?></p>
            <p><strong>Seats:</strong> <?=htmlspecialchars($seats)?></p>
            <p><strong>Seat Type:</strong> <?=htmlspecialchars($seat_type)?></p>
            <p><strong>Coupon:</strong> <?=htmlspecialchars($coupon)?></p>
            <p><strong>User ID:</strong> <?= $user_id ?></p>
        </div>
        <div class="box">
            <h2>Traveller Details</h2>
            <p><strong>Name:</strong> <?=htmlspecialchars($traveller_name)?></p>
            <p><strong>Email:</strong> <?=htmlspecialchars($traveller_email)?></p>
            <p><strong>Phone:</strong> <?=htmlspecialchars($traveller_phone)?></p>
            <p><strong>Gender:</strong> <?=htmlspecialchars($traveller_gender)?></p>
        </div>
    </div>

    <div class="right-section">
        <p><strong>Base Fare:</strong> ₹<?=$base_total?></p>
        <p><strong>GST (5%):</strong> ₹<?=$gst?></p>
        <p><strong>Discount:</strong> ₹<?=$discount?></p>
        <p><strong>Total Amount:</strong> ₹<?=$total_amount?></p>
        <p><strong>Coupon:</strong> <?=htmlspecialchars($coupon)?></p>

        <div class="payment-methods">
            <h3>UPI Payment</h3>
            <input type="text" id="upiId" placeholder="Enter UPI ID (e.g., name@upi)">
            <button class="btn" onclick="verifyUPI()">Verify UPI</button>
            <button class="btn" onclick="openQRPopup()">Generate QR</button>

            <h3>Credit/Debit Card</h3>
            <input type="text" id="cardNumber" placeholder="Card Number" maxlength="16">
            <input type="text" id="cardName" placeholder="Card Holder Name">
            <input type="text" id="cardExpiry" placeholder="MM/YY" maxlength="5">
            <input type="text" id="cardCVV" placeholder="CVV" maxlength="3">

            <button class="btn" id="payBtn" onclick="payNow()" disabled>Pay Now</button>
            <div class="status-message" id="statusMsg"></div>
        </div>
    </div>
</div>

<!-- QR Popup -->
<div class="qr-popup" id="qrPopup">
  <div class="qr-content">
    <h3>Scan QR to Pay</h3>
    <img src="/y22cm171/bus_booking/images/frame.png" alt="QR Code">
    <div class="qr-timer" id="qrTimer">05:00</div>
    <div class="qr-buttons">
      <button class="qr-back" onclick="closeQRPopup()">Back</button>
      <button class="qr-pay" onclick="qrPaymentSuccess()">Pay</button>
    </div>
  </div>
</div>

<!-- Back Button Modal -->
<div id="confirmModal" class="modal" style="display:none;">
  <div class="modal-content">
    <h3>⚠️ Caution</h3>
    <p>Your ticket payment has failed.<br>Do you want to go back?</p>
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
// =================== MAIN PAYMENT TIMER ===================
const TIMER_DURATION = 8*60; 
let paymentTimer = localStorage.getItem('payment_timer') ? parseInt(localStorage.getItem('payment_timer')) : TIMER_DURATION;
const timerElement = document.getElementById('timer');
function updateTimerDisplay(){ const minutes=Math.floor(paymentTimer/60); const seconds=paymentTimer%60; timerElement.textContent=`${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`; }
const timerInterval = setInterval(()=>{
    paymentTimer--; localStorage.setItem('payment_timer',paymentTimer); updateTimerDisplay();
    if(paymentTimer<=0){ clearInterval(timerInterval); disablePayment(); redirectFailed('timeout'); }
},1000);
updateTimerDisplay();
function disablePayment(){ document.getElementById('payBtn').disabled=true; document.querySelectorAll('.payment-methods input,.payment-methods button').forEach(el=>el.disabled=true); }

// =================== QR POPUP ===================
let qrInterval, qrTimeLeft;
function openQRPopup(){
    qrTimeLeft = 5*60; 
    document.getElementById('qrPopup').style.display='flex'; 
    updateQRTimerDisplay();

    // Push state when QR opens so back triggers modal
    history.pushState({qr:true}, null, location.href);

    qrInterval = setInterval(() => {
        qrTimeLeft--;
        updateQRTimerDisplay();
        if(qrTimeLeft <= 0){
            clearInterval(qrInterval);
            document.getElementById('qrPopup').style.display='none';
            redirectFailed('qr_timeout');
        }
    }, 1000);
}

function closeQRPopup(){
    document.getElementById('qrPopup').style.display='none';
    clearInterval(qrInterval);

    // Remove the QR state to avoid double alerts
    history.back();
}

function updateQRTimerDisplay(){ const minutes=Math.floor(qrTimeLeft/60); const seconds=qrTimeLeft%60; document.getElementById('qrTimer').textContent=`${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`; }
function qrPaymentSuccess(){ clearInterval(qrInterval); selectPayment('UPI'); submitPayment(); }

// =================== PAYMENT METHOD SELECTION ===================
let selectedPaymentMethod=''; 
function selectPayment(method){ selectedPaymentMethod=method; document.getElementById('hiddenPaymentMethod').value=method; }

function verifyUPI(){
    const upi=document.getElementById('upiId').value.trim();
    if(/^[\w.-]+@[\w.-]+$/.test(upi)){ document.getElementById('statusMsg').textContent="UPI ID verified. You can proceed to pay."; selectPayment('UPI'); document.getElementById('payBtn').disabled=false; }
    else{ document.getElementById('statusMsg').textContent="Enter a valid UPI ID."; }
}

// Card input validation
['cardNumber','cardName','cardExpiry','cardCVV'].forEach(id=>document.getElementById(id).addEventListener('input',()=>{
    const filled=['cardNumber','cardName','cardExpiry','cardCVV'].every(i=>document.getElementById(i).value.trim()!=='');
    document.getElementById('payBtn').disabled=!filled; if(filled) selectPayment('CARD');
}));

// =================== PAYMENT SUBMISSION ===================
function payNow(){ if(selectedPaymentMethod==='') return alert('Please select/verify a payment method.'); submitPayment(); }
function submitPayment(){ const txnId='TXN'+Date.now(); document.getElementById('transaction_id').value=txnId; document.getElementById('hiddenTotalAmount').value='<?=$total_amount?>'; document.getElementById('hiddenDiscount').value='<?=$discount?>'; clearInterval(timerInterval); localStorage.removeItem('payment_timer'); document.getElementById('paymentForm').submit(); }

// =================== HANDLE PAYMENT FAILURE ===================
function redirectFailed(reason){
    clearInterval(timerInterval); 
    clearInterval(qrInterval); 
    localStorage.removeItem('payment_timer'); 
    disablePayment();

    const form=document.getElementById('paymentForm'); 
    const params=new URLSearchParams();
    Array.from(form.elements).forEach(el=>{
        if(el.name) params.append(el.name,el.value);
    });

    params.append('payment_status','failed'); 
    params.append('reason',reason);

    // ✅ Username & user_id are already included from hidden form
    window.location.href='payment_failed.php?'+params.toString();
}


// =================== MODAL / BACK BUTTON ===================
// =================== MODAL / BACK BUTTON ===================

// Always push a dummy state to trap browser back
history.pushState({ page: "payment" }, "", location.href);

// Handle back button (browser or mobile)
window.onpopstate = function (event) {
    // Instead of navigating back, always show modal
    showConfirmModal();
};

// Show confirm modal
function showConfirmModal() {
    document.getElementById("confirmModal").style.display = "flex";
}

// Close modal without leaving
function closeModal() {
    document.getElementById("confirmModal").style.display = "none";
    // Push state again so next back is also caught
    history.pushState({ page: "payment" }, "", location.href);
}

// Redirect to failed page if user confirms
function goToFailed() {
    redirectFailed("user_cancelled");
}
</script>

</body>
</html>
