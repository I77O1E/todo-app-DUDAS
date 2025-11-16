php
<?php
include 'db.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    if ($password !== $confirm) {
        $message = '<div class="alert alert-danger">Passwords do not match!</div>';
    } elseif (!isset($_POST['terms'])) {
        $message = '<div class="alert alert-danger">You must agree to terms!</div>';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$username, $email, $hashed]);
            $message = '<div class="alert alert-success">Registered! <a href="index.php">Login now</a></div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Username or email already taken!</div>';
        }
    }
}
?>
<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/custom.css">
    <script src="assets/validation.js"></script>

    <style>
        /* Password strength bar */
        #strengthBar {
            height: 6px;
            width: 100%;
            margin-top: 4px;
            border-radius: 4px;
            transition: width 0.3s;
        }
    </style>

    <script>
        // Dark Mode Loader
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

            // Password Strength Meter
            const passInput = document.getElementById("password");
            const bar = document.getElementById("strengthBar");

            passInput.addEventListener("input", () => {
                const pass = passInput.value;
                let strength = 0;

                if (pass.length > 6) strength++;
                if (/[A-Z]/.test(pass)) strength++;
                if (/[0-9]/.test(pass)) strength++;
                if (/[^A-Za-z0-9]/.test(pass)) strength++;

                const colors = ["#dc3545", "#fd7e14", "#ffc107", "#28a745"];
                const widths = ["25%", "50%", "75%", "100%"];

                bar.style.background = colors[strength - 1] || "#ccc";
                bar.style.width = widths[strength - 1] || "0";
            });
        });
    </script>

</head>

<body class="bg-light">
    <div class="container mt-5">

        <div class="card shadow mx-auto" style="max-width: 450px;">
            <div class="card-body">

                <h2 class="text-center mb-3">Register</h2>

                <!-- Show any message (success/error) -->
                <?php if (!empty($message)) echo $message; ?>

                <form method="POST">

                    <!-- Username -->
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input id="password" type="password" name="password" class="form-control" required>
                        <div id="strengthBar"></div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <!-- Terms Checkbox -->
                    <div class="form-check mb-3">
                        <input type="checkbox" name="terms" class="form-check-input" required>
                        <label class="form-check-label">Agree to Terms & Conditions</label>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn btn-primary w-100">Register</button>

                    <p class="text-center mt-3">
                        <a href="index.php">Already have an account? Login</a>
                    </p>

                </form>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>