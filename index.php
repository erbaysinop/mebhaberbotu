<?php
/**
 * MEB Gelişmiş Haber Manşet Botu - PHP 8.5+ Ultra Hızlı
 */

// 1. URL TEMİZLEME
$link = isset($_GET['link']) ? $_GET['link'] : (isset($_GET['okul']) ? $_GET['okul'] : 'https://sinop.meb.gov.tr');
if (!str_starts_with($link, 'http')) $link = 'https://' . $link;
$parsed = parse_url($link);
$baseUrl = $parsed['scheme'] . "://" . $parsed['host'] . "/";

// 2. VERİ ÇEKME (Google Script Linki Yerine Doğrudan Sunucu Gücü)
function fastFetch($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // Gerçek bir tarayıcı gibi davran (MEB engeline takılmamak için)
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    $res = curl_exec($ch);
    return $res;
}

$html = fastFetch($link);
$results = [];
$schoolName = "OKUL ADI";

if ($html) {
    // OKUL ADINI ÇEK
    if (preg_match("/<title>(.*?)<\/title>/si", $html, $titleMatch)) {
        $schoolName = trim(explode('-', explode('|', $titleMatch[1])[0])[0]);
    }

    // AGRESİF REGEX: src veya data-src içinde "meb_iys", "resim", "slider" geçen her şeyi yakala
    $pattern = '/<img[^>]+(?:src|data-src|data-lazy)="([^">]+(?:meb_iys|resim|slider|upload)[^">]+)"[^>]*alt="([^">]*)"/i';
    preg_match_all($pattern, $html, $matches);

    foreach ($matches[1] as $index => $imgUrl) {
        if ($index >= 12) break;
        
        $title = trim($matches[2][$index]);
        if (empty($title) || strlen($title) < 5) $title = "Güncel Haberler";

        // URL Düzenleme
        $fullImg = (str_starts_with($imgUrl, 'http')) ? $imgUrl : $baseUrl . ltrim($imgUrl, '/');
        
        // Gereksizleri Ele (logo vb.)
        if (!preg_match('/(logo|icon|banner|social|v3)/i', $fullImg)) {
            $results[] = ['img' => $fullImg, 'title' => $title];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <title><?php echo $schoolName; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <style>
        body, html { margin: 0; padding: 0; width: 100%; height: 100%; background: #000; overflow: hidden; font-family: 'Segoe UI', sans-serif; }
        .item { position: relative; height: 100vh; background: #000; }
        .item img { width: 100%; height: 100vh !important; object-fit: contain; }
        .eb-cap {
            position: absolute; bottom: 0; left: 0; right: 0;
            padding: 120px 25px 40px; 
            background: linear-gradient(to top, #000 15%, rgba(0,0,0,0.8) 50%, transparent 100%);
            color: #fff;
        }
        .badge-box { display: flex; gap: 10px; margin-bottom: 15px; }
        .s-name { background: #fff; color: #000; padding: 2px 8px; font-weight: 800; font-size: 11px; border-radius: 2px; }
        .n-tag { background: #d32f2f; color: #fff; padding: 2px 8px; font-size: 11px; font-weight: bold; border-radius: 2px; }
        .title-text { font-size: 26px; font-weight: 700; line-height: 1.2; display: block; }
        @media (max-width: 440px) {
            .item img { height: 50vh !important; object-fit: cover; }
            .eb-cap { position: relative; padding: 30px 20px; background: #000; height: 50vh; }
            .title-text { font-size: 19px; }
        }
    </style>
</head>
<body>

<div id="eb-slider" class="owl-carousel">
    <?php foreach ($results as $news): ?>
    <div class="item">
        <img src="<?php echo $news['img']; ?>">
        <div class="eb-cap">
            <div class="badge-box">
                <span class="s-name"><?php echo $schoolName; ?></span>
                <span class="n-tag">HABER</span>
            </div>
            <span class="title-text"><?php echo $news['title']; ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<script>
$(document).ready(function(){
    $('#eb-slider').owlCarousel({
        items: 1, loop: true, autoplay: true, autoplayTimeout: 5000, 
        animateOut: 'fadeOut', smartSpeed: 1000, dots: false, nav: false
    });
});
</script>
</body>
</html>