<?php
// Script to inject "Registro Diário" into navigations

function processFiles($dir, $dashboardTag = 'Dashboard') {
    $files = glob($dir . '/*.html');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        
        // Admin
        if (strpos($content, 'Dashboard') !== false) {
            $content = preg_replace(
                '/(<a href="index\.html".*?><span class="nav-icon">📊<\/span> Dashboard<\/a>)/i',
                "$1\n        <a href=\"registro_diario.html\" class=\"nav-item\"><span class=\"nav-icon\">📋</span> Registro Diário</a>",
                $content
            );
        }
        
        // Motorista
        if (strpos($content, 'Início') !== false && strpos($content, '🏠') !== false) {
            $content = preg_replace(
                '/(<a href="index\.html".*?><span class="nav-icon">🏠<\/span> Início<\/a>)/i',
                "$1\n        <a href=\"registro_diario.html\" class=\"nav-item\"><span class=\"nav-icon\">📋</span> Registro Diário</a>",
                $content
            );
        }

        // Replace the Dashboard action card alert
        $content = str_replace(
            'onclick="alert(\'Em breve: Registro de Viagem\')"',
            'onclick="location.href=\'registro_diario.html\'"',
            $content
        );

        file_put_contents($file, $content);
        echo "Updated: $file\n";
    }
}

processFiles('C:/xampp/htdocs/controle-km/frontend/admin');
processFiles('C:/xampp/htdocs/controle-km/frontend/motorista');

echo "Done.";
?>
