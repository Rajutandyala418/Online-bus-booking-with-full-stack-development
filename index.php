<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Book bus tickets easily with Varahi Travels - Your reliable bus booking system.">
    <title>Welcome to Varahi Travels</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap');

        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
            color: #fff;
        }

        /* Background Video */
        .bg-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        /* Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 0;
        }

        /* Content */
        .content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            text-align: center;
        }

 .typing {
    font-size: 3rem;
    color: #ffde59;
    white-space: nowrap;
    border-right: 4px solid #ffde59;
    overflow: hidden;
    width: 0;
    animation: typing 4s steps(22, end) forwards, blink 0.8s step-end 4s 5; 
    /* Blink runs 5 times after typing starts, then stops */
    text-shadow: 0 0 10px rgba(0,0,0,0.8);
}

@keyframes typing {
    from { width: 0 }
    to { width: 22ch; }
}

@keyframes blink {
    50% { border-color: transparent; }
}

@keyframes blink {
    0%, 100% { border-color: #ffde59; }
    50% { border-color: transparent; }
}


        .content p {
            font-size: 1.4rem;
            max-width: 600px;
            line-height: 1.5;
            margin-top: 20px;
            opacity: 0;
            animation: fadeInUp 2s ease forwards;
            animation-delay: 4s;
        }

        /* Buttons */
        .buttons {
            margin-top: 40px;
            display: flex;
            gap: 20px;
            opacity: 0;
            animation: fadeInUp 2s ease forwards;
            animation-delay: 5s;
        }

        .buttons a {
            padding: 15px 40px;
            font-size: 1.2rem;
            text-decoration: none;
            border-radius: 8px;
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white;
            font-weight: 600;
            transition: transform 0.3s, background 0.3s;
        }
.typing.done {
    border-right: none;
}


        .buttons a:hover {
            transform: scale(1.1);
            background: linear-gradient(90deg, #dd2476, #ff512f);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Support Chat Animation */
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .typing { font-size: 2rem; }
            .buttons a { padding: 12px 25px; font-size: 1rem; }
        }
    </style>
</head>
<body>

<!-- Background Video -->
<video autoplay muted loop playsinline class="bg-video">
    <source src="videos/bus1.mp4" type="video/mp4">
</video>

<div class="overlay"></div>

<!-- Main Content -->
<div class="content">
    <h1 class="typing">Welcome to Varahi Bus</h1>
    <p>Your journey begins here! Book your bus tickets easily with our reliable platform.</p>
    <div class="buttons">
        <a href="admin/login.php">Continue as Admin</a>
        <a href="customer/login.php">Continue as Customer</a>
    </div>
</div>

<!-- Floating Support Chat Icon -->
<div id="support-btn" style="position: fixed; bottom: 20px; right: 20px; z-index: 2; width: 60px; height: 60px; background: #25d366; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.3); cursor: pointer;">
    <img src="https://cdn-icons-png.flaticon.com/512/220/220236.png" alt="Support Chat Icon" width="28" height="28">
</div>

<!-- Support Chat Bubble -->
<div id="support-popup" style="display: none; position: fixed; bottom: 90px; right: 20px; background: #fff; color: #000; border-radius: 10px; padding: 20px; width: 280px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); font-family: 'Poppins', sans-serif; z-index: 3; animation: slideIn 0.5s forwards;">
    <div style="text-align: right; margin-top: -10px; margin-right: -10px;">
        <span id="close-popup" style="cursor: pointer; font-size: 20px; font-weight: bold;">&times;</span>
    </div>
    <h3 style="margin: 0 0 10px; color: #25d366;">Support</h3>
    <p><strong>Phone:</strong> <a href="tel:7569398385" style="color: #000;">7569398385</a></p>
    <p><strong>Email:</strong> <a href="mailto:y22cm171@rvrjc.ac.in" style="color: #000;">y22cm171@rvrjc.ac.in</a></p>
    <p><strong>WhatsApp:</strong> <a href="https://wa.me/917569398385" target="_blank" style="color: #000;">Chat Now</a></p>
</div>

<script>
    const supportBtn = document.getElementById('support-btn');
    const supportPopup = document.getElementById('support-popup');
    const closePopup = document.getElementById('close-popup');

    closePopup.addEventListener('click', () => {
        supportPopup.style.display = 'none';
    });
setTimeout(() => {
    document.querySelector('.typing').classList.add('done');
}, 7000); // Typing duration + blink duration


    supportBtn.addEventListener('click', () => {
        supportPopup.style.display = 'block';
        supportPopup.style.animation = 'slideIn 0.5s forwards';
        setTimeout(() => supportPopup.style.display = 'none', 10000);
    });
</script>

</body>
</html>
