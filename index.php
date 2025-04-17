<?php
session_start();
include('inc/conn.php');


// Check if the user is already logged in (via session or database)
if (isset($_SESSION['PK_userID'])) {
    $PK_userID = $_SESSION['PK_userID'];

    // Check the `is_login` status in the database
    $query = mysqli_query($conn, "SELECT `is_login` FROM `tbl_users` WHERE `PK_userID` = '$PK_userID'");
    $row = mysqli_fetch_assoc($query);

    if ($row['is_login'] == 1) {
        // User is already logged in, redirect to home.php
        header('location:home.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="icon" type="image/x-icon" href="./img/webicon.png" sizes="64x64">

    <!-- SweetAlert and Toastr -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="./js/toastr/toastr.min.js" crossorigin="anonymous"></script>
    <script src="./js/sweetalert/dist/sweetalert.min.js"></script>
    <link rel="stylesheet" href="./js/sweetalert/dist/sweetalert.css">
    <link rel="stylesheet" href="./js/toastr/toastr.min.css">

    <title>Login</title>

    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            font-family: 'Arial', sans-serif;
        }

        .form-signin {
            width: 100%;
            max-width: 360px;
            padding: 20px;
            background: #fff;
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .form-signin img {
            max-width: 100px;
            height: auto;
            margin-bottom: 20px;
        }

        .form-signin h3 {
            margin-bottom: 30px;
            font-size: 24px;
            color: #333;
        }

        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .form-control {
            width: 100%;
            height: 45px;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            color: #333;
            background: #f9f9f9;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #6a11cb;
            outline: none;
            box-shadow: 0 0 5px rgba(106, 17, 203, 0.5);
        }

        .floating-label {
            position: absolute;
            top: 10px;
            left: 15px;
            font-size: 14px;
            color: #999;
            pointer-events: none;
            transition: all 0.2s ease-in-out;
        }

        .form-control:focus~.floating-label,
        .form-control:not(:placeholder-shown)~.floating-label {
            top: -20px;
            left: 15px;
            font-size: 15px;
            color: #6a11cb;
        }

        .btn-primary {
            background-color: #6a11cb;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #5a0fb2;
        }

        .forgot-password {
            margin-top: 15px;
        }

        .forgot-password a {
            color: #6a11cb;
            text-decoration: none;
            transition: color 0.3s;
        }

        .forgot-password a:hover {
            color: #5a0fb2;
        }
    </style>
</head>

<body>
    <main class="form-signin">
        <form id="LoginForm">
            <div>
                <img src="./img/webicon.png" alt="Web Icon">
            </div>
            <div>
                <h3>Welcome Back</h3>
            </div>
            <div class="form-group">
                <input type="text" id="username" name="username" class="form-control" placeholder=" " required>
                <label class="floating-label">Username<b style="color:red">*</b></label>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" class="form-control" placeholder=" " required>
                <label class="floating-label">Password<b style="color:red">*</b></label>
            </div>
            <p class="text-danger error"></p>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
            <div class="forgot-password">
                <a href="#">Forgot your password?</a>
            </div>
        </form>
    </main>

    <!-- Login connection -->
    <script>
        $(document).on('submit', '#LoginForm', function (e) {
            e.preventDefault();

            let username = $('#username').val();
            let password = $('#password').val();

            $.ajax({
                url: "login.php",
                type: "post",
                data: {
                    username: username,
                    password: password
                },
                success: function (data) {
                    let json = JSON.parse(data);
                    if (json.status === 'true') {
                        window.location.href = "home.php"; // Redirect on success
                    } else {
                        // Show error message on the form
                        $('.error').text(json.message).fadeIn();
                        setTimeout(function () {
                            $('.error').fadeOut(); // Hide error after 3 seconds
                        }, 3000);
                    }
                },
                error: function () {
                    $('.error').text("An unexpected error occurred. Please try again.").fadeIn();
                    setTimeout(function () {
                        $('.error').fadeOut();
                    }, 3000);
                }
            });
        });
    </script>

</body>

</html>