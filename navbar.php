<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">

        <!-- Theme toggle button -->
        <button id="themeToggle" class="btn btn-outline-light me-2">🌙</button>

        <a class="navbar-brand" href="dashboard.php"><strong>TODO App</strong></a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
                <li class="nav-item"><a class="nav-link" href="todos.php">TODOs</a></li>
            </ul>
            <a href="logout.php" class="btn btn-outline-light">Logout</a>
        </div>
    </div>

    <script>
        const btn = document.getElementById("themeToggle");

        // Load theme on start
        const savedTheme = localStorage.getItem("theme");
        if (savedTheme === "dark") {
            document.documentElement.setAttribute("data-theme", "dark");
            btn.textContent = "☀️";
        } else {
            btn.textContent = "🌙";
        }

        // Toggle theme
        btn.addEventListener("click", () => {
            if (document.documentElement.getAttribute("data-theme") === "dark") {
                document.documentElement.removeAttribute("data-theme");
                localStorage.setItem("theme", "light");
                btn.textContent = "🌙";
            } else {
                document.documentElement.setAttribute("data-theme", "dark");
                localStorage.setItem("theme", "dark");
                btn.textContent = "☀️";
            }
        });
    </script>
</nav>