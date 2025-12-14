<?php
    // Get the current file name
    $current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* CSS Variables */
    :root {
        --sidebar-width: 240px;
        --text-dark: #2D3436;
        --text-light: #A0A4A8;
        --white: #FFFFFF;
        --primary-blue: #1FB6FF;
    }

    /* Sidebar Container */
    .sidebar {
        width: var(--sidebar-width);
        background: #7B3F00; /* Brown background */
        padding: 30px;
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100%;
        z-index: 999;
        transition: transform 0.3s ease;
        left: 0;
        top: 0;
        /* FIX: Allow scrolling if the menu is taller than the screen */
        overflow-y: auto; 
    }

    /* Logo Styling */
    .logo {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 50px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--white); 
        flex-shrink: 0; 
    }

    /* Menu Item Styling */
    .menu-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        color: var(--text-light);
        text-decoration: none;
        border-radius: 12px;
        margin-bottom: 5px;
        transition: 0.3s;
        font-size: 14px;
        flex-shrink: 0; 
    }

    /* Hover and Active States */
    .menu-item:hover, 
    .menu-item.active {
        background-color: var(--text-dark);
        color: var(--white) !important;
    }

    /* Responsive: Mobile view handling */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        body.toggled .sidebar {
            transform: translateX(0);
        }
    }
</style>

<nav class="sidebar">
    <div class="logo">
        <i class="fa-solid fa-notes-medical" style="color: var(--white);"></i>
        <span>Patient Dashboard</span>
    </div>
    
    <a href="dashboard.php" id="nav-overview" class="menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" style="color: var(--white);">
        <i class="fa-solid fa-table-cells-large"></i> 
        <span>Overview</span>
    </a>
    
    <a href="book_appointment.php" id="nav-booking" class="menu-item <?php echo ($current_page == 'book_appointment.php') ? 'active' : ''; ?>" style="color: var(--white);">
        <i class="fa-regular fa-calendar-plus"></i>
        <span>Book Appointment</span>
    </a>
    
    <a href="myapp.php" id="nav-appointments" class="menu-item <?php echo ($current_page == 'myapp.php') ? 'active' : ''; ?>" style="color: var(--white);">
        <i class="fa-regular fa-calendar-check"></i> 
        <span>My Appointments</span>
    </a>
    
<a href="prescrip.php" id="nav-prescriptions" class="menu-item <?php echo ($current_page == 'prescrip.php') ? 'active' : ''; ?>" style="color: var(--white);">
    <i class="fa-solid fa-file-medical"></i> 
    <span>Prescriptions</span>
</a>

<a href="healtht.php" id="nav-timeline" class="menu-item <?php echo ($current_page == 'healtht.php') ? 'active' : ''; ?>" style="color: var(--white);">
        <i class="fa-solid fa-heart-pulse"></i> 
        <span>Health Timeline</span>
    </a>
    
<a href="payment.php" id="nav-payments" class="menu-item <?php echo ($current_page == 'payment.php') ? 'active' : ''; ?>" style="color: var(--white);">
    <i class="fa-regular fa-credit-card"></i> 
    <span>Payments</span>
</a>
    
<a href="notification.php" id="nav-notifications" class="menu-item <?php echo ($current_page == 'notification.php') ? 'active' : ''; ?>" style="color: var(--white);">
        <i class="fa-regular fa-bell"></i> 
        <span>Notifications</span>
    </a>
    
 <a href="profile.php" id="nav-profile" class="menu-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" style="color: var(--white);">
        <i class="fa-solid fa-user-gear"></i> 
        <span>Profile & Settings</span>
    </a>
</nav>