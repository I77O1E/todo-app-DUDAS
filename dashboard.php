<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
include 'db.php';
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$pending = $pdo->prepare("SELECT COUNT(*) FROM todos WHERE user_id = ? AND status = 'pending'");
$pending->execute([$user_id]);
$pending_count = $pending->fetchColumn();
$overdue = $pdo->prepare("SELECT COUNT(*) FROM todos WHERE user_id = ? AND due_date < CURDATE() AND
status != 'completed'");
$overdue->execute([$user_id]);
$overdue_count = $overdue->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/custom.css">

    <style>
        #loadingSpinner {
            z-index: 2000;
        }
    </style>

    <script>
        // DARK MODE LOADER
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
        });
    </script>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div id="loadingSpinner" class="d-none position-fixed top-50 start-50 translate-middle">
        <div class="spinner-border text-primary"></div>
    </div>

    <div class="container mt-5">

        <h2 class="dashboard-title">
            Welcome, <strong><?php echo htmlspecialchars($user['username']); ?></strong>!
        </h2>

        <?php if (!empty($user['tag'])): ?>
            <span class="badge bg-<?php echo match ($user['tag']) {
                                        'important' => 'danger',
                                        'work'     => 'primary',
                                        'personal' => 'info',
                                        'urgent'   => 'warning',
                                        default    => 'secondary'
                                    }; ?>">
                <?php echo ucfirst($user['tag']); ?>
            </span>
        <?php endif; ?>

        <div class="row g-4 mt-3">

            <div class="col-md-6">
                <div class="card text-white bg-warning shadow-sm">
                    <div class="card dashboard-stat p-3">
                        <h5>Pending Tasks</h5>
                        <h2 id="pendingCount"><?php echo $pending_count; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card text-white bg-danger shadow-sm">
                    <div class="card dashboard-stat p-3">
                        <h5>Overdue</h5>
                        <h2 id="overdueCount"><?php echo $overdue_count; ?></h2>
                    </div>
                </div>
            </div>

        </div>

        <?php if (!empty($next_due_date)): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Next Due Task</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Due:</strong> <?php echo $next_due_date; ?></p>
                    <span class="countdown badge bg-primary p-2" data-duedate="<?php echo $next_due_date; ?>">
                        Loading countdown...
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="todos.php" class="btn btn-primary dashboard-btn">Go to TODOs</a>
        </div>

    </div>

    <script>
        function loadStats() {
            const spinner = document.getElementById("loadingSpinner");
            spinner.classList.remove("d-none");

            fetch("fetch_stats.php")
                .then(res => res.json())
                .then(data => {
                    spinner.classList.add("d-none");
                    document.getElementById("pendingCount").innerText = data.pending;
                    document.getElementById("overdueCount").innerText = data.overdue;
                })
                .catch(err => {
                    spinner.classList.add("d-none");
                    console.error("Error loading stats:", err);
                });
        }

        setInterval(loadStats, 20000);
    </script>


    <script>
        document.querySelectorAll("[data-duedate]").forEach(el => {
            const due = new Date(el.dataset.duedate).getTime();

            setInterval(() => {
                const now = Date.now();
                const diff = due - now;

                if (diff <= 0) {
                    el.textContent = "Due!";
                    return;
                }

                const d = Math.floor(diff / (1000 * 60 * 60 * 24));
                const h = Math.floor((diff / (1000 * 60 * 60)) % 24);
                const m = Math.floor((diff / (1000 * 60)) % 60);

                el.textContent = `${d}d ${h}h ${m}m`;
            }, 1000);
        });
    </script>

</body>

</html>