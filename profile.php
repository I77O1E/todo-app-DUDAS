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

// Ensure keys exist
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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/custom.css">

    <style>
        .countdown { font-weight: bold; font-size: 0.95rem; }
        #loadingSpinner { z-index: 9999; }
        .profile-avatar { max-width: 180px; }
        .dark-toggle { cursor: pointer; user-select: none; font-weight: bold; }
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
        });
    </script>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div id="loadingSpinner" class="d-none position-fixed top-50 start-50 translate-middle">
        <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div class="container profile-container py-4">
        <div class="row">

            <!-- PROFILE INFO -->
            <div class="col-md-4 text-center">
                <?php
                $profilePic = $user['profile_pic'];
                $username = $user['username'];
                $userTag = $user['tag'];
                $bio = $user['bio'];

                $tagColors = [
                    'important' => 'danger',
                    'work'      => 'primary',
                    'personal'  => 'info',
                    'urgent'    => 'warning'
                ];
                $badgeClass = $tagColors[$userTag] ?? 'secondary';
                ?>
                <img src="<?php echo 'todo-app/' . htmlspecialchars($profilePic) . '?v=' . time(); ?>" class="profile-avatar img-fluid rounded-circle mb-3" alt="Profile Picture">


                <h4><?php echo htmlspecialchars($username); ?></h4>

                <?php if (!empty($userTag)): ?>
                    <span class="badge bg-<?php echo $badgeClass; ?>">
                        <?php echo htmlspecialchars(ucfirst($userTag)); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- PROFILE FORM -->
            <div class="col-md-8">

                <div class="card mb-4">
                    <div class="card-header"><h5>About Me</h5></div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($bio ?: 'No bio.')); ?></p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5>Update Profile</h5></div>
                    <div class="card-body">

                        <form id="profileForm" method="POST" enctype="multipart/form-data">

                            <div class="mb-3">
                                <label class="form-label">Bio</label>
                                <textarea name="bio" id="bio" class="form-control" rows="4"><?php echo htmlspecialchars($bio); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Label / Tag</label>
                                <select name="tag" class="form-select">
                                    <option value="">None</option>
                                    <option value="important" <?php if ($userTag=="important") echo "selected"; ?>>Important</option>
                                    <option value="work" <?php if ($userTag=="work") echo "selected"; ?>>Work</option>
                                    <option value="personal" <?php if ($userTag=="personal") echo "selected"; ?>>Personal</option>
                                    <option value="urgent" <?php if ($userTag=="urgent") echo "selected"; ?>>Urgent</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Profile Picture (JPG/PNG, &lt;10MB)</label>
                                <input type="file" name="profile_pic" id="profile_pic" class="form-control" accept="image/*">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Save Changes</button>

                        </form>

                    </div>
                </div>

            </div>

        </div>
    </div>

    <script>
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
                const profileImg = document.querySelector('.profile-avatar');
                profileImg.src = profileImg.src.split('?')[0] + '?v=' + new Date().getTime();
            })
            .catch(err => {
                spinner.classList.add("d-none");
                alert("Error updating profile.");
            });
        });
    </script>

</body>
</html>