<?php
include 'db.php';
$message = '';
$token = $_GET['token'] ?? '';
if (empty($token)) {
    header("Location: index.php");
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();
if (!$user) {
    $message = '<div class="alert alert-danger">Invalid or expired token!</div>';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    if ($password !== $confirm || strlen($password) < 8) {
        $message = '<div class="alert alert-danger">Passwords must match and be 8+ chars!</div>';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry =
NULL WHERE id = ?");
        $update->execute([$hashed, $user['id']]);
        $message = '<div class="alert alert-success">Password reset!<a href="index.php">Login</a></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/custom.css">

    <style>
        #strengthBar {
            height: 6px;
            width: 100%;
            border-radius: 4px;
            transition: width 0.3s;
            margin-top: 4px;
        }

        .toggle-pass {
            cursor: pointer;
            position: absolute;
            right: 12px;
            top: 10px;
            color: gray;
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", () => {

            const theme = localStorage.getItem("theme") || "light";
            document.documentElement.setAttribute("data-theme", theme);

            const toggle = document.getElementById("darkToggle");
            if (toggle) {
                toggle.onclick = () => {
                    const current = document.documentElement.getAttribute("data-theme");
                    const next = current === "light" ? "dark" : "light";
                    document.documentElement.setAttribute("data-theme", next);
                    localStorage.setItem("theme", next);
                };
            }

            const pass = document.getElementById("password");
            const bar = document.getElementById("strengthBar");

            pass.addEventListener("input", () => {
                const val = pass.value;
                let s = 0;

                if (val.length > 6) s++;
                if (/[A-Z]/.test(val)) s++;
                if (/[0-9]/.test(val)) s++;
                if (/[^A-Za-z0-9]/.test(val)) s++;

                const colors = ["#dc3545", "#fd7e14", "#ffc107", "#28a745"];
                const widths = ["25%", "50%", "75%", "100%"];

                bar.style.background = colors[s - 1] || "#ccc";
                bar.style.width = widths[s - 1] || "0";
            });

            document.querySelectorAll(".toggle-pass").forEach(icon => {
                icon.onclick = () => {
                    const input = icon.previousElementSibling;
                    input.type = input.type === "password" ? "text" : "password";
                    icon.textContent = input.type === "password" ? "👁" : "🙈";
                };
            });
        });
    </script>

</head>

<body class="bg-light">

    <div class="container mt-5">

        <div class="card shadow mx-auto" style="max-width: 450px;">
            <div class="card-body">

                <h3 class="text-center mb-3">Reset Password</h3>

                <!-- System message -->
                <?php if (!empty($message)) echo "<div class='alert alert-info'>$message</div>"; ?>

                <?php if ($user): ?>
                    <form method="POST">

                        <!-- New Password -->
                        <div class="mb-3 position-relative">
                            <label class="form-label">New Password</label>
                            <input type="password" id="password" name="password"
                                class="form-control" required>
                            <span class="toggle-pass">👁</span>
                            <div id="strengthBar"></div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3 position-relative">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password"
                                class="form-control" required>
                            <span class="toggle-pass">👁</span>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            Reset Password
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>