$files = @(
    "c:\xampp\htdocs\nokia1100\admin\ventas\ventas.php",
    "c:\xampp\htdocs\nokia1100\admin\ventas\nueva_venta.php",
    "c:\xampp\htdocs\nokia1100\admin\ventas\graficas_ventas.php",
    "c:\xampp\htdocs\nokia1100\admin\ventas\exportar_ventas.php",
    "c:\xampp\htdocs\nokia1100\admin\compras\proveedores.php",
    "c:\xampp\htdocs\nokia1100\admin\compras\nueva_compra.php",
    "c:\xampp\htdocs\nokia1100\admin\compras\historial_compras.php",
    "c:\xampp\htdocs\nokia1100\api\crear_producto_ajax.php",
    "c:\xampp\htdocs\nokia1100\api\cambiar_estado.php",
    "c:\xampp\htdocs\nokia1100\api\get_token.php",
    "c:\xampp\htdocs\nokia1100\api\update_images.php",
    "c:\xampp\htdocs\nokia1100\api\obtener_historial_compras.php",
    "c:\xampp\htdocs\nokia1100\auth\verificar.php",
    "c:\xampp\htdocs\nokia1100\auth\procesar_registro.php"
)

foreach ($file in $files) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw -Encoding UTF8
        
        # Determine the depth level for path adjustments
        if ($file -like "*\admin\ventas\*" -or $file -like "*\admin\compras\*") {
            $pathPrefix = "../../"
        } elseif ($file -like "*\api\*" -or $file -like "*\auth\*") {
            $pathPrefix = "../"
        }
        
        # Replace database includes
        $content = $content -replace "require_once ['\`"]bd\.php['\`"];", "require_once '${pathPrefix}config/bd.php';"
        $content = $content -replace "require_once ['\`"]config/bd\.php['\`"];", "require_once '${pathPrefix}config/bd.php';"
        
        # Replace index.php redirects
        $content = $content -replace 'header\("Location: index\.php"\);', "header(`"Location: ${pathPrefix}index.php`");"
        
        # Replace style.css links  
        $content = $content -replace 'href="style\.css"', "href=`"${pathPrefix}assets/css/style.css`""
        
        # Save the file
        Set-Content -Path $file -Value $content -Encoding UTF8 -NoNewline
        Write-Host "Updated: $file"
    } else {
        Write-Host "File not found: $file" -ForegroundColor Yellow
    }
}

Write-Host "`nAll files updated successfully!" -ForegroundColor Green
