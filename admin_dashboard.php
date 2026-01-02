<?php 
session_start(); 
include "koneksi.php";

// Cek Login
if(!isset($_SESSION['nip'])) { header("location:login.php"); exit; }

// Data Session
$role = $_SESSION['role'];
$nama = $_SESSION['nama'];
$nip_saya = $_SESSION['nip'];
$view = isset($_GET['view']) ? $_GET['view'] : 'menu';

// =================================================================================
// BAGIAN LOGIKA (BACKEND PHP)
// =================================================================================

// --- LOGIKA 1: GENERATE GAJI (KHUSUS ADMIN) ---
if(isset($_POST['generate']) && $role == 'Admin'){
    $bulan = $_POST['bulan'];
    $tahun = $_POST['tahun'];
    $periode = $bulan . " " . $tahun;
    $tgl_now = date('Y-m-d');
    
    // Mapping Bulan ke Angka untuk Query Database
    $list_bulan = [
        'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
        'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
        'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
    ];
    $bulan_angka = $list_bulan[$bulan];

    // Ambil Data Karyawan
    $sql_kar = mysqli_query($koneksi, "SELECT k.nip, j.gaji_pokok, j.tunjangan_makan, j.tunjangan_transport FROM karyawan k JOIN jabatan j ON k.kode_jabatan = j.kode_jabatan");
    
    $sukses = 0;
    while($k = mysqli_fetch_array($sql_kar)){
        $nip = $k['nip'];
        $id_slip = "SLIP-" . $nip . "-" . $bulan . $tahun; 

        // A. HITUNG JAM LEMBUR REAL (Otomatis dari Tabel Absensi)
        $q_lembur = mysqli_query($koneksi, "SELECT SUM(durasi_lembur) as total_jam FROM absensi WHERE nip='$nip' AND MONTH(tanggal)='$bulan_angka' AND YEAR(tanggal)='$tahun'");
        $d_lembur = mysqli_fetch_array($q_lembur);
        $total_jam_lembur = ($d_lembur['total_jam'] == "") ? 0 : $d_lembur['total_jam'];

        // B. HITUNG UANG LEMBUR (Tarif: Rp 50.000 / Jam)
        $tarif_lembur = 50000; 
        $uang_lembur = $total_jam_lembur * $tarif_lembur;

        // C. HITUNG GAJI BERSIH
        $gapok = $k['gaji_pokok'];
        $tunjangan = $k['tunjangan_makan'] + $k['tunjangan_transport'];
        $bpjs = 0.03 * $gapok;
        $pph21 = 0.02 * $gapok;
        
        $total_netto = ($gapok + $tunjangan + $uang_lembur) - ($bpjs + $pph21);

        // D. SIMPAN KE DATABASE (Jika belum ada)
        $cek = mysqli_query($koneksi, "SELECT * FROM slip_gaji WHERE id_slip='$id_slip'");
        if(mysqli_num_rows($cek) == 0){
            $q = "INSERT INTO slip_gaji VALUES ('$id_slip', '$nip', '$periode', '$tgl_now', '$uang_lembur', '0', '$pph21', '$bpjs', '$total_netto', 'Pending')";
            mysqli_query($koneksi, $q);
            $sukses++;
        }
    }
    echo "<script>alert('Berhasil! $sukses slip gaji (termasuk hitungan lembur) diajukan ke Finance.'); window.location='admin_dashboard.php?view=payroll';</script>";
}

// --- LOGIKA 2: VALIDASI (KHUSUS FINANCE) ---
if(isset($_POST['approve']) && $role == 'Finance'){
    $id_slip = $_POST['id_slip'];
    mysqli_query($koneksi, "UPDATE slip_gaji SET status='Paid' WHERE id_slip='$id_slip'");
    echo "<script>alert('Slip Gaji Disetujui!'); window.location='admin_dashboard.php?view=validasi';</script>";
}

// --- LOGIKA 3: INPUT ABSENSI (KHUSUS SUPERVISOR) ---
if(isset($_POST['input_absen'])){
    $nip_absen = $_POST['nip_karyawan'];
    // Ambil tanggal dari form (bisa backdate), default hari ini
    $tgl = !empty($_POST['tanggal_absen']) ? $_POST['tanggal_absen'] : date('Y-m-d');
    $masuk = $_POST['jam_masuk'];
    $keluar = $_POST['jam_keluar'];
    
    // Hitung Lembur Sederhana (Jika Pulang > 17:00)
    $jam_pulang_angka = (int)substr($keluar, 0, 2);
    $durasi_lembur = ($jam_pulang_angka > 17) ? ($jam_pulang_angka - 17) : 0;

    // Cek Double Input
    $cek = mysqli_query($koneksi, "SELECT * FROM absensi WHERE nip='$nip_absen' AND tanggal='$tgl'");
    if(mysqli_num_rows($cek) > 0){
        echo "<script>alert('Gagal! Karyawan ini sudah absen pada tanggal $tgl.');</script>";
    } else {
        $q_absen = "INSERT INTO absensi (nip, tanggal, jam_masuk, jam_keluar, durasi_lembur) VALUES ('$nip_absen', '$tgl', '$masuk', '$keluar', '$durasi_lembur')";
        if(mysqli_query($koneksi, $q_absen)){
            // Redirect kembali ke tanggal yang diinput agar supervisor bisa langsung lihat hasilnya
            echo "<script>alert('Absensi Disimpan! Lembur: $durasi_lembur Jam'); window.location='admin_dashboard.php?view=monitoring&tgl=$tgl';</script>";
        } else {
            echo "<script>alert('Gagal Simpan Database.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sistem Payroll - <?php echo $view; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* CSS Dashboard Grid */
        .dashboard-menu { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .menu-card { 
            background: white; padding: 25px; border-radius: 12px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: center; 
            border-top: 5px solid #D84315; transition: 0.3s; border: 1px solid #eee;
        }
        .menu-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        
        /* CSS Khusus Print (Surat) */
        @media print {
            .no-print, .admin-header, .left-sidebar, header, footer { display: none !important; }
            .container { box-shadow: none; margin: 0; width: 100%; max-width: 100%; background: white; }
            body { background: white; }
            .slip-area { border: 2px solid #000 !important; padding: 20px !important; display: block !important; }
        }
    </style>
</head>
<body>

<div class="container" style="padding:20px; background:#f9f9f9; min-height:100vh;">

    <div class="admin-header no-print" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="color:#D84315; margin:0;">Sistem Payroll Roti Nusantara</h2>
            <p style="font-size:0.9rem; color:#666; margin:0;">Halo, <strong><?php echo $nama; ?></strong> (<?php echo $role; ?>)</p>
        </div>
        
        <div style="display:flex; gap:10px;">
            <a href="index.php" class="btn btn-primary" style="background:#333;">🏠 Ke Halaman Utama</a>
            
            <?php if($view != 'menu') { ?>
                <a href="admin_dashboard.php" class="btn btn-secondary">← Kembali Menu</a>
            <?php } else { ?>
                <a href="login.php?action=logout" class="btn btn-danger">Keluar</a>
            <?php } ?>
        </div>
    </div>

    <?php if($view == 'menu') { ?>
        
        <div class="dashboard-menu">
            <?php if($role == 'Admin') { ?>
                <div class="menu-card">
                    <div style="font-size:3rem;">👥</div><h3>Kelola Karyawan</h3>
                    <a href="admin_karyawan.php" class="btn btn-primary">Buka Menu</a>
                </div>
                <div class="menu-card" style="border-top-color:#FFB74D;">
                    <div style="font-size:3rem;">💰</div><h3>Kelola Payroll</h3>
                    <a href="admin_dashboard.php?view=payroll" class="btn btn-warning">Buka Menu</a>
                </div>
            <?php } ?>

            <?php if($role == 'Finance') { ?>
                <div class="menu-card" style="border-top-color:#29B6F6;">
                    <div style="font-size:3rem;">✅</div><h3>Validasi Payroll</h3>
                    <a href="admin_dashboard.php?view=validasi" class="btn btn-primary">Buka Validasi</a>
                </div>
            <?php } ?>

            <?php if($role == 'Supervisor' || $role == 'SPV') { ?>
                <div class="menu-card" style="border-top-color:#AB47BC;">
                    <div style="font-size:3rem;">👀</div><h3>Monitoring Tim</h3>
                    <a href="admin_dashboard.php?view=monitoring" class="btn btn-primary">Buka Monitoring</a>
                </div>
            <?php } ?>

            <div class="menu-card" style="border-top-color:#66BB6A;">
                <div style="font-size:3rem;">📄</div><h3>Slip Gaji Saya</h3>
                <a href="admin_dashboard.php?view=slip" class="btn btn-primary" style="background:#2E7D32;">Lihat Slip</a>
            </div>
        </div>

    <?php } elseif($view == 'payroll' && $role == 'Admin') { ?>
        
        <div class="form-card" style="border-top:5px solid #FF8C42;">
            <h3>⚡ Ajukan Gaji Baru</h3>
            <p>Sistem akan menghitung total lembur otomatis dari data Absensi.</p>
            <form method="POST" style="display:flex; gap:15px; align-items:flex-end;">
                <div style="flex:1;"><label>Bulan</label>
                    <select name="bulan" class="input-field">
                        <option>Januari</option><option>Februari</option><option>Maret</option>
                        <option>April</option><option>Mei</option><option>Juni</option>
                        <option>Juli</option><option>Agustus</option><option>September</option>
                        <option>Oktober</option><option>November</option><option>Desember</option>
                    </select>
                </div>
                <div style="flex:1;"><label>Tahun</label>
                    <select name="tahun" class="input-field">
                        <option>2025</option><option>2026</option><option>2027</option>
                        <option>2028</option><option>2029</option><option>2030</option>
                    </select>
                </div>
                <button type="submit" name="generate" class="btn btn-primary">⚙️ Ajukan</button>
            </form>
        </div>

        <div class="form-card">
            <h3>📊 Riwayat Terakhir</h3>
            <div class="table-scroll">
                <table class="table-crud">
                    <thead><tr><th>ID</th><th>NIP</th><th>Periode</th><th>Lembur (Rp)</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php 
                        $sql = mysqli_query($koneksi, "SELECT * FROM slip_gaji ORDER BY tgl_generate DESC LIMIT 50");
                        while($r=mysqli_fetch_array($sql)){ 
                            echo "<tr>
                                    <td>".$r['id_slip']."</td>
                                    <td>".$r['nip']."</td>
                                    <td>".$r['periode']."</td>
                                    <td style='color:green;'>Rp ".number_format($r['total_lembur'])."</td>
                                    <td>".$r['status']."</td>
                                  </tr>"; 
                        } 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php } elseif($view == 'validasi' && $role == 'Finance') { ?>

        <div class="form-card">
            <h3>✅ Approval Gaji</h3>
            <div class="table-scroll">
                <table class="table-crud">
                    <thead><tr><th>NIP</th><th>Periode</th><th>Netto</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php 
                        $sql = mysqli_query($koneksi, "SELECT * FROM slip_gaji WHERE status='Pending'");
                        if(mysqli_num_rows($sql) > 0) {
                            while($r=mysqli_fetch_array($sql)){ ?>
                            <tr>
                                <td><?php echo $r['nip']; ?></td>
                                <td><?php echo $r['periode']; ?></td>
                                <td style="font-weight:bold;">Rp <?php echo number_format($r['total_gaji_netto']); ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="id_slip" value="<?php echo $r['id_slip']; ?>">
                                        <button type="submit" name="approve" class="btn btn-primary">✔ Setujui</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } } else { echo "<tr><td colspan='4'>Tidak ada data pending.</td></tr>"; } ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php } elseif($view == 'monitoring' && ($role == 'Supervisor' || $role == 'SPV')) { 
        
        // Logika Filter Tanggal (Default Hari Ini)
        if(isset($_POST['filter_tgl'])) {
            $tgl_view = $_POST['tgl_pilih'];
        } elseif(isset($_GET['tgl'])) {
            $tgl_view = $_GET['tgl'];
        } else {
            $tgl_view = date('Y-m-d');
        }
    ?>

        <div class="form-card" style="border-top:5px solid #AB47BC;">
            <h3>⏱️ Input Absensi Manual</h3>
            <p>Masukkan data kehadiran. Jika pulang > 17:00 otomatis dihitung lembur.</p>
            <form method="POST" style="display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap;">
                <div style="flex:1; min-width:200px;">
                    <label>Pilih Karyawan</label>
                    <select name="nip_karyawan" class="input-field" required>
                        <option value="">-- Pilih --</option>
                        <?php
                        $kar = mysqli_query($koneksi, "SELECT nip, nama_lengkap FROM karyawan ORDER BY nama_lengkap ASC");
                        while($k = mysqli_fetch_array($kar)){ echo "<option value='".$k['nip']."'>".$k['nama_lengkap']."</option>"; }
                        ?>
                    </select>
                </div>
                <div style="flex:1;">
                    <label>Tanggal Absen</label>
                    <input type="date" name="tanggal_absen" class="input-field" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div style="flex:1;"><label>Masuk</label><input type="time" name="jam_masuk" class="input-field" value="08:00" required></div>
                <div style="flex:1;"><label>Pulang</label><input type="time" name="jam_keluar" class="input-field" value="17:00" required></div>
                <button type="submit" name="input_absen" class="btn btn-primary" style="background:#AB47BC;">💾 Simpan</button>
            </form>
        </div>

        <div class="form-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">
                <h3 style="margin:0;">📅 Laporan Kehadiran</h3>
                <form method="POST" style="display:flex; gap:10px; align-items:center;">
                    <span style="font-weight:bold;">Lihat Tanggal:</span>
                    <input type="date" name="tgl_pilih" value="<?php echo $tgl_view; ?>" style="padding:5px; border:1px solid #ddd; border-radius:5px;">
                    <button type="submit" name="filter_tgl" class="btn btn-primary" style="padding:6px 15px; font-size:0.9rem;">Cari</button>
                </form>
            </div>

            <p>Data Tanggal: <strong><?php echo date('d F Y', strtotime($tgl_view)); ?></strong></p>

            <div class="table-scroll">
                <table class="table-crud">
                    <thead><tr><th>Nama</th><th>Masuk</th><th>Pulang</th><th>Lembur</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php
                        // Tampilkan Absensi sesuai Tanggal Filter
                        $sql_mon = mysqli_query($koneksi, "SELECT a.*, k.nama_lengkap FROM absensi a JOIN karyawan k ON a.nip = k.nip WHERE a.tanggal = '$tgl_view' ORDER BY a.jam_masuk ASC");
                        
                        if(mysqli_num_rows($sql_mon) > 0){
                            while($m = mysqli_fetch_array($sql_mon)){
                                $status = ($m['jam_masuk'] > '08:00:00') ? "<span style='color:red;'>Terlambat</span>" : "<span style='color:green;'>Tepat Waktu</span>";
                                echo "<tr>
                                        <td>".$m['nama_lengkap']."</td>
                                        <td>".$m['jam_masuk']."</td>
                                        <td>".$m['jam_keluar']."</td>
                                        <td style='font-weight:bold; color:#d35400;'>".$m['durasi_lembur']." Jam</td>
                                        <td>$status</td>
                                      </tr>";
                            }
                        } else { echo "<tr><td colspan='5' align='center' style='padding:20px; color:#999;'>Tidak ada data absensi pada tanggal ini.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php } elseif($view == 'slip') { ?>

        <div class="form-card">
            <h3>📄 Riwayat Slip Gaji</h3>
            <div class="table-scroll">
                <table class="table-crud">
                    <thead><tr><th>Periode</th><th>Lembur (Rp)</th><th>Total Netto</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php
                        $sql = mysqli_query($koneksi, "SELECT * FROM slip_gaji WHERE nip='$nip_saya' ORDER BY tgl_generate DESC");
                        while($d = mysqli_fetch_array($sql)){
                            $link = ($d['status']=='Paid') ? "admin_dashboard.php?view=cetak&id=".$d['id_slip'] : "#";
                            $btn_cls = ($d['status']=='Paid') ? 'btn-primary' : 'btn-secondary';
                            $btn_txt = ($d['status']=='Paid') ? '🖨️ Detail/Cetak' : '⏳ Proses';
                        ?>
                            <tr>
                                <td><?php echo $d['periode']; ?></td>
                                <td style="color:green;">+ Rp <?php echo number_format($d['total_lembur']); ?></td>
                                <td style="font-weight:bold;">Rp <?php echo number_format($d['total_gaji_netto']); ?></td>
                                <td><?php echo $d['status']; ?></td>
                                <td><a href="<?php echo $link; ?>" class="btn <?php echo $btn_cls; ?>"><?php echo $btn_txt; ?></a></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php } elseif($view == 'cetak' && isset($_GET['id'])) { 
        $id = $_GET['id'];
        $q = mysqli_query($koneksi, "SELECT s.*, k.nama_lengkap, k.nip, k.kode_jabatan, j.nama_jabatan, j.gaji_pokok, j.tunjangan_makan, j.tunjangan_transport FROM slip_gaji s JOIN karyawan k ON s.nip = k.nip JOIN jabatan j ON k.kode_jabatan = j.kode_jabatan WHERE s.id_slip='$id'");
        $d = mysqli_fetch_array($q);
        
        $gaji_kotor = $d['gaji_pokok'] + $d['tunjangan_makan'] + $d['tunjangan_transport'] + $d['total_lembur'];
        $potongan = $d['potongan_pph21'] + $d['potongan_bpjs'];
    ?>
        
        <div class="no-print" style="text-align:center; margin-bottom:20px;">
            <button onclick="window.print()" class="btn btn-primary">🖨️ Cetak PDF</button><br><br>
            <a href="admin_dashboard.php?view=slip">Kembali</a>
        </div>

        <div class="slip-area" style="background:white; padding:40px; border:1px solid #ddd; max-width:800px; margin:0 auto;">
            <div style="text-align:center; border-bottom:3px double #333; padding-bottom:15px; margin-bottom:25px;">
                <h2 style="margin:0;">PT. ROTI NUSANTARA</h2>
                <p style="margin:5px 0;">Jl. Raya Serpong No. 12, Tangerang Selatan</p>
            </div>
            <h3 style="text-align:center; text-decoration:underline;">SLIP GAJI KARYAWAN</h3>
            <table style="width:100%; margin-bottom:20px;">
                <tr><td width="20%"><strong>NIP</strong></td><td>: <?php echo $d['nip']; ?></td><td width="20%"><strong>Periode</strong></td><td>: <?php echo $d['periode']; ?></td></tr>
                <tr><td><strong>Nama</strong></td><td>: <?php echo $d['nama_lengkap']; ?></td><td><strong>Jabatan</strong></td><td>: <?php echo $d['nama_jabatan']; ?></td></tr>
            </table>
            <div style="display:flex; border:1px solid #000;">
                <div style="width:50%; padding:15px; border-right:1px solid #000;">
                    <h4 style="margin-top:0; text-decoration:underline;">PENERIMAAN</h4>
                    <div style="display:flex; justify-content:space-between;"><span>Gaji Pokok</span><span>Rp <?php echo number_format($d['gaji_pokok']); ?></span></div>
                    <div style="display:flex; justify-content:space-between;"><span>Tunjangan</span><span>Rp <?php echo number_format($d['tunjangan_makan']+$d['tunjangan_transport']); ?></span></div>
                    <div style="display:flex; justify-content:space-between; color:green;"><span>Lembur</span><span>Rp <?php echo number_format($d['total_lembur']); ?></span></div>
                    <br><div style="display:flex; justify-content:space-between; font-weight:bold; border-top:1px dashed #ccc;"><span>Total Kotor</span><span>Rp <?php echo number_format($gaji_kotor); ?></span></div>
                </div>
                <div style="width:50%; padding:15px;">
                    <h4 style="margin-top:0; text-decoration:underline;">POTONGAN</h4>
                    <div style="display:flex; justify-content:space-between;"><span>PPh 21</span><span>Rp <?php echo number_format($d['potongan_pph21']); ?></span></div>
                    <div style="display:flex; justify-content:space-between;"><span>BPJS</span><span>Rp <?php echo number_format($d['potongan_bpjs']); ?></span></div>
                    <br><br><div style="display:flex; justify-content:space-between; font-weight:bold; border-top:1px dashed #ccc;"><span>Total Potongan</span><span>(Rp <?php echo number_format($potongan); ?>)</span></div>
                </div>
            </div>
            <div style="background:#eee; border:1px solid #000; padding:15px; margin-top:10px; font-weight:bold; font-size:1.1rem; display:flex; justify-content:space-between;"><span>TOTAL DITERIMA (NETTO)</span><span>Rp <?php echo number_format($d['total_gaji_netto']); ?></span></div>
            <div style="display:flex; justify-content:space-between; margin-top:50px; text-align:center;"><div style="width:150px;"><p>Penerima,</p><br><br><br><p><strong><?php echo $d['nama_lengkap']; ?></strong></p></div><div style="width:150px;"><p>Manager Keuangan,</p><br><br><br><p><strong>Siti Aminah, S.E.</strong></p></div></div>
        </div>

    <?php } else { 
        // CEGAH ADMIN AKSES HALAMAN SUPERVISOR SECARA PAKSA VIA URL
        if($view == 'monitoring' && $role == 'Admin') {
            echo "<div class='container' style='text-align:center; padding:50px;'><h2 style='color:red;'>Akses Ditolak</h2><p>Halaman Monitoring hanya untuk Supervisor.</p><a href='admin_dashboard.php' class='btn btn-primary'>Kembali</a></div>";
        }
    } ?>

</div>
</body>
</html>