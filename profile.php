<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
include 'db.php';
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = trim($_POST['bio']);
    $profile_pic = $user['profile_pic'];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $file = $_FILES['profile_pic'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png']) && $file['size'] < 2000000) {
            $new_name = "uploads/" . time() . "_" . $file['name'];
            if (move_uploaded_file($file['tmp_name'], $new_name)) {
                $profile_pic = $new_name;
            }
        }
    }
    $update = $pdo->prepare("UPDATE users SET bio = ?, profile_pic = ? WHERE id = ?");
    $update->execute([$bio, $profile_pic, $user_id]);
    header("Location: profile.php");
    exit;
}
?>
<?php
// Ensure $user array keys exist
$user['tag'] = $user['tag'] ?? '';
$user['bio'] = $user['bio'] ?? '';
$user['profile_pic'] = $user['profile_pic'] ?? 'default.png';
$user['username'] = $user['username'] ?? 'Anonymous';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Profile</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/custom.css">

    <style>
        /* Profile Page Specific Styles */
        .countdown {
            font-weight: bold;
            font-size: 0.95rem;
        }

        #loadingSpinner {
            z-index: 9999;
        }

        .profile-avatar {
            max-width: 180px;
        }

        .dark-toggle {
            cursor: pointer;
            user-select: none;
            font-weight: bold;
        }
    </style>

    <script>
        // Dark Mode Loader & Toggle
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

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="d-none position-fixed top-50 start-50 translate-middle">
        <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div class="container profile-container py-4">
        <div class="row">

            <!-- Profile Info -->
            <div class="col-md-4 text-center">
                <?php
                $profilePic = $user['profile_pic'] ?? 'default.png';
                $username = $user['username'] ?? 'User';
                $userTag = $user['tag'] ?? '';
                $bio = $user['bio'] ?? '';
                $tagColors = [
                    'important' => 'danger',
                    'work' => 'primary',
                    'personal' => 'info',
                    'urgent' => 'warning'
                ];
                $badgeClass = $tagColors[$userTag] ?? 'secondary';
                ?>
                <img src="<?php echo htmlspecialchars($profilePic); ?>" class="profile-avatar img-fluid rounded-circle mb-3" alt="Profile Picture">

                <h4 class="profile-username"><?php echo htmlspecialchars($username); ?></h4>

                <?php if (!empty($userTag)): ?>
                    <span class="badge bg-<?php echo htmlspecialchars($badgeClass); ?>">
                        <?php echo htmlspecialchars(ucfirst($userTag)); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Profile Form -->
            <div class="col-md-8">

                <!-- About Me Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">About Me</h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($bio ?: 'No bio.')); ?></p>
                    </div>
                </div>

                <!-- Update Profile Card -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Update Profile</h5>
                    </div>
                    <div class="card-body">
                        <form id="profileForm" enctype="multipart/form-data">

                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea name="bio" id="bio" class="form-control" rows="4"><?php echo htmlspecialchars($bio); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Label / Tag</label>
                                <select name="tag" class="form-select">
                                    <option value="">None</option>
                                    <option value="important" <?php if ($userTag === "important") echo "selected"; ?>>Important</option>
                                    <option value="work" <?php if ($userTag === "work") echo "selected"; ?>>Work</option>
                                    <option value="personal" <?php if ($userTag === "personal") echo "selected"; ?>>Personal</option>
                                    <option value="urgent" <?php if ($userTag === "urgent") echo "selected"; ?>>Urgent</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="profile_pic" class="form-label">Profile Picture (JPG/PNG, &lt;2MB)</label>
                                <input type="file" name="profile_pic" id="profile_pic" class="form-control" accept="image/*">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Save Changes</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- JS Scripts -->
    <script>
        // Profile Update via AJAX
        const profileForm = document.getElementById('profileForm');
        const spinner = document.getElementById('loadingSpinner');

        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            spinner.classList.remove("d-none");

            const formData = new FormData(profileForm);

            fetch("update_profile.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.text())
                .then(response => {
                    spinner.classList.add("d-none");
                    alert("Profile updated!");
                    location.reload();
                })
                .catch(err => {
                    spinner.classList.add("d-none");
                    alert("Error updating profile.");
                });
        });

        // Optional: Countdown Timer
        document.querySelectorAll("[data-duedate]").forEach(el => {
            const target = new Date(el.dataset.duedate).getTime();

            setInterval(() => {
                const now = Date.now();
                const diff = target - now;

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
