<?php 
session_start();
include "koneksi.php";

// --- 1. AMBIL IDENTITAS WEBSITE ---
$q_info = mysqli_query($koneksi, "SELECT * FROM tbl_identitas LIMIT 1");
$d_info = (mysqli_num_rows($q_info) > 0) ? mysqli_fetch_array($q_info) : ['nama_website'=>'Roti Nusantara', 'slogan'=>'Kelembutan Tradisi', 'alamat'=>'Alamat Default', 'logo'=>'logo roti1.jpg'];

// --- 2. LOGIKA DATA KARYAWAN
// Ambil daftar karyawan dari tabel pegawai/karyawan (Sesuaikan nama tabelnya, misal: tbl_pegawai)
// Jika kamu belum punya tabel tbl_pegawai, sistem akan tetap eror di sini.
$karyawan_list = $koneksi->query("SELECT nip, nama FROM karyawan ORDER BY nama ASC")->fetch_all(MYSQLI_ASSOC);
$karyawan_list = ($karyawan_list_query) ? mysqli_fetch_all($karyawan_list_query, MYSQLI_ASSOC) : [];

// Ambil NIP yang dipilih dari dropdown atau dari session login
$nip_profil = isset($_GET['nip']) ? mysqli_real_escape_string($koneksi, $_GET['nip']) : ($_SESSION['nip'] ?? ($karyawan_list[0]['nip'] ?? '')); 

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $d_info['nama_website']; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Style tetap dipertahankan seperti desain Roti Nusantara kamu */
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #eee; }
        header { background: #fff; padding: 15px 30px; border-bottom: 4px solid #D84315; display: flex; align-items: center; }
        .container { display: flex; flex-wrap: wrap; max-width: 1350px; margin: 20px auto; background: #fff; }
        .col-nav, .col-aside { width: 25%; background: #f8f9fa; padding: 20px; border: 1px solid #ddd; }
        .col-article { width: 50%; padding: 30px; }
        .menu-item { display: block; padding: 12px; margin-bottom: 5px; background: #fff; border: 1px solid #ddd; text-decoration: none; color: #333; }
        .menu-item.active { background: #D84315; color: #fff; }
    </style>
</head>
<body>

    <header>
        <img src="images/<?php echo $d_info['logo']; ?>" style="height:70px; margin-right:20px;">
        <div class="header-info">
            <h1 style="color:#D84315; margin:0;"><?php echo $d_info['nama_website']; ?></h1>
            <p style="margin:0; font-weight:600;"><?php echo $d_info['slogan']; ?></p>
        </div>
        
        <form action="" method="GET" style="margin-left:auto">
            <label>Pilih Karyawan:</label>
            <select name="nip" onchange="this.form.submit()">
                <?php foreach($karyawan_list as $k): ?>
                    <option value="<?php echo $k['nip']; ?>" <?php echo ($k['nip'] == $nip_profil ? 'selected' : ''); ?>>
                        <?php echo $k['nama']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </header>

    <div class="container">
        <nav class="col-nav">
            <span style="font-weight:bold; color:#555;">MENU UTAMA</span>
            <a href="?page=home&nip=<?php echo $nip_profil; ?>" class="menu-item <?php echo ($page=='home'?'active':''); ?>">🏠 Home / Berita</a>
            <a href="?page=gaji&nip=<?php echo $nip_profil; ?>" class="menu-item <?php echo ($page=='gaji'?'active':''); ?>">💰 Riwayat Gaji</a>
            
            <br>
            <?php if(isset($_SESSION['nip'])) { ?>
                <div style="background:#fff; padding:10px; border:1px solid #ddd; border-top:4px solid #D84315;">
                    Sesi: <b><?php echo $_SESSION['nama']; ?></b><br>
                    <a href="admin_dashboard.php" style="color:#D84315; font-size:0.85rem;">Ke Dashboard →</a>
                </div>
            <?php } ?>
        </nav>

        <article class="col-article">
            <?php if($page == 'home'): ?>
                <h2 style="color:#D84315;">Berita & Info</h2>
                <?php
                $q_art = mysqli_query($koneksi, "SELECT * FROM tbl_artikel ORDER BY id ASC");
                while($a = mysqli_fetch_array($q_art)){
                ?>
                    <div style="border:1px solid #eee; margin-bottom:20px; padding:15px; border-radius:8px;">
                        <h3><?php echo $a['judul']; ?></h3>
                        <p><?php echo substr($a['isi'], 0, 150); ?>...</p>
                        <a href="detail.php?id=<?php echo $a['id']; ?>" style="color:#D84315;">Baca Selengkapnya</a>
                    </div>
                <?php } ?>

            <?php elseif($page == 'gaji'): ?>
                <h2 style="color:#D84315;">Riwayat Gaji NIP: <?php echo $nip_profil; ?></h2>
                <table border="1" cellpadding="10" style="width:100%; border-collapse:collapse;">
                    <tr style="background:#f8f9fa;">
                        <th>Periode</th>
                        <th>Total Gaji Netto</th>
                        <th>Status</th>
                    </tr>
                    <?php
                    $q_gaji = mysqli_query($koneksi, "SELECT * FROM slip_gaji WHERE nip='$nip_profil' ORDER BY id_slip DESC");
                    while($g = mysqli_fetch_array($q_gaji)){
                    ?>
                    <tr>
                        <td><?php echo $g['periode']; ?></td>
                        <td>Rp <?php echo number_format($g['total_gaji_netto']); ?></td>
                        <td><b style="color:green;"><?php echo $g['status']; ?></b></td>
                    </tr>
                    <?php } ?>
                </table>
            <?php endif; ?>
        </article>

        <aside class="col-aside">
            <span style="font-weight:bold; color:#555;">INFO PERUSAHAAN</span>
            <p style="font-size:0.9rem; color:#666;"><?php echo $d_info['alamat']; ?></p>
        </aside>
    </div>

    <footer style="background:#333; color:#fff; padding:20px; text-align:center;">
        &copy; 2025 <?php echo $d_info['nama_website']; ?> | UAS Kelompok 7
    </footer>
</body>
</html>