<?php
session_start();
include "koneksi.php";

// Cek Login & Role
if(!isset($_SESSION['nip']) || $_SESSION['role'] != 'Admin') {
    header("location:login.php"); exit;
}

$v_nip=""; $v_nama=""; $v_jab=""; $action_state="tambah";

// --- LOGIKA CRUD ---

// 1. HAPUS
if(isset($_GET['hapus'])){
    mysqli_query($koneksi, "DELETE FROM karyawan WHERE nip='".$_GET['hapus']."'");
    echo "<script>alert('Data Berhasil Dihapus'); window.location='admin_karyawan.php';</script>";
}

// 2. EDIT (AMBIL DATA)
if(isset($_GET['edit'])){
    $d = mysqli_fetch_array(mysqli_query($koneksi, "SELECT * FROM karyawan WHERE nip='".$_GET['edit']."'"));
    $v_nip=$d['nip']; $v_nama=$d['nama_lengkap']; $v_jab=$d['kode_jabatan']; $action_state="ubah";
}

// 3. SIMPAN (INSERT / UPDATE)
if(isset($_POST['simpan'])){
    $nip=$_POST['nip']; 
    $nama=$_POST['nama']; 
    $jab=$_POST['jabatan']; 
    $st=$_POST['state'];

    // --- [BARU] CEK DUPLIKASI NIP ---
    // Jika sedang menambah data baru, cek dulu apakah NIP sudah ada?
    if($st == "tambah") {
        $cek_duplikat = mysqli_query($koneksi, "SELECT nip FROM karyawan WHERE nip='$nip'");
        if(mysqli_num_rows($cek_duplikat) > 0) {
            // Jika ketemu, munculkan alert dan hentikan proses
            echo "<script>
                    alert('GAGAL! NIP $nip sudah terdaftar. Silakan gunakan NIP lain.');
                    window.location='admin_karyawan.php';
                  </script>";
            exit; // Stop script disini
        }
    }

    // --- LOGIKA PENENTUAN ROLE OTOMATIS ---
    $cek_jab = mysqli_query($koneksi, "SELECT nama_jabatan FROM jabatan WHERE kode_jabatan='$jab'");
    $data_jab = mysqli_fetch_array($cek_jab);
    $nama_jabatan = $data_jab['nama_jabatan'];
    
    $role_otomatis = 'Karyawan'; // Default

    if(stripos($nama_jabatan, 'Admin') !== false) {
        $role_otomatis = 'Admin';
    } elseif(stripos($nama_jabatan, 'Finance') !== false) {
        $role_otomatis = 'Finance';
    } elseif(stripos($nama_jabatan, 'Supervisor') !== false || stripos($nama_jabatan, 'SPV') !== false) {
        $role_otomatis = 'SPV'; 
    }

    if($st=="tambah"){
        // Karena sudah dicek di atas, aman untuk insert
        $q = "INSERT INTO karyawan (nip, nama_lengkap, kode_jabatan, password, role) 
              VALUES ('$nip','$nama','$jab','12345','$role_otomatis')";
    } else {
        $q = "UPDATE karyawan SET nama_lengkap='$nama', kode_jabatan='$jab', role='$role_otomatis' WHERE nip='$nip'";
    }

    // Eksekusi Query dengan penanganan error sederhana
    if(mysqli_query($koneksi, $q)){
        echo "<script>alert('Data Berhasil Disimpan!'); window.location='admin_karyawan.php';</script>";
    } else {
        // Jika masih ada error lain (jarang terjadi)
        echo "<script>alert('Terjadi kesalahan sistem.');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Kelola Karyawan</title><link rel="stylesheet" href="css/style.css"></head>
<body>
    
<div class="container" style="padding:30px; background:#f9f9f9; min-height:100vh;">
    <div class="admin-header">
        <div>
            <h2 style="color:#D84315;">👥 Kelola Data Karyawan</h2>
            <p style="color:#666;">Tambah atau update data pegawai Roti Nusantara</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-secondary">← Kembali ke Dashboard</a>
    </div>

    <div class="form-card">
        <h3 style="border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px; color:#444;">
            <?php echo ($action_state == 'tambah') ? "➕ Tambah Karyawan" : "✏️ Ubah Data"; ?>
        </h3>
        
        <form method="POST">
            <input type="hidden" name="state" value="<?php echo $action_state; ?>">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div class="input-group">
                    <label>NIP</label>
                    <input type="text" name="nip" class="input-field" value="<?php echo $v_nip; ?>" <?php if($action_state=='ubah') echo 'readonly style="background:#eee;"'; ?> required placeholder="Contoh: EMP001">
                </div>

                <div class="input-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" class="input-field" value="<?php echo $v_nama; ?>" required placeholder="Nama Pegawai">
                </div>
            </div>

            <div class="input-group">
                <label>Jabatan (Role Otomatis Menyesuaikan)</label>
                <select name="jabatan" class="input-field">
                    <?php 
                    $jabs = mysqli_query($koneksi, "SELECT * FROM jabatan");
                    while($j=mysqli_fetch_array($jabs)){
                        $sel=($j['kode_jabatan']==$v_jab)?'selected':'';
                        echo "<option value='".$j['kode_jabatan']."' $sel>".$j['nama_jabatan']."</option>";
                    } 
                    ?>
                </select>
            </div>

            <div style="margin-top:20px; border-top:1px solid #eee; padding-top:20px;">
                <button type="submit" name="simpan" class="btn btn-primary">Simpan Data</button>
                <a href="admin_karyawan.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>

    <div class="form-card">
        <h3 style="margin-bottom:15px; color:#444;">📋 Daftar Pegawai Aktif</h3>
        
        <div class="table-scroll">
            <table class="table-crud">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th width="150">NIP</th>
                        <th>Nama Lengkap</th>
                        <th>Jabatan</th>
                        <th>Hak Akses (Role)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $sql = mysqli_query($koneksi, "SELECT k.*, j.nama_jabatan FROM karyawan k LEFT JOIN jabatan j ON k.kode_jabatan=j.kode_jabatan ORDER BY k.nip ASC");
                    while($row=mysqli_fetch_array($sql)){ 
                        $role_color = ($row['role'] == 'SPV') ? '#E1F5FE; color:#0277BD;' : '#f9f9f9; color:#555;';
                        if($row['role'] == 'Admin') $role_color = '#FFEBEE; color:#C62828;';
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><strong><?php echo $row['nip']; ?></strong></td>
                        <td><?php echo $row['nama_lengkap']; ?></td>
                        <td><?php echo $row['nama_jabatan']; ?></td>
                        <td>
                            <span style="padding:4px 8px; border-radius:5px; font-weight:bold; font-size:0.8rem; background:<?php echo $role_color; ?>">
                                <?php echo $row['role']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="admin_karyawan.php?edit=<?php echo $row['nip']; ?>" class="btn btn-warning" style="padding:5px 10px; font-size:0.8rem;">Edit</a>
                            <a href="admin_karyawan.php?hapus=<?php echo $row['nip']; ?>" class="btn btn-danger" style="padding:5px 10px; font-size:0.8rem;" onclick="return confirm('Hapus data ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>