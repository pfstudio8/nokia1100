$files = @(
    "c:\xampp\htdocs\nokia1100\admin\ventas\ventas.php",
    "c:\xampp\htdocs\nokia1100\admin\ventas\nueva_venta.php",
    "c:\xampp\htdocs\nokia1100\admin\ventas\graficas_ventas.php",
    "c:\xampp\htdocs\nokia1100\admin\compras\proveedores.php",
    "c:\xampp\htdocs\nokia1100\admin\compras\nueva_compra.php",
    "c:\xampp\htdocs\nokia1100\admin\compras\historial_compras.php"
)

foreach ($file in $files) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw -Encoding UTF8
        
        # Replace panel links for deeper directories
        $content = $content -replace 'href="panel_admin\.php"', 'href="../panel_admin.php"'
        $content = $content -replace 'href="panel_empleado\.php"', 'href="../panel_empleado.php"'
        $content = $content -replace "href='panel_admin\.php'", "href='../panel_admin.php'"
        $content = $content -replace "href='panel_empleado\.php'", "href='../panel_empleado.php'"
        
        # Save the file
        Set-Content -Path $file -Value $content -Encoding UTF8 -NoNewline
        Write-Host "Updated navigation in: $file"
    }
    else {
        Write-Host "File not found: $file" -ForegroundColor Yellow
    }
}

Write-Host "`nNavigation links updated!" -ForegroundColor Green
