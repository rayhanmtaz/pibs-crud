<?php
session_start();
include "koneksi.php";

$id = $_GET['id'];
$q = mysqli_query($koneksi, "SELECT * FROM tbl_artikel WHERE id='$id'");
$d = mysqli_fetch_array($q);

// AMBIL IDENTITAS (SAMA DG INDEX)
$q_info = mysqli_query($koneksi, "SELECT * FROM tbl_identitas LIMIT 1");
if(mysqli_num_rows($q_info) > 0){ $d_info = mysqli_fetch_array($q_info); }
else { $d_info = ['nama_website'=>'Roti Nusantara', 'slogan'=>'...', 'alamat'=>'...', 'logo'=>'logo.jpg']; }

// LOGIKA ABSENSI
if(isset($_POST['proses_absen'])){
    if(!isset($_SESSION['nip'])){
        echo "<script>alert('Silakan Login Pegawai dulu!'); window.location='login.php';</script>"; exit;
    }
    $nip = $_SESSION['nip']; $tgl = date('Y-m-d'); $waktu = date('H:i:s');
    $cek = mysqli_query($koneksi, "SELECT * FROM absensi WHERE nip='$nip' AND tanggal='$tgl'");
    
    if(mysqli_num_rows($cek) == 0){
        mysqli_query($koneksi, "INSERT INTO absensi (nip, tanggal, jam_masuk, jam_keluar, durasi_lembur) VALUES ('$nip', '$tgl', '$waktu', '00:00:00', 0)");
        echo "<script>alert('✅ ABSEN MASUK BERHASIL ($waktu)'); window.location='detail.php?id=$id';</script>";
    } else {
        $data = mysqli_fetch_array($cek);
        if($data['jam_keluar'] == '00:00:00'){
            $jam_skrg = (int)date('H');
            $lembur = ($jam_skrg > 17) ? ($jam_skrg - 17) : 0;
            mysqli_query($koneksi, "UPDATE absensi SET jam_keluar='$waktu', durasi_lembur='$lembur' WHERE nip='$nip' AND tanggal='$tgl'");
            echo "<script>alert('👋 ABSEN PULANG BERHASIL ($waktu)\\nLembur: $lembur Jam'); window.location='detail.php?id=$id';</script>";
        } else {
            echo "<script>alert('Anda sudah absen hari ini.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $d['judul']; ?> - <?php echo $d_info['nama_website']; ?></title>
    <style>
        * { box-sizing: border-box; } body { margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; background: #eee; color: #333; }
        a { text-decoration: none; color: inherit; } img { max-width: 100%; height: auto; }
        header { background: #fff; padding: 15px 30px; border-bottom: 4px solid #D84315; display: flex; align-items: center; gap: 20px; }
        header img { height: 70px; width: auto; object-fit: contain; }
        .header-info h1 { margin: 0; color: #D84315; font-size: 1.8rem; font-weight: 800; }
        .header-info .slogan { margin: 2px 0 0; font-size: 1rem; font-weight: 600; color: #333; }
        .header-info .alamat { margin: 5px 0 0; font-size: 0.85rem; color: #666; }
        .container { display: flex; flex-wrap: wrap; width: 100%; max-width: 1350px; margin: 20px auto; background: #fff; box-shadow: 0 0 15px rgba(0,0,0,0.05); min-height: 80vh; }
        .col-nav { width: 25%; background-color: #f8f9fa; padding: 25px; border-right: 1px solid #ddd; }
        .col-article { width: 50%; background-color: #ffffff; padding: 30px; }
        .col-aside { width: 25%; background-color: #f8f9fa; padding: 25px; border-left: 1px solid #ddd; }
        .sidebar-title { font-size: 0.85rem; font-weight: bold; color: #555; text-transform: uppercase; border-bottom: 2px solid #ddd; padding-bottom: 5px; display:block; margin-bottom:15px; }
        .menu-item { display: block; padding: 12px 15px; background: white; margin-bottom: 10px; border-radius: 6px; border: 1px solid #ddd; border-left: 4px solid #ddd; transition: 0.2s; color: #555; }
        .menu-item:hover { border-left-color: #D84315; color: #D84315; transform: translateX(3px); }
        .user-box { background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd; border-top: 4px solid #FF9800; margin-bottom: 30px; }
        .btn-dashboard { display: block; background: #FF9800; color: white; text-align: center; padding: 12px; margin-top: 15px; border-radius: 6px; font-weight: bold; }
        .box-absen { background: #FFF3E0; padding: 25px; border: 2px dashed #D84315; text-align: center; border-radius: 10px; margin-top: 40px; }
        .btn-absen { background: #D84315; color: white; padding: 12px 30px; font-size: 1.1rem; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 10px; }
        footer { background: #333; color: white; padding: 40px 20px; }
        .footer-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 30px; }
        .footer-left, .footer-right { flex: 1; min-width: 250px; } .footer-right { text-align: right; }
        .social-icons { display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap; }
        .social-icons a { display: flex; align-items: center; gap: 8px; color: #FFCC80; background: rgba(255,255,255,0.1); padding: 8px 12px; border-radius: 20px; font-size: 0.9rem; }
        @media screen and (max-width: 992px) { .col-nav { width: 30%; } .col-article { width: 70%; } .col-aside { width: 100%; order: 3; border-left: none; border-top: 1px solid #ddd; } }
        @media screen and (max-width: 600px) { .col-nav, .col-article, .col-aside { width: 100%; border: none; } .col-article { order: 2; border-top: 1px solid #ddd; } header, .footer-content { flex-direction: column; text-align: center; } .footer-right { text-align: center; } .social-icons { justify-content: center; } }
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
            ?>
                <a href="<?php echo $m['link']; ?>" class="menu-item">
                    <?php if($m['urutan']==1) echo '🏠'; elseif($m['urutan']==2) echo '📖'; else echo '🍞'; ?> 
                    <?php echo $m['nama_menu']; ?>
                </a>
            <?php } ?>
            
            <br>
            <span class="sidebar-title">AKSES PEGAWAI</span>
            <?php if(!isset($_SESSION['nip'])) { ?>
                <a href="login.php" class="menu-item">🔐 Login Pegawai</a>
            <?php } else { ?>
                <div style="background:white; padding:15px; border-radius:6px; margin-bottom:10px; border:1px solid #ddd;">
                    Halo, <b><?php echo $_SESSION['nama']; ?></b><br>
                    <span style="font-size:0.85rem; color:#666;">Role: <?php echo $_SESSION['role']; ?></span>
                </div>
                <a href="admin_dashboard.php" class="btn-dashboard">⚙️ Masuk Dashboard</a>
                <a href="login.php?action=logout" class="menu-item" style="margin-top:15px; background:#FFEBEE; color:#D32F2F;">🚪 Logout</a>
            <?php } ?>
        </nav>

        <article class="col-article">
            <a href="index.php" style="display:inline-block; margin-bottom:20px; color:#D84315; font-weight:bold;">← Kembali ke Beranda</a>
            
            <?php 
            $img = (!empty($d['gambar']) && file_exists("images/".$d['gambar'])) ? "images/".$d['gambar'] : "images/logo roti1.jpg";
            ?>
            <img src="<?php echo $img; ?>" style="width:100%; border-radius:8px; margin-bottom:20px;">
            
            <small style="color:#888;">Diposting pada: <?php echo $d['tanggal']; ?></small>
            <h1 style="color:#D84315; margin-top:5px;"><?php echo $d['judul']; ?></h1>
            
            <div style="line-height:1.8; font-size:1.1rem; color:#444; text-align:justify;">
                <?php echo $d['isi']; ?> 
            </div>

            <?php if(strpos($d['judul'], 'Absensi') !== false) { ?>
                <div class="box-absen">
                    <h3 style="margin-top:0;">👇 MESIN ABSENSI MANUAL 👇</h3>
                    <p>Tekan tombol di bawah untuk mencatat kehadiran.</p>
                    <form method="POST">
                        <button type="submit" name="proses_absen" class="btn-absen" onclick="return confirm('Absen Sekarang?');">
                            TAP UNTUK ABSEN ✋
                        </button>
                    </form>
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
                        <a href="<?php echo $s['link']; ?>" target="_blank">
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