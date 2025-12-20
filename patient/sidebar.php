<?php 
    // Get the current file name
    $current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* CSS Variables */
    :root {
        --sidebar-width: 260px;
        --text-dark: #2D3436;
        --text-light: rgba(255, 255, 255, 0.7);
        --white: #FFFFFF;
        --primary-blue: #1FB6FF;
        --accent-brown: #5D3000; /* Darker brown for contrast */
        --glass-bg: rgba(255, 255, 255, 0.1);
    }

    /* Custom Scrollbar for Sidebar */
    .sidebar::-webkit-scrollbar {
        width: 5px;
    }
    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }
    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
    }

    /* Sidebar Container */
    .sidebar {
        width: var(--sidebar-width);
        background: linear-gradient(180deg, #7B3F00 0%, #4a2600 100%);
        padding: 30px 20px;
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100%;
        z-index: 1001;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        left: 0;
        top: 0;
        overflow-y: auto;
        box-shadow: 4px 0 15px rgba(0,0,0,0.1);
    }

    /* Logo Styling */
    .logo {
        font-size: 22px;
        font-weight: 800;
        margin-bottom: 40px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--white);
        padding: 0 10px;
        letter-spacing: -0.5px;
    }

    .logo i {
        background: var(--white);
        color: #7B3F00;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 18px;
    }

    /* Menu Item Styling */
    .menu-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 14px 18px;
        color: var(--text-light) !important;
        text-decoration: none;
        border-radius: 15px;
        margin-bottom: 8px;
        transition: all 0.3s ease;
        font-size: 14px;
        font-weight: 500;
        position: relative;
    }

    /* Hover States */
    .menu-item:hover {
        background-color: var(--glass-bg);
        color: var(--white) !important;
        transform: translateX(5px);
    }

    /* Active State */
    .menu-item.active {
        background: var(--white);
        color: #7B3F00 !important;
        font-weight: 700;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .menu-item.active i {
        color: #7B3F00;
    }

    .menu-item i {
        font-size: 18px;
        width: 25px;
        text-align: center;
        transition: 0.3s;
    }

    /* Added a small indicator for the active page */
    .menu-item.active::before {
        content: "";
        position: absolute;
        left: -10px;
        height: 20px;
        width: 4px;
        background: var(--white);
        border-radius: 0 10px 10px 0;
    }

    /* Responsive: Mobile view handling */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }
        
        body.toggled .sidebar {
            transform: translateX(0);
        }
    }
</style>

<nav class="sidebar">
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
        <i class="fa-regular fa-credit-card"></i> 
        <span>Payments</span>
    </a>
    
    <a href="notification.php" class="menu-item <?php echo ($current_page == 'notification.php') ? 'active' : ''; ?>">
        <i class="fa-regular fa-bell"></i> 
        <span>Notifications</span>
    </a>

    <a href="documentation.php" class="menu-item <?php echo ($current_page == 'documentation.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-book"></i> 
        <span>Documentation</span>
    </a>
    
    <a href="profile.php" class="menu-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <i class="fa-solid fa-user-gear"></i> 
        <span>Profile Settings</span>
    </a>
</nav>