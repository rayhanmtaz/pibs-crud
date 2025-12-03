<?php
include 'koneksi.php';

// Folder untuk file upload
$target_dir = "uploads/";

// Fungsi untuk mengambil semua data dari satu tabel berdasarkan NIM
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

// --- 1. TENTUKAN PROFIL MAHASISWA YANG DIPILIH (Filtering) ---
$mahasiswa_list = $koneksi->query("SELECT nim, nama FROM mahasiswa ORDER BY nama ASC")->fetch_all(MYSQLI_ASSOC);
$nim_profil = isset($_GET['nim']) ? $koneksi->real_escape_string($_GET['nim']) : ($mahasiswa_list[0]['nim'] ?? ''); 

// Tentukan halaman yang akan ditampilkan di SECTION (default: biodata)
$page = isset($_GET['page']) ? $_GET['page'] : 'biodata';

// --- 2. Ambil Data HANYA UNTUK PAGE YANG SEDANG AKTIF ---
$data = null;
$table_name = ($page == 'biodata') ? 'mahasiswa' : $page; 

if (in_array($table_name, ['mahasiswa', 'pendidikan', 'pengalaman', 'keahlian', 'publikasi'])) {
    $data = get_data_by_nim($koneksi, $table_name, $nim_profil);
}

// Data Profil untuk HEADER
$data_profil = get_data_by_nim($koneksi, 'mahasiswa', $nim_profil);

// Daftar Hobi dari database (tabel hobi)
$hobi_list = get_data_by_nim($koneksi, 'hobi', $nim_profil);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Mahasiswa - Gold & Onyx</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($data_profil['nama'] ?? 'Profil Mahasiswa'); ?></h1>
            
            <form id="select-form" action="index.php" method="GET" style="display: flex; align-items: center;">
                <input type="hidden" name="page" value="<?php echo htmlspecialchars($page); ?>">
                <label for="nim_select" style="color:var(--color-secondary); margin-right: 10px; font-weight: 700;">Lihat Profil:</label>
                <select name="nim" id="nim_select" onchange="this.form.submit()">
                    <?php foreach ($mahasiswa_list as $mhs): ?>
                        <option value="<?php echo htmlspecialchars($mhs['nim']); ?>" 
                                <?php echo ($mhs['nim'] == $nim_profil) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($mhs['nama']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="admin.php" style="color: var(--color-secondary); margin-left: 20px; text-decoration: none; font-weight: 700;">[Admin CRUD]</a>
        </header>

        <main>
            <nav id="main-nav" class="card">
                <ul>
                    <li><a href="?nim=<?php echo $nim_profil; ?>&page=biodata" class="<?php echo ($page == 'biodata') ? 'active' : ''; ?>">Biodata</a></li>
                    <li><a href="?nim=<?php echo $nim_profil; ?>&page=pendidikan" class="<?php echo ($page == 'pendidikan') ? 'active' : ''; ?>">Pendidikan</a></li>
                    <li><a href="?nim=<?php echo $nim_profil; ?>&page=pengalaman" class="<?php echo ($page == 'pengalaman') ? 'active' : ''; ?>">Pengalaman</a></li>
                    <li><a href="?nim=<?php echo $nim_profil; ?>&page=keahlian" class="<?php echo ($page == 'keahlian') ? 'active' : ''; ?>">Keahlian</a></li> 
                    <li><a href="?nim=<?php echo $nim_profil; ?>&page=publikasi" class="<?php echo ($page == 'publikasi') ? 'active' : ''; ?>">Publikasi</a></li>
                </ul>
            </nav>
            
            <section id="main-content" class="card">
                <h2><?php echo ucfirst($page); ?></h2>

                <?php if (!$data_profil && $nim_profil): ?>
                    <p style="color: var(--color-secondary);">Data Profil (NIM: <?php echo $nim_profil; ?>) tidak ditemukan. Silakan tambahkan data di Admin CRUD.</p>
                <?php endif; ?>

                <?php if ($page == 'biodata' && $data): // Biodata ?>
                    <div class="data-item">
                        <p><strong>NIM:</strong> <?php echo htmlspecialchars($data['nim']); ?></p>
                        <p><strong>Nama:</strong> <?php echo htmlspecialchars($data['nama']); ?></p>
                        <p><strong>Agama:</strong> <?php echo htmlspecialchars($data['agama'] ?? '-'); ?></p>
                        <p><strong>Tanggal Lahir:</strong> <?php echo htmlspecialchars($data['tanggal_lahir'] ?? '-'); ?></p>
                        <p><strong>Tempat Lahir:</strong> <?php echo htmlspecialchars($data['tempat_lahir'] ?? '-'); ?></p>
                    </div>
                
                <?php elseif ($page == 'pendidikan' && $data): // Pendidikan ?>
                    <?php foreach ($data as $pend): ?>
                        <div class="data-item">
                            <h3><?php echo htmlspecialchars($pend['jenjang']); ?></h3>
                            <p><strong>Institusi:</strong> <?php echo htmlspecialchars($pend['institusi']); ?></p>
                            <small><?php echo htmlspecialchars($pend['tahun_masuk']); ?> - <?php echo htmlspecialchars($pend['tahun_lulus']); ?></small>
                        </div>
                    <?php endforeach; ?>
                
                <?php elseif ($page == 'pengalaman' && $data): // Pengalaman ?>
                    <?php foreach ($data as $peng): ?>
                        <div class="data-item">
                            <h3><?php echo htmlspecialchars($peng['judul']); ?> <small style="color:var(--color-secondary);">(<?php echo htmlspecialchars($peng['jenis']); ?>)</small></h3>
                            <p><?php echo htmlspecialchars($peng['deskripsi'] ?? '-'); ?></p>
                            <small>Tahun: <?php echo htmlspecialchars($peng['tahun']); ?></small>
                        </div>
                    <?php endforeach; ?>

                <?php elseif ($page == 'keahlian' && $data): // KEHLIAN MASUK SECTION ?>
                    <?php if ($data): ?>
                        <?php foreach ($data as $skill): ?>
                            <div class="skill-item data-item">
                                <div class="skill-text">
                                    <span><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                                    <span><?php echo htmlspecialchars($skill['level']); ?>%</span>
                                </div>
                                <div class="skill-bar-container">
                                    <div class="skill-bar" style="width: <?php echo htmlspecialchars($skill['level']); ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Belum ada data keahlian.</p>
                    <?php endif; ?>

                <?php elseif ($page == 'publikasi' && $data): // Publikasi ?>
    <ul class="pub-list">
        <?php foreach ($data as $pub): ?>
            <?php 
                $file_name = $pub['file_publikasi'] ?? '';
                $file_url  = $file_name ? $target_dir . $file_name : '';
                $ext = $file_name ? strtolower(pathinfo($file_name, PATHINFO_EXTENSION)) : '';
            ?>
            <li class="pub-item">
                <div class="pub-header">
                    <strong><?php echo htmlspecialchars($pub['judul']); ?></strong>
                    <span class="pub-year">(<?php echo htmlspecialchars($pub['tahun']); ?>)</span>
                </div>

                

                <?php if ($file_name): ?>
                    <div class="pub-file-link">
                        <a href="<?php echo htmlspecialchars($file_url); ?>" target="_blank" style="color: var(--color-secondary);">
                            Unduh File (<?php echo strtoupper($ext); ?>)
                        </a>
                    </div>

                    <?php if (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
                        <!-- Preview gambar -->
                        <div class="pub-preview pub-preview-img">
                            <img src="<?php echo htmlspecialchars($file_url); ?>" 
                                 alt="Preview publikasi - <?php echo htmlspecialchars($pub['judul']); ?>">
                        </div>

                    <?php elseif ($ext === 'pdf'): ?>
                        <!-- Preview PDF -->
                        <div class="pub-preview pub-preview-pdf">
                            <embed src="<?php echo htmlspecialchars($file_url); ?>" 
                                   type="application/pdf">
                        </div>

                    <?php else: ?>
                        <!-- File lain (doc/docx dll) tidak dipreview -->
                        <div class="pub-preview-note">
                            <small>Preview tidak tersedia untuk tipe file ini.</small>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="pub-preview-note">
                        <small>Belum ada file yang diunggah untuk publikasi ini.</small>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

                
                <?php else: ?>
                    <p>Konten belum tersedia atau tidak ada data untuk kategori **<?php echo ucfirst($page); ?>**.</p>
                <?php endif; ?>
            </section>
            
            <aside id="sidebar" class="card">
    <h2>Hobi</h2>
    <ul>
        <?php if ($hobi_list && is_array($hobi_list)): ?>
            <?php foreach ($hobi_list as $hobi): ?>
                <li><?php echo htmlspecialchars($hobi['nama_hobi']); ?></li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>Belum ada data hobi.</li>
        <?php endif; ?>
    </ul>
</aside>

        </main>

        <footer>
            <div class="social-links">
                Twitter: @akun | FB: @akun | Instagram: @akun
            </div>
            <div class="copyright">
                &copy; Copyright 2020. All Rights Reserved
            </div>
            <div class="web-info">
                Kelompok 7 | WE MAKE IT HAPPEND
            </div>
        </footer>
    </div>
    <script>
        // ... (Script JS) ...
    </script>
</body>
</html>
    </div>
    <script>
        document.getElementById('nim_select').addEventListener('change', function() {
            var form = this.form;
            if (!form.elements.page.value) {
                form.elements.page.value = 'biodata';
            }
            form.submit();
        });
    </script>
</body>
</html>