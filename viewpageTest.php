<?php
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

$allowed_pages = ['home', 'activity_logs.php']; // Allowed pages to prevent directory traversal attacks

if (in_array($page, $allowed_pages)) {
    $view = $page . '.php';
} else {
    $view = '404.php'; // Default to a 404 page if the page is not allowed
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Page</title>
</head>
<body>
    <nav>
        <a href="?page=home">Home</a>
        <a href="?page=try">Try</a>
    </nav>

    <div>
        <?php include $view; ?>
    </div>
</body>
</html>
