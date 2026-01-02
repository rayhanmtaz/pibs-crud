<?php 
session_start();
include "koneksi.php";

// --- 1. LOGIKA IDENTITAS WEBSITE ---
$q_info = mysqli_query($koneksi, "SELECT * FROM tbl_identitas LIMIT 1");
if(mysqli_num_rows($q_info) > 0){
    $d_info = mysqli_fetch_array($q_info);
} else {
    $d_info = ['nama_website'=>'Roti Nusantara', 'slogan'=>'Kelembutan Tradisi', 'alamat'=>'Alamat Default', 'logo'=>'logo roti1.jpg'];
}

// --- 2. LOGIKA PROFIL MAHASISWA ---
// Fungsi ambil data berdasarkan NIM
function get_data_by_nim($koneksi, $table, $nim) {
    $nim = $koneksi->real_escape_string($nim);
    $filter_column = ($table == 'mahasiswa') ? 'nim' : 'nim_mhs'; 
    $sql = "SELECT * FROM {$table} WHERE {$filter_column} = '{$nim}' ORDER BY id ASC";
    $result = $koneksi->query($sql);
    if ($table == 'mahasiswa') {
        return $result->fetch_assoc();
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Ambil list mahasiswa untuk dropdown
$mahasiswa_list = $koneksi->query("SELECT nim, nama FROM mahasiswa ORDER BY nama ASC")->fetch_all(MYSQLI_ASSOC);
$nim_profil = isset($_GET['nim']) ? $koneksi->real_escape_string($_GET['nim']) : ($mahasiswa_list[0]['nim'] ?? ''); 

// Tentukan halaman aktif
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$kat_id = isset($_GET['kategori']) ? $_GET['kategori'] : '';

// Ambil data spesifik mahasiswa jika halaman profil dibuka
$data_mhs = null;
if (in_array($page, ['biodata', 'pendidikan', 'pengalaman', 'keahlian', 'publikasi'])) {
    $table_name = ($page == 'biodata') ? 'mahasiswa' : $page; 
    $data_mhs = get_data_by_nim($koneksi, $table_name, $nim_profil);
}

$data_profil_header = get_data_by_nim($koneksi, 'mahasiswa', $nim_profil);
$hobi_list = get_data_by_nim($koneksi, 'hobi', $nim_profil);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $d_info['nama_website']; ?> - Profil Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Gabungan Style Roti Nusantara */
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; background: #eee; color: #333; }
        header { background: #fff; padding: 15px 30px; border-bottom: 4px solid #D84315; display: flex; align-items: center; gap: 20px; }
        header img { height: 70px; }
        .container { display: flex; flex-wrap: wrap; max-width: 1350px; margin: 20px auto; background: #fff; box-shadow: 0 0 15px rgba(0,0,0,0.05); }
        .col-nav { width: 25%; background: #f8f9fa; padding: 25px; border-right: 1px solid #ddd; }
        .col-article { width: 50%; padding: 30px; background: #fff; }
        .col-aside { width: 25%; background: #f8f9fa; padding: 25px; border-left: 1px solid #ddd; }
        .sidebar-title { font-weight: bold; color: #555; display: block; border-bottom: 2px solid #ddd; margin-bottom: 15px; }
        .menu-item { display: block; padding: 12px; background: white; margin-bottom: 10px; border-left: 4px solid #ddd; text-decoration: none; color: #555; }
        .menu-item.active { background: #D84315; color: white; border-color: #D84315; }
        .card { border: 1px solid #eee; margin-bottom: 20px; border-radius: 8px; overflow: hidden; }
        .skill-bar-container { background: #ddd; border-radius: 5px; height: 10px; margin-top: 5px; }
        .skill-bar { background: #D84315; height: 100%; border-radius: 5px; }
    </style>
</head>
<body>

    <header>
        <img src="images/<?php echo $d_info['logo']; ?>" alt="Logo">
        <div class="header-info">
            <h1 style="margin:0; color:#D84315;"><?php echo $d_info['nama_website']; ?></h1>
            <p style="margin:0; font-weight:600;"><?php echo $d_info['slogan']; ?></p>
        </div>
        <form action="index.php" method="GET" style="margin-left:auto; display:flex; align-items:center;">
            <input type="hidden" name="page" value="<?php echo htmlspecialchars($page); ?>">
            <label style="margin-right:10px; font-weight:bold;">Lihat Profil:</label>
            <select name="nim" onchange="this.form.submit()">
                <?php foreach ($mahasiswa_list as $mhs): ?>
                    <option value="<?php echo $mhs['nim']; ?>" <?php echo ($mhs['nim'] == $nim_profil) ? 'selected' : ''; ?>>
                        <?php echo $mhs['nama']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </header>

    <div class="container">
        <nav class="col-nav">
            <span class="sidebar-title">MENU UTAMA</span>
            <?php 
            $q_menu = mysqli_query($koneksi, "SELECT * FROM tbl_menu ORDER BY urutan ASC");
            while($m = mysqli_fetch_array($q_menu)){
                $isActive = ($page == 'home' && strpos($m['link'], 'index.php') !== false) || (strpos($m['link'], "page=$page") !== false) ? 'active' : '';
            ?>
                <a href="<?php echo $m['link']; ?>&nim=<?php echo $nim_profil; ?>" class="menu-item <?php echo $isActive; ?>">
                    <?php echo $m['nama_menu']; ?>
                </a>
            <?php } ?>

            <br><span class="sidebar-title">NAVIGASI PROFIL</span>
            <a href="?nim=<?php echo $nim_profil; ?>&page=biodata" class="menu-item <?php echo ($page == 'biodata') ? 'active' : ''; ?>">👤 Biodata</a>
            <a href="?nim=<?php echo $nim_profil; ?>&page=pendidikan" class="menu-item <?php echo ($page == 'pendidikan') ? 'active' : ''; ?>">🎓 Pendidikan</a>
            <a href="?nim=<?php echo $nim_profil; ?>&page=keahlian" class="menu-item <?php echo ($page == 'keahlian') ? 'active' : ''; ?>">⚡ Keahlian</a>
        </nav>

        <article class="col-article">
            <?php if($page == 'home') { ?>
                <h2 style="border-bottom:2px solid #D84315; color:#D84315;">Berita & Info</h2>
                <?php
                $q_art = "SELECT * FROM tbl_artikel " . ($kat_id ? "WHERE id_kategori='$kat_id'" : "") . " ORDER BY id ASC";
                $sql_art = mysqli_query($koneksi, $q_art);
                while($art = mysqli_fetch_array($sql_art)){
                    $img_art = (!empty($art['gambar']) && file_exists("images/".$art['gambar'])) ? "images/".$art['gambar'] : "images/logo roti1.jpg";
                ?>
                    <div class="card">
                        <img src="<?php echo $img_art; ?>" style="width:100%; height:200px; object-fit:cover;">
                        <div style="padding:15px;">
                            <small>📅 <?php echo $art['tanggal']; ?></small>
                            <h3><?php echo $art['judul']; ?></h3>
                            <p><?php echo substr($art['isi'], 0, 100); ?>...</p>
                            <a href="detail.php?id=<?php echo $art['id']; ?>" style="color:#D84315; font-weight:bold;">Baca Selengkapnya →</a>
                        </div>
                    </div>
                <?php } ?>

            <?php } elseif(in_array($page, ['biodata', 'pendidikan', 'keahlian'])) { ?>
                <h2 style="border-bottom:2px solid #D84315; color:#D84315;"><?php echo ucfirst($page); ?>: <?php echo $data_profil_header['nama'] ?? ''; ?></h2>
                
                <?php if($page == 'biodata' && $data_mhs): ?>
                    <p><strong>NIM:</strong> <?php echo $data_mhs['nim']; ?></p>
                    <p><strong>Agama:</strong> <?php echo $data_mhs['agama'] ?? '-'; ?></p>
                    <p><strong>Tempat Lahir:</strong> <?php echo $data_mhs['tempat_lahir'] ?? '-'; ?></p>
                
                <?php elseif($page == 'keahlian' && $data_mhs): ?>
                    <?php foreach($data_mhs as $skill): ?>
                        <div style="margin-bottom:15px;">
                            <span><?php echo $skill['skill_name']; ?> (<?php echo $skill['level']; ?>%)</span>
                            <div class="skill-bar-container"><div class="skill-bar" style="width:<?php echo $skill['level']; ?>%"></div></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php } ?>
        </article>

        <aside class="col-aside">
            <span class="sidebar-title">HOBI</span>
            <ul>
                <?php if($hobi_list): foreach($hobi_list as $h): ?>
                    <li><?php echo htmlspecialchars($h['nama_hobi']); ?></li>
                <?php endforeach; else: echo "<li>Belum ada data</li>"; endif; ?>
            </ul>
            <br>
            <?php if(isset($_SESSION['nip'])) { ?>
                <span class="sidebar-title">PEGAWAI AKTIF</span>
                <div style="background:white; padding:10px; border:1px solid #ddd;">
                    <b><?php echo $_SESSION['nama']; ?></b><br>
                    <a href="admin_dashboard.php" style="color:#D84315; font-size:0.8rem;">⚙️ Ke Dashboard</a>
                </div>
            <?php } ?>
        </aside>
    </div>

    <footer style="background:#333; color:#fff; padding:20px; text-align:center;">
        &copy; 2025 <?php echo $d_info['nama_website']; ?> | <?php echo $d_info['alamat']; ?>
    </footer>

</body>
</html>