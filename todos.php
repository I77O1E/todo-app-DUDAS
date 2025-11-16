<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
include 'db.php';
include 'utils.php';
$user_id = $_SESSION['user_id'];
// CRUD Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $desc = $_POST['description'] ?? '';
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    $due_date = $_POST['due_date'] ?: null;
    $category_id = $_POST['category_id'] ?: null;
    $attachment = '';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $file = $_FILES['attachment'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png']) && $file['size'] < 5000000) {
            $attachment = "uploads/" . time() . "_" . $file['name'];
            move_uploaded_file($file['tmp_name'], $attachment);
        }
    }
    if ($_POST['action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO todos (user_id, category_id, title, description, status, priority, notifications, due_date, attachment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $category_id,
            $title,
            $desc,
            $status,
            $priority,
            $notifications,
            $due_date,
            $attachment
        ]);
    } elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("SELECT status, notifications, attachment FROM todos WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($status == 'completed' && $old['status'] != 'completed' && $notifications) {
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $email = $stmt->fetchColumn();
            simulate_email($email, "Task Done!", "Task '$title' completed!");
        }
        $stmt = $pdo->prepare("UPDATE todos SET category_id=?, title=?, description=?, status=?, priority=?, notifications=?, due_date=?, attachment=? WHERE id=? AND user_id=?");
        $stmt->execute([
            $category_id,
            $title,
            $desc,
            $status,
            $priority,
            $notifications,
            $due_date,
            $attachment ?: $old['attachment'],
            $id,
            $user_id
        ]);
    }
    header("Location: todos.php");
}
// Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM todos WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    header("Location: todos.php");
}
// Pagination & Filter
$page = max(1, $_GET['page'] ?? 1);
$limit = 5;
$offset = ($page - 1) * $limit;
$where = "WHERE t.user_id = ?";
$params = [$user_id];
if ($_GET['search'] ?? '') {
    $s = "%{$_GET['search']}%";
    $where .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $params[] = $s;
    $params[] = $s;
}
if ($_GET['status'] ?? '') {
    $where .= " AND t.status = ?";
    $params[] = $_GET['status'];
}
if ($_GET['category'] ?? '') {
    $where .= " AND t.category_id = ?";
    $params[] = $_GET['category'];
}
$stmt = $pdo->prepare("SELECT COUNT(*) FROM todos t $where");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$pages = ceil($total / $limit);
$stmt = $pdo->prepare("SELECT t.*, c.name as cat_name FROM todos t LEFT JOIN categories c ON t.category_id = c.id $where ORDER BY t.created_at DESC LIMIT ? OFFSET ?");
$todos = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ?");
$stmt->execute([$user_id]);
$cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TODOs</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/custom.css">

    <style>
        #loadingSpinner {
            z-index: 2000;
        }

        .countdown {
            font-weight: bold;
            font-size: 0.9rem;
        }

        .tag-badge {
            font-size: 0.75rem;
            margin-left: 6px;
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

    <!-- Global Spinner -->
    <div id="loadingSpinner" class="d-none position-fixed top-50 start-50 translate-middle">
        <div class="spinner-border text-primary"></div>
    </div>

    <div class="container mt-5">

        <h3 class="todo-title">My TODOs</h3>

        <!-- FILTERS -->
        <form method="GET" class="row g-2 mb-3 todo-filters">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search..."
                    value="<?php echo $_GET['search'] ?? ''; ?>">
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?= (($_GET['status'] ?? '') == 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="in_progress" <?= (($_GET['status'] ?? '') == 'in_progress') ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed" <?= (($_GET['status'] ?? '') == 'completed') ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>

            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= $c['id']; ?>" <?= (($_GET['category'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                            <?= $c['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <button class="btn btn-success mb-3 add-btn" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Task</button>

        <!-- TASK TABLE -->
        <table class="table table-striped table-glass">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Due</th>
                    <th>Tag</th>
                    <th>File</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($todos as $t): ?>
                    <tr>

                        <!-- TITLE -->
                        <td><?= htmlspecialchars($t['title']); ?></td>

                        <!-- STATUS -->
                        <td>
                            <span class="badge-status 
                                <?= $t['status'] == 'completed'
                                    ? 'badge-completed'
                                    : ($t['status'] == 'in_progress'
                                        ? 'badge-inprogress'
                                        : 'badge-pending'); ?>">
                                <?= $t['status']; ?>
                            </span>
                        </td>

                        <!-- COUNTDOWN / DUE DATE -->
                        <td>
                            <?php if ($t['due_date']): ?>
                                <span class="countdown" data-duedate="<?= $t['due_date']; ?>">Loading...</span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>

                        <!-- TAG LABEL -->
                        <td>
                            <?php if ($t['tag']): ?>
                                <span class="badge tag-badge bg-<?=
                                                                match ($t['tag']) {
                                                                    'important' => 'danger',
                                                                    'work'      => 'primary',
                                                                    'personal'  => 'info',
                                                                    'urgent'    => 'warning',
                                                                    default     => 'secondary'
                                                                }
                                                                ?>">
                                    <?= ucfirst($t['tag']); ?>
                                </span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>

                        <!-- FILE -->
                        <td>
                            <?= $t['attachment']
                                ? '<a href="' . $t['attachment'] . '" target="_blank">View</a>'
                                : '—'
                            ?>
                        </td>

                        <!-- ACTIONS -->
                        <td>
                            <button class="btn btn-primary btn-sm action-btn"
                                onclick='editTodo(<?= json_encode($t); ?>)'
                                data-bs-toggle="modal"
                                data-bs-target="#editModal">
                                Edit
                            </button>

                            <a href="?delete=<?= $t['id']; ?>"
                                class="btn btn-danger btn-sm action-btn"
                                onclick="return confirm('Delete?')">Del</a>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- PAGINATION -->
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link"
                            href="?page=<?= $i; ?>&<?= http_build_query([
                                                        'search' => $_GET['search'] ?? '',
                                                        'status' => $_GET['status'] ?? '',
                                                        'category' => $_GET['category'] ?? ''
                                                    ]); ?>">
                            <?= $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>

    </div>

    <!-- ADD MODAL -->
    <div class="modal fade" id="addModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <?php include 'todo_form.php'; ?>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <?php include 'todo_form.php'; ?>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script>
        function editTodo(t) {
            document.getElementById('edit_id').value = t.id;
            document.querySelector('#editModal [name="title"]').value = t.title;
            document.querySelector('#editModal [name="description"]').value = t.description;
            document.querySelector('#editModal [name="status"]').value = t.status;
            document.querySelectorAll('#editModal [name="priority"]').forEach(r => r.checked = r.value === t.priority);
            document.querySelector('#editModal [name="notifications"]').checked = (t.notifications == 1);
            document.querySelector('#editModal [name="due_date"]').value = t.due_date;
            document.querySelector('#editModal [name="category_id"]').value = t.category_id || '';
            if (t.tag) document.querySelector('#editModal [name="tag"]').value = t.tag;
        }
    </script>

    <!-- COUNTDOWN TIMER -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>