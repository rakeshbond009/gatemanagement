<div class="sidebar">
    <h2>Security Panel</h2>
    <nav>
        <ul>
            <li><a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>Dashboard</a></li>
            <li><a href="check_in.php" <?php echo basename($_SERVER['PHP_SELF']) == 'check_in.php' ? 'class="active"' : ''; ?>>Visitor Check-in</a></li>
            <li><a href="check_out.php" <?php echo basename($_SERVER['PHP_SELF']) == 'check_out.php' ? 'class="active"' : ''; ?>>Visitor Check-out</a></li>
            <li><a href="visitors.php" <?php echo basename($_SERVER['PHP_SELF']) == 'visitors.php' ? 'class="active"' : ''; ?>>All Visitors</a></li>
            <li><a href="pre_approved.php" <?php echo basename($_SERVER['PHP_SELF']) == 'pre_approved.php' ? 'class="active"' : ''; ?>>Pre-approved List</a></li>
            <li><a href="profile.php" <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'class="active"' : ''; ?>>My Profile</a></li>
            <li><a href="../../api/auth.php?logout=1">Logout</a></li>
        </ul>
    </nav>
</div>
