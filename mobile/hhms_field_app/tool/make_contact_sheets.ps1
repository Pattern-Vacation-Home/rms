Add-Type -AssemblyName System.Drawing

$project = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$source = Join-Path $project 'test/screens'
$target = Join-Path $project 'screenshots'
New-Item -ItemType Directory -Force -Path $target | Out-Null

function Make-Sheet($name, $files, $columns) {
    $tileWidth = 260
    $tileHeight = 590
    $rows = [Math]::Ceiling($files.Count / $columns)
    $bitmap = [System.Drawing.Bitmap]::new($columns * $tileWidth + 24, $rows * $tileHeight + 95)
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
    $graphics.Clear([System.Drawing.Color]::FromArgb(244, 247, 245))
    $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $titleFont = [System.Drawing.Font]::new('Arial', 23, [System.Drawing.FontStyle]::Bold)
    $labelFont = [System.Drawing.Font]::new('Arial', 11, [System.Drawing.FontStyle]::Bold)
    $brush = [System.Drawing.SolidBrush]::new([System.Drawing.Color]::FromArgb(20, 50, 72))
    $graphics.DrawString("HHMS Field - $name", $titleFont, $brush, 24, 22)
    for ($i = 0; $i -lt $files.Count; $i++) {
        $file = $files[$i]
        $col = $i % $columns
        $row = [Math]::Floor($i / $columns)
        $x = 18 + $col * $tileWidth
        $y = 87 + $row * $tileHeight
        $image = [System.Drawing.Image]::FromFile($file.FullName)
        try {
            $graphics.DrawImage($image, $x, $y, 244, 528)
            $label = [System.IO.Path]::GetFileNameWithoutExtension($file.Name) -replace '^\d+-', '' -replace '-', ' '
            $graphics.DrawString($label, $labelFont, $brush, $x, $y + 535)
        } finally {
            $image.Dispose()
        }
    }
    $output = Join-Path $target "$name-contact-sheet.png"
    try { $bitmap.Save($output, [System.Drawing.Imaging.ImageFormat]::Png) }
    finally { $graphics.Dispose(); $bitmap.Dispose(); $titleFont.Dispose(); $labelFont.Dispose(); $brush.Dispose() }
    Write-Output $output
}

$files = @(Get-ChildItem -LiteralPath $source -Filter '*.png' | Sort-Object Name)
Make-Sheet 'operations' @($files | Select-Object -First 6) 3
Make-Sheet 'maintainer' @($files | Select-Object -Skip 6) 4
