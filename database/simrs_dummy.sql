USE simrs_dummy;

CREATE TABLE pasien (
  no_rm VARCHAR(30) PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  tgl_lahir DATE NOT NULL,
  no_hp VARCHAR(20) NULL
);

CREATE TABLE diagnosis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_rm VARCHAR(30) NOT NULL,
  kode_icd VARCHAR(10) NOT NULL,
  tgl_diagnosis DATE NOT NULL
);

CREATE TABLE resep_obat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_rm VARCHAR(30) NOT NULL,
  nama_obat VARCHAR(150) NOT NULL,
  dosis VARCHAR(150) NOT NULL,
  tgl_resep DATE NOT NULL
);

INSERT INTO pasien (no_rm, nama, tgl_lahir, no_hp) VALUES
 ('000101', 'Sutrisno', '1960-03-12', '081200000001'),
 ('000102', 'Siti Aminah', '1975-08-25', '081200000002'),
 ('000103', 'Pasien Bukan TBC', '1980-01-01', '081200000003');

INSERT INTO diagnosis (no_rm, kode_icd, tgl_diagnosis) VALUES
 ('000101', 'A15.0', '2026-09-01'),
 ('000102', 'A16.2', '2026-09-10'),
 ('000103', 'J06.9', '2026-09-12');

INSERT INTO resep_obat (no_rm, nama_obat, dosis, tgl_resep) VALUES
 ('000101', 'OAT KDT Fase Intensif', '3 tablet 1x sehari pagi', '2026-09-01'),
 ('000102', 'OAT KDT Fase Intensif', '2 tablet 1x sehari pagi', '2026-09-10');