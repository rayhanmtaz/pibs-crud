<?php 
session_start();
include "koneksi.php";

$q_info = mysqli_query($koneksi, "SELECT * FROM tbl_identitas LIMIT 1");
if(mysqli_num_rows($q_info) > 0){
    $d_info = mysqli_fetch_array($q_info);
} else {
    $d_info = ['nama_website'=>'Roti Nusantara', 'slogan'=>'Kelembutan Tradisi', 'alamat'=>'Alamat Default', 'logo'=>'logo roti1.jpg'];
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$kat_id = isset($_GET['kategori']) ? $_GET['kategori'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $d_info['nama_website']; ?></title>
    <style>
        
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; background: #eee; color: #333; }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; height: auto; }
        
        header { background: #fff; padding: 15px 30px; border-bottom: 4px solid #D84315; display: flex; align-items: center; gap: 20px; }
        header img { height: 70px; width: auto; object-fit: contain; }
        .header-info h1 { margin: 0; color: #D84315; font-size: 1.8rem; line-height: 1.2; font-weight: 800; }
        .header-info .slogan { margin: 2px 0 0; font-size: 1rem; font-weight: 600; color: #333; }
        .header-info .alamat { margin: 5px 0 0; font-size: 0.85rem; color: #666; }
    
        .container { display: flex; flex-wrap: wrap; width: 100%; max-width: 1350px; margin: 20px auto; background: #fff; box-shadow: 0 0 15px rgba(0,0,0,0.05); min-height: 80vh; }

        .col-nav { width: 25%; background-color: #f8f9fa; padding: 25px; border-right: 1px solid #ddd; }
        .col-article { width: 50%; background-color: #ffffff; padding: 30px; }
        .col-aside { width: 25%; background-color: #f8f9fa; padding: 25px; border-left: 1px solid #ddd; }

        /* SIDEBAR ELEMENTS */
        .sidebar-title { font-size: 0.85rem; font-weight: bold; color: #555; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; display: block; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
        .menu-item { display: block; padding: 12px 15px; background: white; margin-bottom: 10px; border-radius: 6px; border: 1px solid #ddd; border-left: 4px solid #ddd; transition: 0.2s; color: #555; }
        .menu-item:hover { border-left-color: #D84315; color: #D84315; transform: translateX(3px); }
        .menu-item.active { background: #D84315; color: white; border-color: #D84315; }
        
        .user-box { background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd; border-top: 4px solid #FF9800; margin-bottom: 30px; }
        .card { border: 1px solid #eee; border-radius: 8px; overflow: hidden; margin-bottom: 25px; transition: 0.3s; background: #fff; }
        .card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.08); transform: translateY(-2px); }
        .card-body { padding: 20px; }
        .btn-dashboard { display: block; background: #FF9800; color: white; text-align: center; padding: 12px; margin-top: 15px; border-radius: 6px; font-weight: bold; }

        /* FOOTER */
        footer { background: #333; color: white; padding: 40px 20px; }
        .footer-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 30px; }
        .footer-left, .footer-right { flex: 1; min-width: 250px; }
        .footer-right { text-align: right; }
        .social-icons { display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap; }
        .social-icons a { display: flex; align-items: center; gap: 8px; color: #FFCC80; transition: 0.3s; text-decoration: none; background: rgba(255,255,255,0.1); padding: 8px 12px; border-radius: 20px; font-size: 0.9rem; }
        .social-icons a:hover { background: #FFCC80; color: #333; }

        /* RESPONSIVE */
        @media screen and (max-width: 992px) { 
            .col-nav { width: 30%; } .col-article { width: 70%; } .col-aside { width: 100%; order: 3; border-left: none; border-top: 1px solid #ddd; }
        }
        @media screen and (max-width: 600px) { 
            .container { margin: 0; box-shadow: none; } .col-nav, .col-article, .col-aside { width: 100%; border: none; } .col-article { order: 2; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; }
            header { flex-direction: column; text-align: center; gap: 10px; padding: 20px 15px; } .header-info .alamat { justify-content: center; }
            footer { padding: 30px 15px; } .footer-content { flex-direction: column; text-align: center; gap: 40px; } .footer-left { border-bottom: 1px solid #444; padding-bottom: 30px; } .social-icons { justify-content: center; } .footer-right { text-align: center; }
        }
    </style>
</head>
<body>

    <header>
        <img src="images/<?php echo $d_info['logo']; ?>" alt="Logo">
        <div class="header-info">
            <h1><?php echo $d_info['nama_website']; ?></h1>
            <p class="slogan"><?php echo $d_info['slogan']; ?></p>
            <p class="alamat">📍 <?php echo $d_info['alamat']; ?></p>
        </div>
    </header>

    <div class="container">
        
        <nav class="col-nav">
            <span class="sidebar-title">MENU UTAMA</span>
            <?php 
                $q_menu = mysqli_query($koneksi, "SELECT * FROM tbl_menu ORDER BY urutan ASC");
                while($m = mysqli_fetch_array($q_menu)){
                    $isActive = ($page == 'home' && $m['link'] == 'index.php') || ($page != 'home' && strpos($m['link'], "page=$page") !== false) ? 'active' : '';
            ?>
                <a href="<?php echo $m['link']; ?>" class="menu-item <?php echo $isActive; ?>">
                    <?php if($m['urutan']==1) echo '🏠'; elseif($m['urutan']==2) echo '📖'; else echo '🍞'; ?> 
                    <?php echo $m['nama_menu']; ?>
                </a>
            <?php } ?>
            
            <br>
            <span class="sidebar-title">AKSES PEGAWAI</span>
            <?php if(!isset($_SESSION['nip'])) { ?>
                <a href="login.php" class="menu-item">🔐 Login Pegawai</a>
            <?php } else { ?>
                <div style="background:white; padding:15px; border-radius:6px; margin-bottom:10px; border:1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    Halo, <b><?php echo $_SESSION['nama']; ?></b><br>
                    <span style="font-size:0.85rem; color:#666;">Role: <?php echo $_SESSION['role']; ?></span>
                </div>
                <a href="admin_dashboard.php" class="btn-dashboard">⚙️ Masuk Dashboard</a>
                <a href="login.php?action=logout" class="menu-item" style="margin-top:15px; background:#FFEBEE; color:#D32F2F; border-left-color:#D32F2F;">🚪 Logout</a>
            <?php } ?>
        </nav>

        <article class="col-article">
            <?php if($page == 'home') { ?>
                <h2 style="margin-top:0; border-bottom:2px solid #D84315; padding-bottom:10px; color:#D84315;">Berita & Info</h2>
                <?php
                $q_art = "SELECT * FROM tbl_artikel " . ($kat_id ? "WHERE id_kategori='$kat_id'" : "") . " ORDER BY id ASC";
                $sql = mysqli_query($koneksi, $q_art);
                if(mysqli_num_rows($sql) > 0){
                    while($a = mysqli_fetch_array($sql)){
                        $img = (!empty($a['gambar']) && file_exists("images/".$a['gambar'])) ? "images/".$a['gambar'] : "images/logo roti1.jpg";
                ?>
                    <div class="card">
                        <img src="<?php echo $img; ?>" style="width:100%; height:220px; object-fit:cover;">
                        <div class="card-body">
                            <span style="background:#e3f2fd; color:#1565c0; padding:4px 8px; border-radius:4px; font-size:0.8rem; font-weight:bold;">📅 <?php echo $a['tanggal']; ?></span>
                            <h3 style="margin:10px 0; color:#333; font-size:1.3rem;"><?php echo $a['judul']; ?></h3>
                            <p style="color:#666; line-height:1.6;"><?php echo substr($a['isi'], 0, 110); ?>...</p>
                            <a href="detail.php?id=<?php echo $a['id']; ?>" style="display:inline-block; margin-top:10px; background:#D84315; color:white; padding:10px 20px; border-radius:30px; font-weight:bold; font-size:0.9rem;">Baca Selengkapnya →</a>
                        </div>
                    </div>
                <?php } } else { echo "<p>Tidak ada berita.</p>"; } ?>

            <?php } elseif($page == 'produk') { ?>
                <h2 style="margin-top:0; border-bottom:2px solid #D84315; padding-bottom:10px; color:#D84315;">Katalog Produk</h2>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:20px;">
                    <?php 
                    $sql = mysqli_query($koneksi, "SELECT * FROM tbl_produk");
                    while($p = mysqli_fetch_array($sql)){
                        $img = (!empty($p['gambar']) && file_exists("images/".$p['gambar'])) ? "images/".$p['gambar'] : "images/logo roti1.jpg";
                    ?>
                    <div class="card" style="text-align:center;">
                        <img src="<?php echo $img; ?>" style="height:150px; object-fit:contain; margin-top:15px;">
                        <div class="card-body">
                            <h4 style="margin:0; color:#D84315;"><?php echo $p['nama_produk']; ?></h4>
                            <b style="font-size:1.1rem; display:block; margin:5px 0;">Rp <?php echo number_format($p['harga']); ?></b>
                        </div>
                    </div>
                    <?php } ?>
                </div>

            <?php } elseif($page == 'tentang') { 
                // AMBIL DATA DARI DATABASE TBL_HALAMAN
                $q_hal = mysqli_query($koneksi, "SELECT * FROM tbl_halaman WHERE judul_halaman='Tentang Kami' LIMIT 1");
                $hal = mysqli_fetch_array($q_hal);
                
                // Cek ada gambar atau tidak
                $img_hal = (!empty($hal['gambar'])) ? "images/".$hal['gambar'] : "images/logo roti1.jpg";
            ?>
                
                <h2 style="border-bottom:2px solid #D84315; padding-bottom:10px; color:#D84315;">
                    <?php echo $hal['judul_halaman']; ?>
                </h2>
                
                <div style="background:white; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05); line-height:1.8;">
                    <img src="<?php echo $img_hal; ?>" style="float:left; width:150px; margin-right:20px; border-radius:10px; margin-bottom:10px;">
                    
                    <?php echo $hal['isi_halaman']; ?>
                    
                </div>

            <?php } ?>
        </article>

        <aside class="col-aside">
            <?php if(isset($_SESSION['nip'])) { ?>
                <span class="sidebar-title">👤 Profil Saya</span>
                <div class="user-box">
                    <div style="margin-bottom:8px;"><strong>Nama:</strong><br><?php echo $_SESSION['nama']; ?></div>
                    <div style="margin-bottom:8px;"><strong>NIP:</strong><br><?php echo $_SESSION['nip']; ?></div>
                    <div><strong>Role:</strong><br><span style="background:#E0F2F1; color:#00695C; padding:2px 8px; border-radius:4px; font-size:0.85rem;"><?php echo $_SESSION['role']; ?></span></div>
                </div>
            <?php } ?>

            <span class="sidebar-title">KATEGORI INFO</span>
            <ul style="list-style:none; padding:0;">
                <?php 
                    $q_kat = mysqli_query($koneksi, "SELECT * FROM tbl_kategori_artikel");
                    while($k = mysqli_fetch_array($q_kat)){
                ?>
                <li>
                    <a href="index.php?kategori=<?php echo $k['id']; ?>" class="menu-item" style="border-left-color:#78909C;">
                        📂 <?php echo $k['nama_kategori']; ?>
                    </a>
                </li>
                <?php } ?>
            </ul>
        </aside>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-left">
                <h4 style="color:#FFCC80; margin-top:0;">IKUTI KAMI</h4>
                <div class="social-icons">
                    <?php 
                        $q_sos = mysqli_query($koneksi, "SELECT * FROM tbl_sosmed");
                        while($s = mysqli_fetch_array($q_sos)){
                    ?>
                        <a href="<?php echo $s['link']; ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo $s['icon']; ?> <?php echo $s['nama_sosmed']; ?>
                        </a>
                    <?php } ?>
                </div>
                <p style="font-size:0.85rem; color:#ccc; margin-top:15px;">Dapatkan update terbaru seputar promo dan menu baru.</p>
            </div>
            
            <div class="footer-right">
                <h4 style="color:#FFCC80; margin-top:0; text-transform:uppercase;"><?php echo $d_info['nama_website']; ?></h4>
                <p style="font-style:italic; margin:5px 0;">"<?php echo $d_info['slogan']; ?>"</p>
                <p style="font-size:0.85rem; color:#ccc; line-height:1.6;"><?php echo $d_info['alamat']; ?></p>
            </div>
        </div>
        <div style="text-align:center; margin-top:30px; font-size:0.8rem; color:#777; border-top:1px solid #444; padding-top:15px;">
            &copy; 2025 UAS Pengolahan Informasi Berbasis Script.
        </div>
    </footer>

</body>
</html>