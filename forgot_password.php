<?php
include 'db.php';
include 'utils.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
        $update->execute([$token, $expiry, $user['id']]);
        $link = "http://localhost/todo-app/reset_password.php?token=$token";
        simulate_email($email, "Password Reset", "Click: $link");
        $message = '<div class="alert alert-success">Check <code>email_log.txt</code> for reset link!</div>';
    } else {
        $message = '<div class="alert alert-danger">Email not found!</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/custom.css">
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const theme = localStorage.getItem("theme") || "light";
            document.documentElement.setAttribute("data-theme", theme);

            const toggleBtn = document.getElementById("darkToggle");
            if (toggleBtn) {
                toggleBtn.onclick = () => {
                    const current = document.documentElement.getAttribute("data-theme");
                    const next = current === "light" ? "dark" : "light";
                    document.documentElement.setAttribute("data-theme", next);
                    localStorage.setItem("theme", next);
                };
            }
        });
    </script>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow mx-auto" style="max-width: 400px;">
            <div class="card-body">
                <h3 class="text-center mb-4">Forgot Password</h3>

                <!-- PHP message -->
                <?php if (!empty($message)) echo '<div class="alert alert-info">' . $message . '</div>'; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">Send Reset Link</button>
                </form>

                <p class="text-center mt-3"><a href="index.php">Back to Login</a></p>
            </div>
        </div>
    </div>
</body>

</html>