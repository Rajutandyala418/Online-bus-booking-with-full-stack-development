<?php
include(__DIR__ . '/../include/db_connect.php');
$username = isset($_GET['username']) ? trim($_GET['username']) : '';
$user_id  = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
// 1️⃣ Get bus details from query string
$schedule_id  = $_GET['schedule_id'] ?? '';
$bus_name     = $_GET['bus_name'] ?? '';
$bus_number   = $_GET['bus_number'] ?? '';
$route        = $_GET['route'] ?? '';
$travel_date  = $_GET['travel_date'] ?? '';
$fare         = $_GET['fare'] ?? '';
$departure    = $_GET['departure'] ?? '';
$arrival      = $_GET['arrival'] ?? '';

// Parse source and destination from route if needed (assuming "Source → Destination")
$source = '';
$destination = '';
if ($route) {
    $parts = explode('→', $route);
    if (count($parts) == 2) {
        $source = trim($parts[0]);
        $destination = trim($parts[1]);
    }
}

// Fetch booked seats for this schedule (exclude cancelled)
$bookedSeats = [];
if ($schedule_id) {
    $stmt = $conn->prepare("SELECT seat_number FROM bookings WHERE schedule_id = ? AND status != 'cancelled'");
    if ($stmt) {
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $seats = explode(',', $row['seat_number']);
            $bookedSeats = array_merge($bookedSeats, $seats);
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Seat Selection - <?php echo htmlspecialchars($bus_name); ?></title>
<style>
body { margin:0; font-family: 'Poppins', sans-serif; background: #111; color: #fff; }
.container { display: flex; flex-wrap: wrap; justify-content: space-around; padding: 30px; }
.bus-details, .seat-layout { background: rgba(0,0,0,0.6); padding: 20px; border-radius: 10px; width: 45%; min-width: 320px; margin: 10px; }
h2 { color: #ffde59; margin-bottom: 10px; }
p { margin: 5px 0; }
#backBtn, #bookNowBtn { display:block; margin:20px auto; padding:15px 30px; background: linear-gradient(90deg,#ff512f,#dd2476); color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:1.2rem; text-align:center; text-decoration:none; width:fit-content; }
#backBtn:hover, #bookNowBtn:hover { transform: scale(1.05); }
.seat-row { display:flex; justify-content:center; align-items:center; margin:5px 0; }
.seat { width:40px; height:40px; margin:5px; border-radius:5px; cursor:pointer; background-size:cover; background-repeat:no-repeat; background-position:center; border:1px solid #444; }
.seat.seater { background-image: url('/y22cm171/bus_booking/images/seater.png'); }
.seat.sleeper { width:30px; height:60px; background-image: url('/y22cm171/bus_booking/images/sleeper.png'); }
.seat.booked { filter: grayscale(100%); opacity:0.5; cursor:not-allowed; }
.seat.selected { box-shadow:0 0 10px 3px #0ff; filter:brightness(1.3); }
.driver { background-image:url('/y22cm171/bus_booking/images/driver_wheel.png'); background-size:contain; background-repeat:no-repeat; width:60px; height:60px; position:absolute; top:10px; left:50%; transform:translateX(-50%); }
.tab-buttons { text-align:center; margin-bottom:15px; }
.tab-buttons button { margin:0 10px; padding:8px 16px; border:none; border-radius:5px; background:#ff512f; color:#fff; cursor:pointer; }
.tab-buttons button.active { background:#0ff; color:#000; }
#selectedSeats { margin:15px 0; font-weight:bold; color:#ffde59; }
.seat-layout { position:relative; }
</style>
</head>
<body>

<div class="container">
    <div class="bus-details">
        <h2>Bus Details</h2>
        <p><strong>Bus Name:</strong> <?php echo htmlspecialchars($bus_name); ?></p>
        <p><strong>Bus Number:</strong> <?php echo htmlspecialchars($bus_number); ?></p>
        <p><strong>Route:</strong> <?php echo htmlspecialchars($route); ?></p>
        <p><strong>Date of Journey:</strong> <?php echo htmlspecialchars($travel_date); ?></p>
        <p><strong>Departure:</strong> <?php echo htmlspecialchars($departure); ?></p>
        <p><strong>Arrival:</strong> <?php echo htmlspecialchars($arrival); ?></p>
        <p><strong>Fare:</strong> ₹<?php echo htmlspecialchars($fare); ?></p>
    </div>

    <div class="seat-layout">
        <h2>Select Seats</h2>
        <div class="tab-buttons">
            <button class="active" data-tab="seater">Seater</button>
            <button data-tab="sleeper">Sleeper</button>
        </div>
        <div id="seatContainer"></div>
        <p id="selectedSeats">Selected Seats: None</p>
        <button id="bookNowBtn">Book Now</button>
    </div>
</div>

<a id="backBtn" 
   href="dashboard.php?username=<?php echo urlencode($username); ?>">
   ⬅ Back
</a>



<script>
const seatContainer = document.getElementById('seatContainer');
const selectedSeatsDisplay = document.getElementById('selectedSeats');
const bookedSeats = <?php echo json_encode($bookedSeats); ?>;
let selectedSeats = [];

const toggleSeat = (seatName, seatElement) => {
    const idx = selectedSeats.indexOf(seatName);
    if(idx > -1){ selectedSeats.splice(idx,1); seatElement.classList.remove('selected'); }
    else { selectedSeats.push(seatName); seatElement.classList.add('selected'); }
    selectedSeatsDisplay.textContent = selectedSeats.length>0 ? "Selected Seats: "+selectedSeats.join(', ') : "Selected Seats: None";
};

const renderSeater = () => {
    seatContainer.innerHTML = '<div class="driver"></div>';
    for(let r=1;r<=13;r++){
        let rowDiv=document.createElement('div'); rowDiv.className='seat-row';
        [r+'LW',r+'LS'].forEach(sn=>{
            let seat=document.createElement('div'); seat.className='seat seater';
            if(bookedSeats.includes(sn)) seat.classList.add('booked'); 
            else seat.addEventListener('click',()=>toggleSeat(sn,seat));
            rowDiv.appendChild(seat);
        });
        if(r===13){
            let middle=document.createElement('div'); middle.className='seat seater';
            let mn=r+'M';
            if(bookedSeats.includes(mn)) middle.classList.add('booked'); 
            else middle.addEventListener('click',()=>toggleSeat(mn,middle));
            rowDiv.appendChild(middle);
        }else{
            let spacer=document.createElement('div'); spacer.style.width='40px'; rowDiv.appendChild(spacer);
        }
        [r+'RS',r+'RW'].forEach(sn=>{
            let seat=document.createElement('div'); seat.className='seat seater';
            if(bookedSeats.includes(sn)) seat.classList.add('booked'); 
            else seat.addEventListener('click',()=>toggleSeat(sn,seat));
            rowDiv.appendChild(seat);
        });
        seatContainer.appendChild(rowDiv);
    }
};

const renderSleeper = () => {
    seatContainer.innerHTML = '<div class="driver"></div>';
    for(let r=1;r<=5;r++){
        let rowDiv=document.createElement('div'); rowDiv.className='seat-row';
        ['LW','LS'].forEach(side=>{
            let seat=document.createElement('div'); seat.className='seat sleeper';
            let sn=`${r}U ${side}`;
            if(bookedSeats.includes(sn)) seat.classList.add('booked'); 
            else seat.addEventListener('click',()=>toggleSeat(sn,seat));
            rowDiv.appendChild(seat);
        });
        let spacer=document.createElement('div'); spacer.style.width='60px'; rowDiv.appendChild(spacer);
        ['RS','RW'].forEach(side=>{
            let seat=document.createElement('div'); seat.className='seat sleeper';
            let sn=`${r}U ${side}`;
            if(bookedSeats.includes(sn)) seat.classList.add('booked'); 
            else seat.addEventListener('click',()=>toggleSeat(sn,seat));
            rowDiv.appendChild(seat);
        });
        seatContainer.appendChild(rowDiv);
    }
};

document.querySelectorAll('.tab-buttons button').forEach(btn=>{
    btn.addEventListener('click',()=>{
        document.querySelectorAll('.tab-buttons button').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        btn.getAttribute('data-tab')==='seater'?renderSeater():renderSleeper();
    });
});

renderSeater();

document.getElementById('bookNowBtn').addEventListener('click',()=>{
    if(selectedSeats.length===0){ 
        alert("Please select at least one seat before proceeding."); 
        return; 
    }
    const params=new URLSearchParams({
        schedule_id: "<?php echo $schedule_id; ?>",
        bus_name: "<?php echo $bus_name; ?>",
        bus_number: "<?php echo $bus_number; ?>",
        route: "<?php echo $route; ?>",
        travel_date: "<?php echo $travel_date; ?>",
        fare: "<?php echo $fare; ?>",
        departure: "<?php echo $departure; ?>",
        arrival: "<?php echo $arrival; ?>",
        seats: selectedSeats.join(','),
        user_id: "<?php echo $user_id; ?>",          // ✅ send user_id
        username: "<?php echo $username; ?>"         // ✅ send username
    });
    window.location.href="booking_details.php?"+params.toString();
});

</script>
</body>
</html>
