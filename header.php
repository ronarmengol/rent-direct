<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($page_title)) {
    $page_title = 'Housing App - Find a Home';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- PWA Manifest & Meta -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#3b82f6">
    <link rel="apple-touch-icon" href="app-icon.png">

    <link rel="stylesheet" href="style.css?v=1.2">
    
    <?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body class="<?php echo isset($body_class) ? $body_class : ''; ?>">

    <!-- MESSAGE LOGIC -->
    <?php if(isset($_SESSION['msg'])): ?>
        <div class="notification-toast <?php echo $_SESSION['msg_type']; ?>" id="notification" style="display:none;">
            <?php echo $_SESSION['msg']; unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
        </div>
        <script>
            setTimeout(function() {
                var box = document.getElementById('notification');
                if(box) {
                    box.style.display = 'block'; 
                    setTimeout(function() { 
                        box.style.opacity = '0'; 
                        setTimeout(function(){ box.remove(); }, 1000); 
                    }, 5000);
                }
            }, 500); 
        </script>
    <?php endif; ?>

    <!-- Navbar -->
    <?php if(!isset($hide_navbar) || !$hide_navbar): ?>
    <nav class="navbar">
        <div class="brand">
            <a href="index.php">
                <img src="house-logo.png" alt="Logo">
                Rent Direct
            </a>
        </div>
        <div style="display: flex; gap: 16px; align-items: center;">
            <?php if(basename($_SERVER['PHP_SELF']) != 'index.php'): ?>
                <a href="index.php">Home</a>
            <?php endif; ?>
            
            <button id="install-app-btn" class="nav-btn" style="display: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <span>📲</span> Install App
            </button>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Global Scripts (PWA & Service Worker) -->
    <script>
        // Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('service-worker.js')
                    .then(registration => console.log('SW Registered'))
                    .catch(err => console.log('SW Failed', err));
            });
        }
        
        // PWA Install Logic
        let deferredPrompt;
        const installBtn = document.getElementById('install-app-btn');
        
        if(installBtn) {
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                installBtn.style.display = 'flex';
            });

            installBtn.addEventListener('click', (e) => {
                installBtn.style.display = 'none';
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    deferredPrompt = null;
                });
            });

            window.addEventListener('appinstalled', () => {
                installBtn.style.display = 'none';
            });
        }
    </script>
