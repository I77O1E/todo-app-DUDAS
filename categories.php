<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
include 'db.php';
$user_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['name']))) {
    $name = trim($_POST['name']);
    $stmt = $pdo->prepare("INSERT INTO categories (name, user_id) VALUES (?, ?)");
    $stmt->execute([$name, $user_id]);
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    header("Location: categories.php");
}
$stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY name");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Categories</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/custom.css">

    <style>
        #loadingSpinner {
            z-index: 2000;
        }

        .category-badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
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
        });
    </script>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <!-- GLOBAL SPINNER -->
    <div id="loadingSpinner" class="d-none position-fixed top-50 start-50 translate-middle">
        <div class="spinner-border text-primary"></div>
    </div>

    <div class="container mt-5">

        <h3 class="categories-title mb-4">Manage Categories</h3>

        <!-- ADD FORM -->
        <form method="POST" class="row g-3 mb-4 category-form">
            <div class="col-md-6 col-sm-8">
                <input type="text" name="name" class="form-control" placeholder="New category" required>
            </div>
            <div class="col-md-3 col-sm-4">
                <button type="submit" class="btn btn-success w-100">Add</button>
            </div>
        </form>

        <!-- TABLE -->
        <div class="table-responsive">
            <table class="table table-bordered category-table">
                <thead class="table-primary">
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Name</th>
                        <th style="width:150px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $i => $cat): ?>
                        <tr>
                            <td><?= $i + 1; ?></td>
                            <td>
                                <?= htmlspecialchars($cat['name']); ?>
                            </td>
                            <td>
                                <button class="btn btn-danger btn-sm"
                                    onclick="deleteCategory(<?= $cat['id']; ?>)">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- SCRIPTS -->
    <script>
        function deleteCategory(id) {
            if (!confirm("Delete this category?")) return;

            const spinner = document.getElementById("loadingSpinner");
            spinner.classList.remove("d-none");

            fetch("?delete=" + id)
                .then(() => location.reload())
                .catch(() => alert("Error deleting category"))
                .finally(() => spinner.classList.add("d-none"));
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>