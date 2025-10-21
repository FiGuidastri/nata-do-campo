<?php
function render_header($title = 'Nata do Campo') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?> | Nata do Campo</title>
        
        <!-- CSS Base -->
        <link rel="stylesheet" href="<?php echo url('/public/assets/css/base.css'); ?>">
        <link rel="stylesheet" href="<?php echo url('/public/assets/css/layout.css'); ?>">
        <link rel="stylesheet" href="<?php echo url('/public/assets/css/forms.css'); ?>">
        <link rel="stylesheet" href="<?php echo url('/public/assets/css/style.css'); ?>">
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        
        <!-- jQuery e Plugins -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
        <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
        <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
        
        <!-- Global Variables -->
        <script>
            const BASE_URL = '<?php echo BASE_URL; ?>';
        </script>
    </head>
    <body class="main-layout">
    <?php
    // Include sidebar and top header
    include_once 'includes/sidebar.php';
    include_once 'includes/top-header.php';
    ?>
    <main class="main-content">
    <?php
}

function render_footer() {
    ?>
        </main>
        <!-- JavaScript principal e módulos -->
        <script src="<?php echo url('/public/assets/js/main.js'); ?>"></script>
        <script src="<?php echo url('/public/assets/js/forms.js'); ?>"></script>
    </body>
    </html>
    <?php
}

function url($path) {
    return BASE_URL . $path;
}