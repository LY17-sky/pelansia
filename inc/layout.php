<?php
if (isset($_GET['notif_read_all'])) {
    $uid = $_SESSION['user_id'] ?? 0;
    if ($uid) {
        try {
            getDbConn()->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$uid]);
        } catch(PDOException $e) {}
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}
$__userId = $_SESSION['user_id'] ?? 0;
$__notifications = [];
$__unreadCount = 0;
$__puskesmasNama = '-';
if ($__userId) {
    $__notifications = function_exists('getNotifications') ? getNotifications($__userId, 5) : [];
    $__unreadCount = function_exists('getUnreadNotifCount') ? getUnreadNotifCount($__userId) : 0;
    $__puskesmasNama = function_exists('dbFetch') ? (dbFetch("SELECT nama_puskesmas FROM puskesmas LIMIT 1")['nama_puskesmas'] ?? '-') : '-';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Sistem Pelaporan Lansia' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4A90D9;
            --primary-dark: #3570B5;
            --primary-light: #2C5282;
            --secondary: #64748b;
            --dark: #1e293b;
            --light: #f8fafc;
            --bg: #f1f5f9;
            --white: #ffffff;
            --border: #e2e8f0;
            --success: #059669;
            --warning: #D97706;
            --danger: #DC2626;
            --info: #3b82f6;
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background-color: var(--bg);
            font-size: 14px;
            color: var(--dark);
        }
        
        /* ===== SIDEBAR ===== */
        .sidebar {
            background: #4A90D9;
            min-height: 100vh;
            position: fixed;
            width: 260px;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
            box-shadow: 4px 0 20px rgba(177, 201, 239, 0.3);
        }
        
        .sidebar-brand {
            padding: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            background: rgba(0,0,0,0.1);
        }
        
        .sidebar-brand h4 {
            color: var(--white);
            font-weight: 700;
            letter-spacing: 1px;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .sidebar-brand span {
            color: rgba(255,255,255,0.8);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .sidebar-menu {
            padding: 16px 0;
        }
        
        .sidebar-menu a {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            margin: 4px 12px;
            border-radius: 10px;
        }
        
        .sidebar-menu a:hover {
            color: var(--white);
            background: rgba(255,255,255,0.15);
            transform: translateX(4px);
        }
        
        .sidebar-menu a.active {
            color: var(--white);
            background: rgba(255,255,255,0.25);
            border-left-color: var(--white);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .sidebar-menu a i {
            font-size: 18px;
            width: 24px;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px 24px;
            border-top: 1px solid rgba(255,255,255,0.2);
            background: rgba(0,0,0,0.1);
        }
        
        .sidebar-footer a {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .sidebar-footer a:hover {
            color: var(--white);
            background: rgba(239, 68, 68, 0.3);
        }
        
        .main-content {
            margin-left: 260px;
            padding: 88px 32px 24px 32px;
            min-height: 100vh;
            background: linear-gradient(180deg, var(--bg) 0%, #e2e8f0 100%);
        }
        
        /* ===== TOP NAVBAR ===== */
        .top-navbar {
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            z-index: 9998;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            height: 64px;
            background: #4A90D9;
            box-shadow: 0 2px 12px rgba(177, 201, 239, 0.25);
            border-radius: 0;
        }
        
        .top-navbar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.05) 50%, transparent 100%);
        }
        
        .top-navbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
            z-index: 1;
        }
        
        /* ===== CARD ICON BG ===== */
        .card-icon-bg {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            margin-right: 12px;
            background: #4A90D9;
            box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3);
        }
        
        .top-navbar-left .menu-toggle-btn {
            display: none;
            width: 36px;
            height: 36px;
            border: none;
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
            color: white;
            font-size: 18px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .top-navbar-left .menu-toggle-btn:hover {
            background: rgba(255,255,255,0.25);
        }
        
        .top-navbar-left .page-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .top-navbar-left .page-icon {
            width: 38px;
            height: 38px;
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }
        
        .top-navbar-left .welcome-text {
            display: none;
        }
        
        .top-navbar-left .page-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--white);
            margin: 0;
            letter-spacing: 0;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px;
        }
        
        .top-navbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            z-index: 1;
        }
        
        .top-navbar .dropdown-container {
            position: relative;
            z-index: 10000;
        }
        
        .top-navbar .nav-icon-btn {
            width: 38px;
            height: 38px;
            border: none;
            background: rgba(255,255,255,0.12);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.9);
            font-size: 16px;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
        }
        
        .top-navbar .nav-icon-btn:hover {
            background: rgba(255,255,255,0.25);
            color: white;
        }
        
        .top-navbar .nav-icon-btn .badge-count {
            position: absolute;
            top: 4px;
            right: 4px;
            min-width: 16px;
            height: 16px;
            padding: 0 3px;
            background: #ef4444;
            color: white;
            border-radius: 8px;
            font-size: 9px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--primary);
        }
        
        .top-navbar .dropdown-menu-custom {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15), 0 1px 3px rgba(0,0,0,0.1);
            min-width: 280px;
            overflow: visible;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.25s ease;
            border: 1px solid var(--border);
        }
        
        .top-navbar .dropdown-menu-custom.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .top-navbar .dropdown-header {
            padding: 16px 20px;
            background: #4A90D9;
            color: white;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .top-navbar .dropdown-header .mark-read {
            font-size: 12px;
            color: rgba(255,255,255,0.8);
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .top-navbar .dropdown-header .mark-read:hover {
            color: white;
        }
        
        .top-navbar .dropdown-item-custom {
            padding: 14px 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .top-navbar .dropdown-item-custom:hover {
            background: rgba(30, 58, 95, 0.05);
        }
        
        .top-navbar .dropdown-item-custom:last-child {
            border-bottom: none;
        }
        
        .top-navbar .notif-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .top-navbar .notif-icon.info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1d4ed8;
        }
        
        .top-navbar .notif-icon.warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706;
        }
        
        .top-navbar .notif-icon.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }
        
        .top-navbar .notif-content {
            flex: 1;
        }
        
        .top-navbar .notif-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 2px;
        }
        
        .top-navbar .notif-desc {
            font-size: 12px;
            color: var(--secondary);
            line-height: 1.4;
        }
        
        .top-navbar .notif-time {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px;
        }
        
        .top-navbar .dropdown-footer-custom {
            padding: 12px 20px;
            text-align: center;
            background: var(--bg);
            border-top: 1px solid var(--border);
        }
        
        .top-navbar .dropdown-footer-custom a {
            color: var(--primary);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .top-navbar .dropdown-footer-custom a:hover {
            color: var(--primary-dark);
        }
        
        .top-navbar .user-profile {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 14px 5px 5px;
            background: rgba(255,255,255,0.12);
            border-radius: 24px;
            color: var(--white);
            text-decoration: none;
            transition: all 0.25s ease;
            cursor: pointer;
            position: relative;
            border: 1px solid rgba(255,255,255,0.15);
        }
        
        .top-navbar .user-profile:hover {
            background: rgba(255,255,255,0.2);
            color: var(--white);
        }
        
        .top-navbar .user-avatar {
            width: 28px;
            height: 28px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 600;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .top-navbar .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        
        .top-navbar .user-name {
            font-size: 12px;
            font-weight: 600;
        }
        
        .top-navbar .user-role {
            font-size: 10px;
            color: rgba(255,255,255,0.75);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .top-navbar .user-profile .dropdown-arrow {
            font-size: 12px;
            margin-left: 4px;
            transition: transform 0.2s;
        }
        
        .top-navbar .user-profile.show .dropdown-arrow {
            transform: rotate(180deg);
        }
        
        .top-navbar .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15), 0 1px 3px rgba(0,0,0,0.1);
            min-width: 220px;
            overflow: visible;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.25s ease;
            border: 1px solid var(--border);
        }
        
        .top-navbar .profile-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .top-navbar .profile-dropdown-header {
            padding: 20px;
            background: #4A90D9;
            color: white;
            text-align: center;
        }
        
        .top-navbar .profile-dropdown-header .avatar-lg {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 600;
            margin: 0 auto 10px;
            border: 3px solid rgba(255,255,255,0.3);
        }
        
        .top-navbar .profile-dropdown-header .name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        
        .top-navbar .profile-dropdown-header .email {
            font-size: 12px;
            color: rgba(255,255,255,0.75);
        }
        
        .top-navbar .profile-dropdown-item {
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.2s;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
        }
        
        .top-navbar .profile-dropdown-item:hover {
            background: rgba(30, 58, 95, 0.05);
            color: var(--primary);
        }
        
        .top-navbar .profile-dropdown-item:last-child {
            border-bottom: none;
            color: var(--danger);
        }
        
        .top-navbar .profile-dropdown-item:last-child:hover {
            background: linear-gradient(90deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.15) 100%);
            color: var(--danger);
        }
        
        .top-navbar .profile-dropdown-item i {
            font-size: 16px;
            width: 20px;
        }
        
        .top-navbar .settings-group {
            padding: 12px 20px;
            border-top: 1px solid var(--border);
        }
        
        .top-navbar .settings-group-title {
            font-size: 11px;
            font-weight: 600;
            color: var(--secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .top-navbar .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        
        .top-navbar .settings-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            background: var(--bg);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 12px;
            color: var(--secondary);
        }
        
        .top-navbar .settings-item:hover {
            background: var(--primary);
            color: white;
        }
        
        .top-navbar .settings-item i {
            font-size: 14px;
        }
        
        @media (max-width: 991px) {
            .top-navbar-left .menu-toggle-btn {
                display: flex;
            }
        }
        
        .top-navbar .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 16px 6px 6px;
            background: rgba(255,255,255,0.2);
            border-radius: 30px;
            color: var(--white);
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .top-navbar .user-profile:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
            color: var(--white);
        }
        
        .top-navbar .user-avatar {
            width: 34px;
            height: 34px;
            background: rgba(255,255,255,0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 600;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .top-navbar .user-name {
            font-size: 13px;
            font-weight: 500;
        }
        
        /* ===== PAGE HEADER ===== */
        .page-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--border);
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }
        
        .page-header p {
            color: var(--secondary);
            margin: 4px 0 0;
        }
        
        /* ===== CARDS ===== */
        .custom-card {
            background: var(--white);
            border: none;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
        }
        
        .custom-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #4A90D9;
        }
        
        .custom-card {
            position: relative;
        }
        
        .custom-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(177, 201, 239, 0.15);
        }
        
        .custom-card .card-body {
            padding: 20px 24px;
        }
        
        /* ===== STAT CARDS ===== */
        .stat-card {
            padding: 20px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
        }
        
        .stat-card .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-card .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            line-height: 1;
        }
        
        .stat-card .stat-info p {
            font-size: 13px;
            color: var(--secondary);
            margin: 4px 0 0;
        }
        
        /* ===== TABLE ===== */
        .table-custom {
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
        }
        
        .table-custom table {
            margin-bottom: 0;
        }
        
        .table-custom th {
            background: #4A90D9;
            padding: 14px 16px;
            font-weight: 600;
            color: var(--white);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }
        
        .table-custom td {
            padding: 14px 16px;
            vertical-align: middle;
            border-color: var(--border);
        }
        
        .table-custom tbody tr {
            transition: background 0.2s ease;
        }
        
        .table-custom tbody tr:hover {
            background: rgba(30, 58, 95, 0.05);
        }
        
        /* ===== FORMS ===== */
        .form-control, .form-select {
            padding: 10px 14px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(30, 58, 95, 0.15);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 6px;
            font-size: 13px;
        }
        
        /* ===== BUTTONS ===== */
        .btn-primary {
            background: #4A90D9;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(177, 201, 239, 0.3);
        }
        
        .btn-primary:hover {
            background: #3570B5;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(30, 58, 95, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary) 0%, #475569 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #475569 0%, #334155 100%);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            color: white;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            color: white;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-danger.btn-sm {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            border: none;
        }
        
        .btn-danger.btn-sm:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }
        
        .btn-primary.btn-sm {
            background: #4A90D9;
            border: none;
        }
        
        .btn-primary.btn-sm:hover {
            background: #3570B5;
        }
        
        .btn-primary.btn-sm:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #0d6efd 100%);
        }
        
        /* ===== BADGES ===== */
        .badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* ===== LOGIN PAGE ===== */
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #4A90D9;
            position: relative;
            overflow: hidden;
        }
        
        .login-page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255,255,255,0.05) 0%, transparent 30%);
        }
        
        .login-card {
            background: var(--white);
            border-radius: 24px;
            padding: 48px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            position: relative;
            z-index: 1;
        }
        
        .login-card h3 {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }
        
        .login-card p {
            color: var(--secondary);
            margin-bottom: 32px;
        }

        /* Login form hover effects */
        .login-card .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30, 58, 95, 0.15);
        }

        .login-card .input-group-text:hover {
            background: rgba(177, 201, 239, 0.1);
            border-color: var(--primary);
        }

        .login-card .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(177, 201, 239, 0.4);
        }

        .login-card .form-control:hover {
            border-color: var(--primary-light);
        }
        
        /* Card interactivity */
        .stat-card-clickable {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .stat-card-clickable:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2) !important;
        }
        .category-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            border-radius: 12px;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
        }
        
        /* ===== ALERTS ===== */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 14px 18px;
            font-weight: 500;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-left: 4px solid var(--success);
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }
        
        /* ===== MOBILE TOGGLE ===== */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 1001;
            width: 48px;
            height: 48px;
            background: #4A90D9;
            border: none;
            border-radius: 14px;
            color: white;
            font-size: 22px;
            box-shadow: 0 4px 15px rgba(177, 201, 239, 0.4);
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(30, 58, 95, 0.8);
            z-index: 999;
        }
        
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .sidebar-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
                padding: 88px 16px 24px;
            }
            
            .top-navbar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 9998;
                margin: 0;
                padding: 0 16px;
                height: 64px;
            }
            
            .top-navbar-left {
                gap: 10px;
            }
            
            .top-navbar-left .menu-toggle-btn {
                display: flex;
            }
            
            .top-navbar-left .page-info {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .top-navbar-left .page-icon {
                width: 30px;
                height: 30px;
                font-size: 14px;
                border-radius: 8px;
            }
            
            .top-navbar-left .page-title {
                font-size: 15px;
                font-weight: 600;
            }
            
            .top-navbar-left .welcome-text {
                display: none;
            }
            
            .top-navbar-right {
                gap: 6px;
            }
            
            .top-navbar .nav-icon-btn {
                width: 32px;
                height: 32px;
                background: rgba(255,255,255,0.12);
                color: white;
                font-size: 14px;
            }
            
            .top-navbar .nav-icon-btn:hover {
                background: rgba(255,255,255,0.2);
            }
            
            .top-navbar .nav-icon-btn .badge-count {
                top: 2px;
                right: 2px;
                min-width: 12px;
                height: 12px;
                font-size: 7px;
            }
            
            .top-navbar .user-info {
                display: none;
            }
            
            .top-navbar .user-profile {
                padding: 3px 6px 3px 3px;
                background: rgba(255,255,255,0.1);
                border: 1px solid rgba(255,255,255,0.15);
            }
            
            .top-navbar .user-avatar {
                width: 20px;
                height: 20px;
                font-size: 10px;
            }
            
            .top-navbar .dropdown-menu-custom,
            .top-navbar .profile-dropdown {
                position: fixed;
                top: 64px;
                left: 10px;
                right: 10px;
                width: auto;
            }
        }
        
        @media (max-width: 767px) {
            .stat-card {
                padding: 16px;
            }
            
            .stat-card .stat-icon {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }
            
            .stat-card .stat-info h3 {
                font-size: 24px;
            }
            
            .login-card {
                padding: 32px 24px;
                margin: 16px;
            }
        }
        
        /* ===== ANIMATIONS ===== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .custom-card {
            animation: fadeInUp 0.4s ease-out;
        }
    </style>
</head>
<body>
<?php if (isLoggedIn()): ?>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h4 style="font-family: 'Poppins', sans-serif; font-weight: 700; letter-spacing: 2px;"><i class="bi bi-heart-pulse me-2"></i>PELANSIA</h4>
        <span>Sistem Pelaporan Lansia</span>
    </div>
    <div class="sidebar-menu">
        <a href="<?= $base_url ?>/dashboard.php" class="<?= $page === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <?php if (isSuperAdmin()): ?>
        <a href="<?= $base_url ?>/lansia.php" class="<?= $page === 'lansia' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Data Lansia
        </a>
        <a href="<?= $base_url ?>/laporan.php" class="<?= $page === 'laporan' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text"></i> Laporan
        </a>
        <?php else: ?>
        <a href="<?= $base_url ?>/lansia.php" class="<?= $page === 'lansia' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Data Lansia
        </a>
        <a href="<?= $base_url ?>/kunjungan.php" class="<?= $page === 'kunjungan' ? 'active' : '' ?>">
            <i class="bi bi-clipboard-plus"></i> Input Kunjungan
        </a>
        <?php endif; ?>
    </div>
    <div class="sidebar-footer">
        <a href="<?= $base_url ?>/logout.php">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </div>
</div>

<div class="main-content">
    <?php 
    $currentUser = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Admin';
    $pageIcons = [
        'dashboard' => 'bi-speedometer2',
        'lansia' => 'bi-people',
        'kunjungan' => 'bi-clipboard-plus',
        'laporan' => 'bi-file-earmark-text',
        'pengaturan' => 'bi-gear'
    ];
    $currentIcon = $pageIcons[$page] ?? 'bi-speedometer2';
    ?>
    <div class="top-navbar">
        <div class="top-navbar-left">
            <button class="menu-toggle-btn" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <div class="page-info">
                <div class="page-icon">
                    <i class="bi <?= $currentIcon ?>"></i>
                </div>
                <h2 class="page-title"><?= $pageTitle ?? 'Dashboard' ?></h2>
            </div>
        </div>
        <div class="top-navbar-right">
            <div class="dropdown-container">
                <button class="nav-icon-btn" id="notifBtn" onclick="toggleNotif(event)">
                    <i class="bi bi-bell"></i>
                    <?php if ($__unreadCount > 0): ?>
                <span class="badge-count" id="notifBadge"><?= $__unreadCount > 9 ? '9+' : $__unreadCount ?></span>
                <?php endif; ?>
                </button>
                <div class="dropdown-menu-custom" id="notifDropdown">
                    <div class="dropdown-header">
                        <span><i class="bi bi-bell me-2"></i>Notifikasi</span>
                        <span class="mark-read" onclick="markAllRead()">Tandai semua dibaca</span>
                    </div>
                    <?php if (empty($__notifications)): ?>
                    <div class="dropdown-item-custom">
                        <div class="notif-content text-center py-2">
                            <div class="notif-desc" style="color: #94a3b8;">Tidak ada notifikasi</div>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($__notifications as $__n):
                        $__iconClass = match($__n['type']) {
                            'lansia_baru', 'kunjungan_baru' => 'info',
                            'lansia_risti', 'kesehatan_memburuk' => 'warning',
                            'laporan_terkirim' => 'success',
                            default => 'info'
                        };
                        $__biIcon = match($__iconClass) {
                            'info' => 'bi-info-circle',
                            'warning' => 'bi-exclamation-triangle',
                            'success' => 'bi-check-circle'
                        };
                    ?>
                    <div class="dropdown-item-custom" style="<?= $__n['is_read'] ? 'opacity:0.75;' : '' ?>">
                        <div class="notif-icon <?= $__iconClass ?>"><i class="bi <?= $__biIcon ?>"></i></div>
                        <div class="notif-content">
                            <div class="notif-title"><?= htmlspecialchars($__n['title']) ?></div>
                            <div class="notif-desc"><?= htmlspecialchars($__n['message']) ?></div>
                            <div class="notif-time"><?= getTimeAgo($__n['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="dropdown-footer-custom">
                        <a href="#" onclick="markAllRead(); return false;">Tandai semua dibaca</a>
                    </div>
                </div>
            </div>
            <div class="dropdown-container">
                <button class="nav-icon-btn" id="settingsBtn" onclick="toggleSettings(event)">
                    <i class="bi bi-gear"></i>
                </button>
                <div class="dropdown-menu-custom" id="settingsDropdown">
                    <div class="dropdown-header">
                        <span><i class="bi bi-gear me-2"></i>Pengaturan</span>
                    </div>
                    <div class="settings-group">
                        <div class="settings-group-title">Akses Cepat</div>
                        <div class="settings-grid">
                            <div class="settings-item" onclick="window.location.href='<?= $base_url ?>/lansia.php'">
                                <i class="bi bi-people"></i> Data Lansia
                            </div>
                            <?php if (isSuperAdmin()): ?>
                            <div class="settings-item" onclick="window.location.href='<?= $base_url ?>/laporan.php'">
                                <i class="bi bi-file-earmark-text"></i> Laporan
                            </div>
                            <?php else: ?>
                            <div class="settings-item" onclick="window.location.href='<?= $base_url ?>/kunjungan.php'">
                                <i class="bi bi-clipboard-plus"></i> Input Kunjungan
                            </div>
                            <?php endif; ?>
                            <div class="settings-item" data-bs-toggle="modal" data-bs-target="#modal-bantuan" onclick="closeAllDropdowns()">
                                <i class="bi bi-question-circle"></i> Bantuan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dropdown-container">
                <div class="user-profile" id="userProfile" onclick="toggleProfile(event)">
                    <div class="user-avatar">
                        <i class="bi bi-person"></i>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= isset($_SESSION['nama_lengkap']) ? htmlspecialchars($_SESSION['nama_lengkap']) : 'Admin' ?></span>
                        <span class="user-role"><?= isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'Admin' ?></span>
                    </div>
                    <i class="bi bi-chevron-down dropdown-arrow"></i>
                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <div class="profile-dropdown-header">
                        <div class="avatar-lg"><i class="bi bi-person"></i></div>
                        <div class="name"><?= isset($_SESSION['nama_lengkap']) ? htmlspecialchars($_SESSION['nama_lengkap']) : 'Admin' ?></div>
                        <div class="email"><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'admin@pelansia.com' ?></div>
                    </div>
                    <div class="profile-dropdown-item" onclick="window.location.href='<?= $base_url ?>/dashboard.php'">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </div>
                    <div class="profile-dropdown-item" onclick="window.location.href='<?= $base_url ?>/lansia.php'">
                        <i class="bi bi-people"></i> Data Lansia
                    </div>
                    <div class="profile-dropdown-item" onclick="window.location.href='<?= $base_url ?>/pengaturan.php'">
                        <i class="bi bi-gear"></i> Pengaturan Akun
                    </div>
                    <div class="profile-dropdown-item" onclick="window.location.href='<?= $base_url ?>/logout.php'">
                        <i class="bi bi-box-arrow-left"></i> Logout
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?= $content ?>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
}

function toggleNotif(e) {
    e.stopPropagation();
    closeAllDropdowns();
    document.getElementById('notifDropdown').classList.toggle('show');
    document.getElementById('notifBtn').classList.toggle('active');
}

function toggleSettings(e) {
    e.stopPropagation();
    closeAllDropdowns();
    document.getElementById('settingsDropdown').classList.toggle('show');
    document.getElementById('settingsBtn').classList.toggle('active');
}

function toggleProfile(e) {
    e.stopPropagation();
    closeAllDropdowns();
    document.getElementById('profileDropdown').classList.toggle('show');
    document.getElementById('userProfile').classList.toggle('show');
}

function closeAllDropdowns() {
    document.querySelectorAll('.dropdown-menu-custom').forEach(el => el.classList.remove('show'));
    document.querySelectorAll('.profile-dropdown').forEach(el => el.classList.remove('show'));
    document.querySelectorAll('.nav-icon-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('userProfile').classList.remove('show');
}

function markAllRead() {
    fetch(window.location.pathname + '?notif_read_all=1')
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                var badge = document.getElementById('notifBadge');
                if (badge) badge.style.display = 'none';
                document.querySelectorAll('.dropdown-item-custom').forEach(function(el) {
                    el.style.opacity = '0.5';
                });
            }
        })
        .catch(function() {});
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown-container')) {
        closeAllDropdowns();
    }
});
</script>
<?php else: ?>
<div class="login-page">
    <?= $content ?>
</div>
<?php endif; ?>

<style>
.modal { z-index: 10050; }
.modal-backdrop { z-index: 10040; }
.modal-body { padding-top: 1.5rem; }
</style>

<!-- Modal Bantuan -->
<div class="modal fade" id="modal-bantuan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="bi bi-question-circle me-2"></i>Bantuan & Panduan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">

                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-lightning me-2"></i>Panduan Cepat</h6>
                <div class="row g-2 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="fw-medium"><i class="bi bi-people text-primary me-2"></i>Data Lansia</div>
                            <small class="text-muted">Menu untuk menambah, mengedit, dan mencari data lansia</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="fw-medium"><i class="bi bi-clipboard-plus text-success me-2"></i>Input Kunjungan</div>
                            <small class="text-muted">Mencatat kunjungan dan hasil pemeriksaan lansia</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="fw-medium"><i class="bi bi-file-earmark-text text-warning me-2"></i>Laporan</div>
                            <small class="text-muted">Melihat dan mengexport laporan (PDF/CSV/Excel)</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="fw-medium"><i class="bi bi-gear text-secondary me-2"></i>Pengaturan Akun</div>
                            <small class="text-muted">Mengganti password dan data profil akun</small>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-palette me-2"></i>Arti Warna Status</h6>
                <div class="row g-2 mb-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 p-2 bg-light rounded-3">
                            <span class="badge bg-success" style="width: 60px;">Sehat</span>
                            <small class="text-muted">Lansia dalam kondisi sehat</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 p-2 bg-light rounded-3">
                            <span class="badge bg-warning text-dark" style="width: 60px;">Ringan</span>
                            <small class="text-muted">Lansia dengan sakit ringan</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 p-2 bg-light rounded-3">
                            <span class="badge bg-danger" style="width: 60px;">Berat</span>
                            <small class="text-muted">Lansia dengan sakit berat</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2 p-2 bg-light rounded-3">
                            <span class="badge bg-danger" style="width: 60px;">Risti</span>
                            <small class="text-muted">Lansia risiko tinggi</small>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-info-circle me-2"></i>Informasi Sistem</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted ps-0" style="width: 130px;">Aplikasi</td>
                        <td class="fw-medium">PELANSIA v1.0.0</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Puskesmas</td>
                        <td class="fw-medium"><?= htmlspecialchars($__puskesmasNama) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Login sebagai</td>
                        <td class="fw-medium"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-0">Role</td>
                        <td><span class="badge bg-<?= (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') ? 'danger' : 'primary' ?>"><?= htmlspecialchars($_SESSION['role'] ?? '-') ?></span></td>
                    </tr>
                </table>

            </div>
            <div class="modal-footer border-0 justify-content-center gap-2">
                <a href="PANDUAN_PENGGUNAAN.md" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-text me-1"></i> Panduan Pengguna
                </a>
                <a href="PANDUAN_DETAIL_LANSIA.md" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-text me-1"></i> Detail Lansia
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>