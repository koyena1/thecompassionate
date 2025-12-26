<?php 
    // Get the current file name
    $current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* CSS Variables - RESTORED ORIGINAL */
    :root {
        --sidebar-width: 260px;
        --text-dark: #2D3436;
        --text-gray: #636e72; 
        --white: #FFFFFF;
        --brand-brown: #7B3F00; 
        --sidebar-bg: #FFFFFF; 
        --menu-text: var(--text-gray); 
    }

    /* DARK MODE OVERRIDES - RESTORED ORIGINAL */
    body.dark-mode {
        --sidebar-bg: #121212; 
        --menu-text: #E0E0E0;  
        --text-dark: #FFFFFF;
    }

    /* Sidebar Container - RESTORED ORIGINAL STYLES */
    .sidebar {
        width: var(--sidebar-width);
        background: var(--sidebar-bg);
        padding: 30px 20px;
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100%;
        z-index: 1001;
        left: 0;
        top: 0;
        overflow-y: auto;
        box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        border-right: 1px solid rgba(0,0,0,0.1);
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), background 0.4s ease;
    }

    /* --- ADDED TOGGLE LOGIC (NON-DESTRUCTIVE) --- */
    body.sidebar-off .sidebar {
        transform: translateX(-100%);
    }

    /* Close Button (X) - Added as requested */
    .sidebar-close-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        background: none;
        border: none;
        font-size: 22px;
        cursor: pointer;
        color: var(--text-dark); /* Contrast-aware color */
        display: none; /* Hidden on desktop by default */
        transition: transform 0.3s ease;
    }
    
    .sidebar-close-btn:hover { transform: rotate(90deg); color: var(--brand-brown); }

    @media (max-width: 992px) {
        .sidebar { transform: translateX(-100%); }
        body.mobile-sidebar-on .sidebar { transform: translateX(0); }
        .sidebar-close-btn { display: block; }
    }

    /* Menu Item Styling - RESTORED ORIGINAL */
    .menu-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 14px 18px;
        color: var(--menu-text) !important;
        text-decoration: none;
        border-radius: 15px;
        margin-bottom: 8px;
        transition: all 0.3s ease;
        font-size: 14px;
        font-weight: 500;
        position: relative;
    }

    .menu-item.active { background: var(--brand-brown); color: #fff !important; }
    .menu-item:hover { background: rgba(123, 63, 0, 0.05); }
    
    .menu-item[href="logout.php"]:hover {
        background: rgba(231, 76, 60, 0.1) !important;
    }

    .logo { color: var(--text-dark); font-weight: 700; font-size: 20px; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
</style>

<nav class="sidebar">
    <button class="sidebar-close-btn" id="sidebar-close-btn">
        <i class="fa-solid fa-xmark"></i>
    </button>

    <div class="logo">
        <i class="fa-solid fa-notes-medical"></i>
        <span>Patient Center</span>
    </div>
    
    <a href="dashboard.php" class="menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-table-cells-large"></i> 
        <span>Overview</span>
    </a>

    <a href="about the doctor.php" class="menu-item <?php echo ($current_page == 'about the doctor.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-id-card-clip"></i> 
        <span>About the doctor</span>
    </a>
    
    <a href="book_appointment.php" class="menu-item <?php echo ($current_page == 'book_appointment.php') ? 'active' : ''; ?>">
        <i class="fa-regular fa-calendar-plus"></i>
        <span>Book Appointment</span>
    </a>
    
    <a href="myapp.php" class="menu-item <?php echo ($current_page == 'myapp.php') ? 'active' : ''; ?>">
        <i class="fa-regular fa-calendar-check"></i> 
        <span>My Appointments</span>
    </a>
    
    <a href="prescrip.php" class="menu-item <?php echo ($current_page == 'prescrip.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-file-medical"></i> 
        <span>Prescriptions</span>
    </a>

    <a href="healtht.php" class="menu-item <?php echo ($current_page == 'healtht.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-heart-pulse"></i> 
        <span>Health Timeline</span>
    </a>
    
    <a href="payment.php" class="menu-item <?php echo ($current_page == 'payment.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-clock-rotate-left"></i> 
        <span>Payment History</span>
    </a>
    
    <a href="notification.php" class="menu-item <?php echo ($current_page == 'notification.php') ? 'active' : ''; ?>">
        <i class="fa-regular fa-bell"></i> 
        <span>Notifications</span>
    </a>
    
    <a href="followup.php" class="menu-item <?php echo ($current_page == 'followup.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-comments"></i> 
        <span>Follow-up Chat</span>
    </a>

    <a href="documentation.php" class="menu-item <?php echo ($current_page == 'documentation.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-book"></i> 
        <span>Documentation</span>
    </a>
    
    <a href="profile.php" class="menu-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" style="margin-top: auto; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 20px;">
        <i class="fa-solid fa-user-gear"></i> 
        <span>Profile Settings</span>
    </a>
    
    <a href="logout.php" class="menu-item" style="margin-top: 8px; color: #e74c3c !important;">
        <i class="fa-solid fa-right-from-bracket"></i> 
        <span>Logout</span>
    </a>
</nav>