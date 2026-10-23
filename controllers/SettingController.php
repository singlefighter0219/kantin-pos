<?php
require_once __DIR__ . '/../config/database.php';

class SettingController {
    private $pdo;

    public function __construct($pdo_conn) {
        $this->pdo = $pdo_conn;
    }

    public function getAllSettings() {
        try {
            $stmt = $this->pdo->query("SELECT param_key, param_value FROM pengaturan");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['param_key']] = $row['param_value'];
            }
            return $settings;
        } catch (PDOException $e) {
            return [];
        }
    }

    // Update data berdasarkan tipe form yang dikirim
    public function updateSettings($formType, $postData, $fileData) {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("UPDATE pengaturan SET param_value = ? WHERE param_key = ?");

            if ($formType === 'form_umum') {
                $keys = ['nama_aplikasi', 'tagline', 'nama_pemilik', 'alamat_lengkap', 'no_telepon'];
                foreach ($keys as $key) {
                    $val = trim($postData[$key] ?? '');
                    $stmt->execute([$val, $key]);
                }
                $msg = 'Informasi umum usaha berhasil diperbarui!';
            } 
            elseif ($formType === 'form_nota') {
                $keys = ['prefix_penjualan', 'prefix_konsinyasi', 'prefix_retur', 'pesan_nota'];
                foreach ($keys as $key) {
                    $val = trim($postData[$key] ?? '');
                    $stmt->execute([$val, $key]);
                }
                $msg = 'Pengaturan nota & struk berhasil diperbarui!';
            } 
            elseif ($formType === 'form_favico') {
                if (isset($fileData['favico']) && $fileData['favico']['error'] === UPLOAD_ERR_OK) {
                    $favName = $this->uploadFile($fileData['favico'], ['ico', 'png', 'jpg', 'jpeg'], 'fav_');
                    if ($favName) {
                        $stmt->execute([$favName, 'favico']);
                    }
                }
                $msg = 'Favicon berhasil diperbarui!';
            } 
            elseif ($formType === 'form_logo') {
                if (isset($fileData['logo']) && $fileData['logo']['error'] === UPLOAD_ERR_OK) {
                    $logoName = $this->uploadFile($fileData['logo'], ['png', 'jpg', 'jpeg'], 'logo_');
                    if ($logoName) {
                        $stmt->execute([$logoName, 'logo']);
                    }
                }
                $msg = 'Logo toko berhasil diperbarui!';
            } else {
                throw new Exception('Aksi form tidak valid.');
            }

            $this->pdo->commit();
            return ['status' => true, 'message' => $msg];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['status' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()];
        }
    }

    // Fungsi untuk menghapus gambar (Favicon / Logo)
    public function removeImage($paramKey) {
        try {
            // Ambil nama file lama
            $stmt = $this->pdo->prepare("SELECT param_value FROM pengaturan WHERE param_key = ?");
            $stmt->execute([$paramKey]);
            $fileName = $stmt->fetchColumn();

            if ($fileName) {
                $filePath = __DIR__ . '/../assets/uploads/' . $fileName;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            // Kosongkan di database
            $update = $this->pdo->prepare("UPDATE pengaturan SET param_value = '' WHERE param_key = ?");
            $update->execute([$paramKey]);

            return ['status' => true, 'message' => 'Gambar berhasil dihapus!'];
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Gagal menghapus gambar: ' . $e->getMessage()];
        }
    }

    private function uploadFile($file, $allowedExtensions, $prefix) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            throw new Exception("Format file tidak didukung!");
        }

        $uploadDir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = $prefix . time() . '.' . $ext;
        $destination = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $fileName;
        }
        throw new Exception("Gagal mengunggah file ke server.");
    }
}