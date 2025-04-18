<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mis2025";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (mysqli_sql_exception $e) {
    error_log("Database connection failed: " . $e->getMessage(), 3, "error_log.txt");

    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Error</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Particles.js -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
            margin: 0;
            position: relative;
            overflow: hidden;
            font-family: "Segoe UI", sans-serif;
        }

        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
        }

        .content-box {
            background-color: rgba(255, 255, 255, 0.85);
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        .icon {
            font-size: 60px;
            color: #dc3545;
            animation: bounce 1.5s infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .headline {
            font-weight: 700;
            color: #dc3545;
            margin-top: 20px;
        }

        .message {
            font-size: 16px;
            color: #333;
            margin-top: 15px;
            line-height: 1.6;
        }

        .error-code {
            color: #888;
            margin-top: 10px;
            font-size: 14px;
        }

        .btn-refresh {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>

    <div class="content-box">
        <i class="bi bi-database-x icon"></i>
        <h2 class="headline">Database Connection Error</h2>
        <p class="message">
            We encountered a problem while trying to connect to the server.<br>
            This might be due to server downtime, incorrect credentials, or network issues.
        </p>
        <div class="error-code"><strong>Error Code:</strong> DB-CONN-001</div>
        <a href="" class="btn btn-outline-danger btn-refresh"><i class="bi bi-arrow-clockwise"></i> Try Again</a>
        or try this to fix the error 
        <a href="../trouble_shoot.php">HELP?</a>
    </div>

    <script>
        particlesJS("particles-js", {
            particles: {
                number: { value: 200, density: { enable: true, value_area: 1000 } },
                color: { value: "#B40001" },
                shape: { type: "line" },
                opacity: { value: 0.7 },
                size: { value: 2 },
                move: {
                    enable: true,
                    speed: 1,
                    direction: "none",
                    random: false,
                    straight: false,
                    out_mode: "out"
                },
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
</html>';
    exit;
}
?>