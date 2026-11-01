<?php
session_start();
if (empty($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}
require __DIR__ . '/../koneksi.php';

$STATUS = ['Terdaftar', 'Karya terkirim', 'Diverifikasi', 'Perlu revisi', 'Ditolak'];
$e = fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID pendaftaran tidak valid.');
}
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Sesi tidak valid. Muat ulang halaman.');
    }

    $status = (string)($_POST['status'] ?? '');
    $catatan = mb_substr(trim((string)($_POST['catatan'] ?? '')), 0, 255);
    if (!in_array($status, $STATUS, true)) {
        http_response_code(400);
        exit('Status tidak valid.');
    }

    $koneksi->begin_transaction();
    try {
        $pendaftaran = $koneksi->execute_query(
            'SELECT pengguna_id, lomba_id FROM pendaftaran WHERE id=? FOR UPDATE',
            [$id]
        )->fetch_assoc();
        if (!$pendaftaran) {
            $koneksi->rollback();
            http_response_code(404);
            exit('Pendaftaran tidak ditemukan.');
        }

        $koneksi->execute_query(
            'UPDATE pendaftaran SET status=?, catatan_admin=? WHERE id=?',
            [$status, $catatan, $id]
        );
        $koneksi->execute_query(
            "UPDATE pendaftaran p SET p.tahap = IF(?='Perlu revisi', 'terbuka', IF(EXISTS(SELECT 1 FROM karya k WHERE k.pendaftaran_id=p.id), 'terkirim', p.tahap)) WHERE p.id=?",
            [$status, $id]
        );

        $teks = 'Status pendaftaran diperbarui menjadi: ' . $status . '.';
        if ($status === 'Perlu revisi') {
            $teks .= ' Kamu bisa mengunggah ulang karyamu.';
        }
        if ($catatan !== '') {
            $teks .= ' Catatan panitia: ' . mb_substr($catatan, 0, 140);
        }
        $koneksi->execute_query(
            'INSERT INTO notifikasi (pengguna_id, lomba_id, pesan) VALUES (?,?,?)',
            [(int)$pendaftaran['pengguna_id'], (int)$pendaftaran['lomba_id'], mb_substr($teks, 0, 255)]
        );
        $koneksi->commit();
    } catch (Throwable $error) {
        $koneksi->rollback();
        throw $error;
    }

    header('Location: tinjau.php?id=' . $id . '&tersimpan=1');
    exit;
}

$r = $koneksi->execute_query(
    'SELECT p.*, l.nama AS lomba, k.judul, k.file_pdf, k.file_word,
            k.file_kartu AS karya_file_kartu, k.file_pernyataan, k.dikirim_pada
     FROM pendaftaran p
     JOIN lomba l ON l.id=p.lomba_id
     LEFT JOIN karya k ON k.pendaftaran_id=p.id
     WHERE p.id=?',
    [$id]
)->fetch_assoc();
if (!$r) {
    http_response_code(404);
    exit('Pendaftaran tidak ditemukan.');
}

$berkas = [
    ['label' => 'Naskah PDF', 'nama' => $r['file_pdf'] ?? '', 'preview' => true],
    ['label' => 'Naskah Word', 'nama' => $r['file_word'] ?? '', 'preview' => false],
    ['label' => 'Kartu peserta', 'nama' => $r['file_kartu'] ?: ($r['karya_file_kartu'] ?? ''), 'preview' => true],
    ['label' => 'Pernyataan keaslian', 'nama' => $r['file_pernyataan'] ?? '', 'preview' => true],
];
$uploads = 'unduh.php?f=';
$tanggal = static function (?string $value): string {
    return $value ? date('d M Y, H:i', strtotime($value)) : '-';
};
$ext = strtolower(pathinfo((string)$r['file_pdf'], PATHINFO_EXTENSION));
$previewPdf = $ext === 'pdf' && !empty($r['file_pdf']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tinjau pendaftaran | Admin Kemenham</title>
  <link rel="stylesheet" href="admin-tinjau.css">
</head>
<body>
  <header class="top">
    <a class="brand" href="index.php"><i></i>Admin Kemenham</a>
    <nav>
      <a href="index.php">Dashboard</a>
      <span><?= $e($_SESSION['admin']) ?></span>
      <a class="logout" href="index.php?keluar=1">Keluar</a>
    </nav>
  </header>
  <main class="wrap">
    <a class="back" href="index.php">← Kembali ke dashboard</a>
    <div class="page-title">
      <div>
        <p class="eyebrow">TINJAU PENDAFTARAN #<?= (int)$r['id'] ?></p>
        <h1><?= $e($r['nama_peserta']) ?></h1>
        <p class="muted"><?= $e($r['asal']) ?> · <?= $e($r['lomba']) ?></p>
      </div>
      <span class="badge"><?= $e($r['status']) ?></span>
    </div>

    <?php if (isset($_GET['tersimpan'])): ?>
      <p class="success" role="status">Status dan catatan berhasil disimpan, dan notifikasi telah dikirim kepada peserta.</p>
    <?php endif; ?>

    <section class="layout">
      <div class="main-column">
        <section class="card">
          <div class="section-heading">
            <div><p class="eyebrow">KARYA PESERTA</p><h2><?= $e($r['judul'] ?: 'Belum ada karya dikirim') ?></h2></div>
            <span class="muted">Dikirim <?= $e($tanggal($r['dikirim_pada'])) ?></span>
          </div>
          <?php if ($previewPdf): ?>
            <iframe class="preview" title="Pratinjau naskah PDF" src="<?= $e($uploads . rawurlencode($r['file_pdf']) . '&lihat=1') ?>"></iframe>
          <?php elseif (!empty($r['file_pdf'])): ?>
            <div class="preview-placeholder">
              <p>Pratinjau tidak tersedia untuk format berkas ini.</p>
              <a class="button" href="<?= $e($uploads . rawurlencode($r['file_pdf'])) ?>">Unduh naskah</a>
            </div>
          <?php else: ?>
            <div class="preview-placeholder"><p>Peserta belum mengirim naskah karya.</p></div>
          <?php endif; ?>
          <?php if ($previewPdf): ?>
            <a class="download-link" href="<?= $e($uploads . rawurlencode($r['file_pdf'])) ?>">Unduh naskah PDF</a>
          <?php endif; ?>
        </section>

        <section class="card">
          <p class="eyebrow">BERKAS PENDAFTARAN</p>
          <div class="files">
            <?php foreach ($berkas as $file): ?>
              <article class="file-card">
                <div class="file-icon"><?= $file['preview'] ? 'PDF / GAMBAR' : 'WORD' ?></div>
                <div class="file-info">
                  <h3><?= $e($file['label']) ?></h3>
                  <?php if ($file['nama']): ?>
                    <a href="<?= $e($uploads . rawurlencode($file['nama']) . ($file['preview'] ? '&lihat=1' : '')) ?>" target="_blank" rel="noopener">Buka berkas ↗</a>
                  <?php else: ?>
                    <span class="muted">Berkas belum tersedia</span>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      </div>

      <aside class="side-column">
        <section class="card">
          <p class="eyebrow">INFORMASI PESERTA</p>
          <dl class="details">
            <div><dt>Nama peserta</dt><dd><?= $e($r['nama_peserta']) ?></dd></div>
            <div><dt>Jenis peserta</dt><dd><?= $e($r['tipe_tim']) ?></dd></div>
            <?php if ($r['tipe_tim'] === 'Kelompok'): ?>
              <div><dt>Anggota 2</dt><dd><?= $e($r['anggota2'] ?: '-') ?></dd></div>
              <div><dt>Anggota 3</dt><dd><?= $e($r['anggota3'] ?: '-') ?></dd></div>
            <?php endif; ?>
            <div><dt>Asal sekolah / instansi</dt><dd><?= $e($r['asal']) ?></dd></div>
            <div><dt>Nomor induk</dt><dd><?= $e($r['nomor_induk']) ?></dd></div>
            <div><dt>Email</dt><dd><a href="mailto:<?= $e($r['email']) ?>"><?= $e($r['email']) ?></a></dd></div>
            <div><dt>WhatsApp</dt><dd><?= $e($r['whatsapp']) ?></dd></div>
            <div><dt>Tanggal daftar</dt><dd><?= $e($tanggal($r['dibuat_pada'])) ?></dd></div>
          </dl>
        </section>

        <section class="card review-card">
          <p class="eyebrow">HASIL PENINJAUAN</p>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">
            <label for="status">Status pendaftaran</label>
            <select id="status" name="status" required>
              <?php foreach ($STATUS as $status): ?>
                <option value="<?= $e($status) ?>"<?= $r['status'] === $status ? ' selected' : '' ?>><?= $e($status) ?></option>
              <?php endforeach; ?>
            </select>
            <label for="catatan">Catatan untuk peserta <small>(maksimal 255 karakter)</small></label>
            <textarea id="catatan" name="catatan" rows="5" maxlength="255" placeholder="Tuliskan masukan atau alasan revisi..."><?= $e($r['catatan_admin'] ?? '') ?></textarea>
            <button class="button" type="submit">Simpan hasil tinjauan</button>
            <p class="hint">Peserta akan menerima notifikasi setelah status disimpan. Status “Perlu revisi” membuka kembali pengiriman karya.</p>
          </form>
        </section>
      </aside>
    </section>
  </main>
</body>
</html>
