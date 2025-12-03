<?php
include 'koneksi.php'; 
$sukses = "";
$error = "";
$target_dir = "uploads/";

// --- 1. TENTUKAN TABEL & OPERASI YANG DIINGINKAN ---
$table = isset($_GET['table']) ? $_GET['table'] : 'mahasiswa';
$op = isset($_GET['op']) ? $_GET['op'] : '';

// --- LOGIKA HAPUS (DELETE) ---
if ($op == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql_hapus = "DELETE FROM {$table} WHERE id = $id";
    
    if ($table == 'publikasi') {
        $q_file = $koneksi->query("SELECT file_publikasi FROM publikasi WHERE id = $id");
        $r_file = $q_file->fetch_assoc();
        if ($r_file && file_exists($target_dir . $r_file['file_publikasi'])) {
            unlink($target_dir . $r_file['file_publikasi']);
        }
    }
    
    if ($koneksi->query($sql_hapus)) {
        $sukses = "Data dari tabel '{$table}' berhasil dihapus.";
    } else {
        $error = "Gagal menghapus data: " . $koneksi->error;
    }
    header("Location: admin.php?table={$table}");
    exit();
}

// --- LOGIKA UNTUK TAMPIL DATA YANG AKAN DIEDIT (READ FOR UPDATE) ---
$data_edit = null;
if ($op == 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql_edit = "SELECT * FROM {$table} WHERE id = $id";
    $q_edit = $koneksi->query($sql_edit);
    $data_edit = $q_edit->fetch_assoc();
    if (!$data_edit) {
        $error = "Data tidak ditemukan.";
    }
}

// --- LOGIKA SIMPAN/UPDATE (CREATE & UPDATE) ---
if (isset($_POST['simpan'])) {
    
    $file_publikasi_name = $data_edit['file_publikasi'] ?? null;
    $upload_error = false;

    // A. Penanganan FILE UPLOAD
    if ($table == 'publikasi' && isset($_FILES['file_publikasi']) && $_FILES['file_publikasi']['error'] == UPLOAD_ERR_OK) {
        $target_file = $target_dir . basename($_FILES["file_publikasi"]["name"]);
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $unique_name = time() . "_" . basename($_FILES["file_publikasi"]["name"]);
        $final_target = $target_dir . $unique_name;
        
        if ($file_type != "pdf" && $file_type != "docx" && $file_type != "jpg" && $file_type != "png") {
            $error = "Hanya file PDF, DOCX, JPG, & PNG yang diizinkan.";
            $upload_error = true;
        } elseif (move_uploaded_file($_FILES["file_publikasi"]["tmp_name"], $final_target)) {
            if ($op == 'edit' && $file_publikasi_name && file_exists($target_dir . $file_publikasi_name)) {
                unlink($target_dir . $file_publikasi_name);
            }
            $file_publikasi_name = $unique_name;
        } else {
            $error = "Gagal mengunggah file.";
            $upload_error = true;
        }
    }

    // B. Susun Field dan Nilai berdasarkan Tabel
    $fields = [];
    $update_parts = [];
    
    switch ($table) {
        case 'mahasiswa':
            $fields = ['nim', 'nama', 'agama', 'tanggal_lahir', 'tempat_lahir'];
            $nim_val = $_POST['nim'] ?? '';
            break;
        case 'pendidikan':
            $fields = ['nim_mhs', 'jenjang', 'institusi', 'tahun_masuk', 'tahun_lulus'];
            break;
        case 'pengalaman':
            $fields = ['nim_mhs', 'judul', 'deskripsi', 'tahun', 'jenis'];
            break;
        case 'keahlian':
            $fields = ['nim_mhs', 'skill_name', 'level'];
            break;
        case 'hobi':
            $fields = ['nim_mhs', 'nama_hobi'];
            break;
        case 'publikasi':
            $fields = ['nim_mhs', 'judul', 'tahun', 'link_url', 'file_publikasi'];
        $_POST['file_publikasi'] = $file_publikasi_name; 
            break;

    }

    // C. Validasi dan Persiapan Query
    $valid = true;
    $values = [];
    foreach ($fields as $field) {
        $value = $koneksi->real_escape_string($_POST[$field] ?? '');
        $values[] = "'$value'";
        $update_parts[] = "$field = '$value'";
        
        if (empty($value) && !in_array($field, ['file_publikasi', 'link_url', 'deskripsi', 'agama', 'tanggal_lahir', 'tempat_lahir'])) {
            $valid = false;
        }
    }

    if ($valid && !$upload_error) {
        if ($op == 'edit' && isset($_GET['id'])) {
            // UPDATE
            $sql_query = "UPDATE {$table} SET " . implode(', ', $update_parts) . " WHERE id = {$_GET['id']}";
            $action = "diubah";
        } else {
            // CREATE
            if ($table == 'mahasiswa') {
                $sql_check = $koneksi->query("SELECT nim FROM mahasiswa WHERE nim = '$nim_val'");
                if ($sql_check->num_rows > 0) {
                    $error = "NIM sudah terdaftar.";
                    $valid = false;
                }
            }
            if ($valid) {
                $sql_query = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
                $action = "ditambahkan";
            }
        }

        // Eksekusi Query
        if ($valid && $koneksi->query($sql_query)) {
            $sukses = "Data di tabel '{$table}' berhasil {$action}.";
        } elseif ($valid) {
            $error = "Gagal memproses data: " . $koneksi->error;
        }
        
        if($sukses) {
             header("Location: admin.php?table={$table}");
             exit();
        }

    } elseif (!$valid) {
        $error = "Silakan isi semua field wajib!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>CRUD All-in-One: <?php echo ucfirst($table); ?></title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>
    <div class="container">
        <header style="justify-content: flex-start; padding: 20px;">
            <h1>Admin Panel CRUD</h1>
            <a href="index.php" class="button-link" style="margin-left: 40px;">← Lihat Profil</a>
        </header>

        <main style="flex-direction: column;">
            <div class="card" style="padding: 15px; margin-bottom: 20px;">
                <h3 style="color: var(--color-text-light); font-family: 'Poppins', sans-serif;">Pilih Tabel untuk Dikelola:</h3>
                <div class="menu" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                <a href="admin.php?table=mahasiswa" class="<?php echo ($table == 'mahasiswa') ? 'active' : ''; ?>">Biodata</a>
                <a href="admin.php?table=pendidikan" class="<?php echo ($table == 'pendidikan') ? 'active' : ''; ?>">Pendidikan</a>
                <a href="admin.php?table=pengalaman" class="<?php echo ($table == 'pengalaman') ? 'active' : ''; ?>">Pengalaman</a>
                <a href="admin.php?table=keahlian" class="<?php echo ($table == 'keahlian') ? 'active' : ''; ?>">Keahlian</a>
                <a href="admin.php?table=hobi" class="<?php echo ($table == 'hobi') ? 'active' : ''; ?>">Hobi</a>
                <a href="admin.php?table=publikasi" class="<?php echo ($table == 'publikasi') ? 'active' : ''; ?>">Publikasi</a>
            </div>

            <?php if ($error): ?>
                <div class="error card" style="background-color: #ff5252; color: white; padding: 10px; border-radius: 4px; box-shadow: 0 0 10px #ff5252; border: none; font-weight: 700; ">Error: <?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($sukses): ?>
                <div class="success card" style="background-color: var(--color-secondary); color: var(--color-primary); padding: 10px; border-radius: 4px; box-shadow: 0 0 10px var(--color-secondary); font-weight: 700; border: none; ">Sukses: <?php echo $sukses; ?></div>
            <?php endif; ?>

            <div class="form-container card">
                <h3><?php echo ($op == 'edit') ? 'FORM UBAH DATA' : 'FORM TAMBAH DATA'; ?> - Tabel: <?php echo ucfirst($table); ?></h3>
                <p style="color: #999; margin-bottom: 15px;">*Gunakan NIM yang sama untuk menghubungkan data ke profil utama.</p>

                <form action="admin.php?table=<?php echo $table; ?><?php echo ($op == 'edit') ? '&op=edit&id=' . $_GET['id'] : ''; ?>" method="POST" enctype="<?php echo ($table == 'publikasi') ? 'multipart/form-data' : 'application/x-www-form-urlencoded'; ?>">
                    
                    <?php if ($table == 'mahasiswa'): ?>
                        <label>NIM:</label><input type="text" name="nim" value="<?php echo htmlspecialchars($data_edit['nim'] ?? ''); ?>" required <?php echo ($op == 'edit') ? 'readonly' : ''; ?>><br>
                        <label>Nama:</label><input type="text" name="nama" value="<?php echo htmlspecialchars($data_edit['nama'] ?? ''); ?>" required><br>
                        <label>Agama:</label><input type="text" name="agama" value="<?php echo htmlspecialchars($data_edit['agama'] ?? ''); ?>"><br>
                        <label>Tgl Lahir (YYYY-MM-DD):</label><input type="text" name="tanggal_lahir" value="<?php echo htmlspecialchars($data_edit['tanggal_lahir'] ?? ''); ?>"><br>
                        <label>Tempat Lahir:</label><input type="text" name="tempat_lahir" value="<?php echo htmlspecialchars($data_edit['tempat_lahir'] ?? ''); ?>"><br>
                    
                    <?php elseif ($table == 'pendidikan'): ?>
                        <label>NIM MHS:</label><input type="text" name="nim_mhs" value="<?php echo htmlspecialchars($data_edit['nim_mhs'] ?? ''); ?>" required><br>
                        <label>Jenjang:</label><input type="text" name="jenjang" value="<?php echo htmlspecialchars($data_edit['jenjang'] ?? ''); ?>" required><br>
                        <label>Institusi:</label><input type="text" name="institusi" value="<?php echo htmlspecialchars($data_edit['institusi'] ?? ''); ?>" required><br>
                        <label>Tahun Masuk:</label><input type="number" name="tahun_masuk" value="<?php echo htmlspecialchars($data_edit['tahun_masuk'] ?? ''); ?>" min="1900" max="2099"><br>
                        <label>Tahun Lulus:</label><input type="number" name="tahun_lulus" value="<?php echo htmlspecialchars($data_edit['tahun_lulus'] ?? ''); ?>" min="1900" max="2099"><br>

                    <?php elseif ($table == 'pengalaman'): ?>
                        <label>NIM MHS:</label><input type="text" name="nim_mhs" value="<?php echo htmlspecialchars($data_edit['nim_mhs'] ?? ''); ?>" required><br>
                        <label>Judul/Posisi:</label><input type="text" name="judul" value="<?php echo htmlspecialchars($data_edit['judul'] ?? ''); ?>" required><br>
                        <label>Tahun:</label><input type="text" name="tahun" value="<?php echo htmlspecialchars($data_edit['tahun'] ?? ''); ?>" required><br>
                        <label>Deskripsi:</label><textarea name="deskripsi"><?php echo htmlspecialchars($data_edit['deskripsi'] ?? ''); ?></textarea><br>
                        <label>Jenis:</label>
                        <select name="jenis">
                            <option value="Kerja" <?php echo (isset($data_edit['jenis']) && $data_edit['jenis'] == 'Kerja') ? 'selected' : ''; ?>>Kerja</option>
                            <option value="Organisasi" <?php echo (isset($data_edit['jenis']) && $data_edit['jenis'] == 'Organisasi') ? 'selected' : ''; ?>>Organisasi</option>
                            <option value="Lainnya" <?php echo (isset($data_edit['jenis']) && $data_edit['jenis'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                        </select><br>

                    <?php elseif ($table == 'keahlian'): ?>
                        <label>NIM MHS:</label><input type="text" name="nim_mhs" value="<?php echo htmlspecialchars($data_edit['nim_mhs'] ?? ''); ?>" required><br>
                        <label>Skill Name:</label><input type="text" name="skill_name" value="<?php echo htmlspecialchars($data_edit['skill_name'] ?? ''); ?>" required><br>
                        <label>Level (0-100):</label><input type="number" name="level" value="<?php echo htmlspecialchars($data_edit['level'] ?? ''); ?>" required min="0" max="100"><br>

                    <?php elseif ($table == 'hobi'): ?>
                        <label>NIM MHS:</label><input type="text" name="nim_mhs" value="<?php echo htmlspecialchars($data_edit['nim_mhs'] ?? ''); ?>" required><br>
                        <label>Nama Hobi:</label><input type="text" name="nama_hobi" value="<?php echo htmlspecialchars($data_edit['nama_hobi'] ?? ''); ?>" required><br>


                    <?php elseif ($table == 'publikasi'): ?>
                        <label>NIM MHS:</label><input type="text" name="nim_mhs" value="<?php echo htmlspecialchars($data_edit['nim_mhs'] ?? ''); ?>" required><br>
                        <label>Judul:</label><input type="text" name="judul" value="<?php echo htmlspecialchars($data_edit['judul'] ?? ''); ?>" required><br>
                        <label>Tahun:</label><input type="number" name="tahun" value="<?php echo htmlspecialchars($data_edit['tahun'] ?? ''); ?>" min="1900" max="2099"><br>
                        <label>Link URL (Opsional):</label><input type="text" name="link_url" value="<?php echo htmlspecialchars($data_edit['link_url'] ?? ''); ?>"><br>
                        
                        <label>File Publikasi:</label>
                        <input type="file" name="file_publikasi" style="padding: 10px 0;"><br>
                        <?php if ($op == 'edit' && $data_edit['file_publikasi']): ?>
                            <p style="color: var(--color-secondary); font-size: 0.9em; margin-top: -10px; margin-bottom: 10px;">File saat ini: <a href="<?php echo $target_dir . htmlspecialchars($data_edit['file_publikasi']); ?>" target="_blank" style="color: var(--color-secondary);"><?php echo htmlspecialchars($data_edit['file_publikasi']); ?></a> (Upload file baru untuk mengganti)</p>
                        <?php endif; ?>
                    <?php endif; ?>
                    <br>
                    <input type="submit" name="simpan" value="<?php echo ($op == 'edit') ? 'UBAH DATA' : 'SIMPAN'; ?>">
                    <?php if ($op == 'edit'): ?>
                         <a href="admin.php?table=<?php echo $table; ?>" class="button-link" style="margin-left: 10px; background-color: #555;">BATAL UBAH</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-container card">
                <h3>Data Saat Ini - Tabel: <?php echo ucfirst($table); ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th>NO</th>
                            <?php 
                                $sql_header = $koneksi->query("SELECT * FROM {$table} LIMIT 1");
                                $headers = [];
                                if ($sql_header && $sql_header->num_rows > 0) {
                                    $row = $sql_header->fetch_assoc();
                                    foreach ($row as $key => $value) {
                                        $headers[] = $key;
                                        echo "<th>" . strtoupper(str_replace('_', ' ', $key)) . "</th>";
                                    }
                                }
                            ?>
                            <th>KELOLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_read = "SELECT * FROM {$table} ORDER BY id DESC";
                        $q_read = $koneksi->query($sql_read);
                        $no = 1;
                        while ($r = $q_read->fetch_assoc()) {
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <?php foreach ($headers as $header): ?>
                                <td>
                                    <?php 
                                    if ($header == 'file_publikasi' && $table == 'publikasi' && $r['file_publikasi']) {
                                        echo "<a href='{$target_dir}" . htmlspecialchars($r['file_publikasi']) . "' target='_blank' style='color: var(--color-secondary);'>Lihat File</a>";
                                    } else {
                                        echo htmlspecialchars($r[$header]);
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <a href="admin.php?table=<?php echo $table; ?>&op=edit&id=<?php echo $r['id']; ?>" style="color: var(--color-secondary);">UBAH</a> | 
                                <a href="admin.php?table=<?php echo $table; ?>&op=delete&id=<?php echo $r['id']; ?>" class="delete-link" onclick="return confirm('Yakin menghapus data ini?')">HAPUS</a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>