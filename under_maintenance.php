<?php
include('inc/conn.php');
session_start();


// Check if redirection URL is set
$redirectPage = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

// Check if maintenance is still enabled
$sqlCheck = "SELECT * FROM db_pages WHERE (page_name = ? OR page_name = ?) AND is_underMaintenance = 1";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->execute([$redirectPage, 'all_page']);
$isMaintenance = $stmtCheck->fetch();

if (!$isMaintenance) {
    // If maintenance is over, redirect back
    header("Location: " . $redirectPage);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Page</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <style>
        /* Center the content vertically and horizontally */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
            text-align: center;
            margin: 0;
            position: relative;
            overflow: hidden;
        }
        /* Background particles */
        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
        }
        /* Container for the gears */
        .gears {
            position: relative;
            display: inline-block;
        }
        /* Styling for the large gear */
        .gear {
            font-size: 50px;
            position: absolute;
            top: -39px;
            right: 15px;
            animation: rotate 2s linear infinite;
        }
        /* Styling for the small gear */
        .gear-small {
            font-size: 30px;
            position: absolute;
            top: -51px;
            right: -7px;
            animation: rotate-reverse 2s linear infinite;
        }
        /* Keyframes for clockwise rotation */
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        /* Keyframes for counter-clockwise rotation */
        @keyframes rotate-reverse {
            from { transform: rotate(0deg); }
            to { transform: rotate(-360deg); }
        }
    </style>
</head>
<body>
    <!-- Particle background container -->
    <div id="particles-js"></div>
    
    <div>
        <!-- Gear icons with animations -->
        <div class="gears">
            <i class="bi bi-gear-fill gear"></i>
            <i class="bi bi-gear-fill gear-small"></i>
        </div>
        <!-- Maintenance message -->
        <h2 class="mt-3 fw-bold">Site is under maintenance</h2>
        <p class="text-muted">We're working hard to improve the user experience. Stay tuned!</p>
    </div>
    
    <script>
        particlesJS("particles-js", {
            particles: {
                number: { value: 200, density: { enable: true, value_area: 1000 } },
                color: { value: "#B40001" },
                shape: { type: "line" },
                opacity: { value: 0.7, random: false },
                size: { value: 2, random: false },
                move: { enable: true, speed: 1, direction: "none", random: false, straight: false, out_mode: "out" },
                line_linked: {
                    enable: true,
                    distance: 120,
                    color: "#B40001",
                    opacity: 0.8,
                    width: 2
                }
            },
            interactivity: {
                events: {
                    onhover: { enable: true, mode: "grab" },
                    onclick: { enable: true, mode: "push" }
                },
                modes: {
                    grab: { distance: 250, line_linked: { opacity: 1 } },
                    push: { particles_nb: 6 }
                }
            },
            retina_detect: true
        });
    </script>
</body>
</html>
