CREATE DATABASE nurse_portal;
USE nurse_portal;

-- Users (both clients and nurses)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(200),
    role ENUM('Client','Nurse','Admin') NOT NULL DEFAULT 'Client',
    approval_status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    skills VARCHAR(255),
    service VARCHAR(100),
    salary DECIMAL(10,2),
    experience INT,
    location VARCHAR(100),
    bio TEXT,
    certificate_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    nurse_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (nurse_id) REFERENCES users(id)
);

CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nurse_id INT NOT NULL,
    client_id INT NOT NULL,
    rating ENUM('1-Poor','2-Fair','3-Good','4-Very Good','5-Excellent') NOT NULL,
    review TEXT,
    is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nurse_id) REFERENCES users(id),
    FOREIGN KEY (client_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS client_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  service_type ENUM('Babysitting','ElderlyCare','PatientCare') NOT NULL,
  patient_name VARCHAR(150) NOT NULL,
  age VARCHAR(20) DEFAULT NULL,
  address VARCHAR(255),
  phone VARCHAR(20),
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
);

USE nurse_portal;

-- Admin Table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin
INSERT INTO admin (username, email, password)
VALUES ('admin', 'admin@gmail.com', MD5('admin123'));

CREATE TABLE client_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    age VARCHAR(10),
    address TEXT,
    phone VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS notifications (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
title VARCHAR(255) NOT NULL,
message TEXT NOT NULL,
type ENUM('booking','request','system','payment') DEFAULT 'system',
is_read BOOLEAN DEFAULT FALSE,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
INDEX idx_user_read (user_id, is_read),
INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

t (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add columns only if missing (MariaDB & MySQL 8.0.29+ support IF NOT EXISTS)
ALTER TABLE users ADD COLUMN IF NOT EXISTS average_rating DECIMAL(3,2) NOT NULL DEFAULT 0.00;
ALTER TABLE users ADD COLUMN IF NOT EXISTS review_count INT NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  base_price DECIMAL(10,2) DEFAULT 0.00,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO services (name, slug, base_price, active)
VALUES ('Lab Testing','LabTesting',0,1);
INSERT IGNORE INTO services (name, slug, base_price, active)
VALUES ('Elderly Care','ElderlyCare',0,1);
INSERT IGNORE INTO services (name, slug, base_price, active)
VALUES ('Patient Care','PatientCare',0,1);
INSERT IGNORE INTO services (name, slug, base_price, active)
VALUES ('Babysitting','Babysitting',0,1);

CREATE TABLE IF NOT EXISTS service_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  service_slug VARCHAR(100) NOT NULL,
  name VARCHAR(150) NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_service_item (service_slug, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO service_items (service_slug, name, price, active) VALUES
('LabTesting','Adenosine Deaminase (ADA), Serum',300,1),
('LabTesting','Alkaline Phosphatase (ALP), Serum',150,1),
('LabTesting','Albumin, Serum',150,1),
('LabTesting','Amylase, Serum',250,1),
('LabTesting','Bilirubin Total, Serum',200,1),
('LabTesting','Bilirubin Direct & Indirect, Serum',350,1),
('LabTesting','Bilirubin Direct, Serum',200,1),
('LabTesting','Blood Urea NitCREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewer
        FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewee
        FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT uq_review UNIQUE (booking_id, reviewer_id),
    INDEX idx_reviewee (reviewee_id),
    INDEX idx_reviewer (reviewer_id),
    INDEX idx_booking (booking_id),
    INDEX idx_created_arogen (BUN), Serum',200,1),
('LabTesting','Calcium, Serum',200,1),
('LabTesting','Cholesterol Total, Serum',150,1),
('LabTesting','Cholesterol HDL, Serum',250,1),
('LabTesting','Cholesterol LDL, Serum',300,1),
('LabTesting','Cholesterol VLDL, Serum',200,1),
('LabTesting','Chloride, Serum',300,1),
('LabTesting','CK-MB, Serum',250,1),
('LabTesting','Creatinine, Serum',200,1),
('LabTesting','Serum Electrolytes',550,1),
('LabTesting','Glucose Fasting, Serum',70,1),
('LabTesting','Glucose Random, Serum',70,1),
('LabTesting','Glucose Post-Prandial, Serum',70,1),
('LabTesting','Gamma-Glutamyl Transferase (GGT), Serum',250,1),
('LabTesting','Glucose Tolerance Test (GTT), Serum',850,1),
('LabTesting','Ionised Calcium',300,1),
('LabTesting','Total Iron, Serum',350,1),
('LabTesting','Total Iron Binding Capacity (TIBC), Serum',350,1),
('LabTesting','TIBC & UIBC, Serum',500,1),
('LabTesting','Lipid Profile, Serum',850,1),
('LabTesting','Iron Profile (Fe, TIBC, UIBC), Serum',650,1),
('LabTesting','Triglycerides, Serum',200,1),
('LabTesting','LDH, Serum',250,1),
('LabTesting','Lipase, Serum',500,1),
('LabTesting','Liver Function Test (LFT), Serum',850,1),
('LabTesting','Magnesium',300,1),
('LabTesting','Microprotein / Albumin (Urine)',250,1),
('LabTesting','Potassium, Serum',300,1),
('LabTesting','Protein Total, Serum / Fluid',150,1),
('LabTesting','Phosphorus, Serum',300,1),
('LabTesting','Kidney Function Test (KFT / RFT), Serum',400,1),
('LabTesting','SGOT (AST), Serum',200,1),
('LabTesting','SGPT (ALT), Serum',200,1),
('LabTesting','Sodium, Serum',300,1),
('LabTesting','Urea, Serum',200,1),
('LabTesting','Uric Acid, Serum',200,1),
('LabTesting','Urine 24 Hr Protein / Albumin / Creatinine',300,1),
('LabTesting','Urine Albumin Creatinine Ratio (ACR)',400,1),
('LabTesting','Bence Jones Protein, Urine',200,1),
('LabTesting','Bile Salt Pigment, Urine',50,1),
('LabTesting','Body Fluid Analysis',400,1),
('LabTesting','Body Fluid AFB / Gram Stain (each)',200,1),
('LabTesting','Body Fluid ADA',300,1),
('LabTesting','Cytology',500,1),
('LabTesting','CSF Analysis',500,1),
('LabTesting','CSF India Ink',300,1),
('LabTesting','FNAC',750,1),
('LabTesting','FNAC Without Procedure',600,1),
('LabTesting','Hanging Drop, Stool',100,1),
('LabTesting','India Ink',300,1),
('LabTesting','Ketone Bodies, Urine',70,1),
('LabTesting','Nasal Smear for Eosinophils',250,1),
('LabTesting','Pap Smear (Conventional)',350,1),
('LabTesting','Pap Smear (LBC)',900,1),
('LabTesting','Pregnancy Test',100,1),
('LabTesting','Semen Analysis',1500,1),
('LabTesting','Stool Benedict''s Test',300,1),
('LabTesting','Stool Routine',150,1),
('LabTesting','Stool Occult Blood',100,1),
('LabTesting','Urine Routine',100,1),
('LabTesting','Urine Microalbumin',250,1),
('LabTesting','Urethral Smear Gram Stain',250,1),
('LabTesting','Absolute Lymphocyte Count',70,1),
('LabTesting','Absolute Neutrophil Count',70,1),
('LabTesting','APTT',300,1),
('LabTesting','BT / CT',70,1),
('LabTesting','Blood Group & Rh',70,1),
('LabTesting','Bone Marrow Examination',1200,1),
('LabTesting','CBC (Complete Blood Count)',350,1),
('LabTesting','Coagulation Profile',550,1),
('LabTesting','D-Dimer',950,1),
('LabTesting','Differential Count (DLC)',70,1),
('LabTesting','ESR',70,1),
('LabTesting','HbA1c',600,1),
('LabTesting','Hemoglobin (Hb)',70,1),
('LabTesting','INR',300,1),
('LabTesting','I:T Ratio',150,1),
('LabTesting','MP Blood Smear',100,1),
('LabTesting','LE Cell',300,1),
('LabTesting','MCH',70,1),
('LabTesting','MCV',70,1),
('LabTesting','MCHC',70,1),
('LabTesting','PCV',70,1),
('LabTesting','Platelet Count',70,1),
('LabTesting','Peripheral Blood Smear',350,1),
('LabTesting','PT/INR',300,1),
('LabTesting','RBC Count',100,1),
('LabTesting','Reticulocyte Count',70,1),
('LabTesting','Sepsis Screen',650,1),
('LabTesting','Small Biopsy (1 vial)',1000,1),
('LabTesting','Small Biopsy (2 vials)',1600,1),
('LabTesting','Small Biopsy (3 vials)',2400,1),
('LabTesting','Skin Biopsy',1800,1),
('LabTesting','Medium Biopsy',1800,1),
('LabTesting','Large Biopsy',2800,1),
('LabTesting','Cancer Specimen',4000,1),
('LabTesting','ASO',300,1),
('LabTesting','CRP',300,1),
('LabTesting','CRP Quantitative',650,1),
('LabTesting','HAV Ab',350,1),
('LabTesting','HIV Ab',350,1),
('LabTesting','HBsAg',350,1),
('LabTesting','HCV Ab',350,1),
('LabTesting','HEV Ab',350,1),
('LabTesting','H. Pylori Antigen (Stool)',950,1),
('LabTesting','H. Pylori Antigen (Serum)',350,1),
('LabTesting','Leptospira IgM',600,1),
('LabTesting','MP ICT',250,1),
('LabTesting','Rheumatoid Factor (RA)',300,1),
('LabTesting','Scrub Typhus (ICT)',500,1),
('LabTesting','Scrub Typhus (Weil-Felix)',500,1),
('LabTesting','Syphicheck',200,1),
('LabTesting','Widal Test',200,1),
('LabTesting','VDRL',200,1),
('LabTesting','Typhicheck IgM/IgG',400,1),
('LabTesting','Triple Test (HBsAg, HCV, HIV)',600,1),
('LabTesting','Troponin I',750,1),
('LabTesting','Cardiac Troponin (cTn-I)',850,1),
('LabTesting','Dengue',700,1),
('LabTesting','NT-Pro BNP',1400,1),
('LabTesting','AFB / ZN Stain',200,1),
('LabTesting','Blood Culture',1250,1),
('LabTesting','Culture & Sensitivity',800,1),
('LabTesting','Fungal Stain',500,1),
('LabTesting','Gram Stain',200,1),
('LabTesting','Mantoux Test',150,1),
('LabTesting','Anti CCP',1800,1),
('LabTesting','Beta hCG',900,1),
('LabTesting','CA-125',1350,1),
('LabTesting','CEA',850,1),
('LabTesting','Ferritin',700,1),
('LabTesting','FSH',600,1),
('LabTesting','FSH / LH / Prolactin Panel',1800,1),
('LabTesting','Folic Acid',1400,1),
('LabTesting','IgE',800,1),
('LabTesting','Prolactin',600,1),
('LabTesting','Procalcitonin',1500,1),
('LabTesting','PSA (Free)',700,1),
('LabTesting','PSA (Total)',700,1),
('LabTesting','PSA Ratio Panel',1400,1),
('LabTesting','TFT (T3, T4, TSH)',700,1),
('LabTesting','T3',350,1),
('LabTesting','T4',350,1),
('LabTesting','TSH',350,1),
('LabTesting','FT3',400,1),
('LabTesting','FT4',400,1),
('LabTesting','Vitamin B12',1100,1),
('LabTesting','Vitamin D',1500,1),
('LabTesting','Alpha-Feto Protein',850,1),
('LabTesting','ECG',400,1),
('LabTesting','HPV',2000,1),
('LabTesting','HPV Duo (PAP + PCR)',2800,1),
('LabTesting','IgG',1150,1),
('LabTesting','AMH',2500,1),
('LabTesting','Mycoreal (PCR)',2450,1),
('LabTesting','Xpert MTB / RIF Ultra (Extra-Pulmonary)',2700,1),
('LabTesting','ANA by IFA',1600,1),
('LabTesting','ANA Profile (IFA + ENA Blot)',4700,1),
('LabTesting','HSV 1 & 2 IgG',4500,1),
('LabTesting','Vitamin D 1,25',4500,1),
('LabTesting','CA 19.9',1800,1),
('LabTesting','ENA Panel',4500,1),
('LabTesting','Cortisol',1000,1),
('LabTesting','TORCH Panel',3800,1),
('LabTesting','PTH Intact',2400,1),
('LabTesting','C-Peptide',1600,1),
('LabTesting','Insulin (any type)',1250,1),
('LabTesting','Lithium',900,1),
('LabTesting','Anti-Thyroglobulin Ab',2100,1),
('LabTesting','Anti-TPO Ab',2050,1),
('LabTesting','ER/PR HER2 (Tissue)',3300,1),
('LabTesting','Total Testosterone',1050,1),
('LabTesting','Progesterone',1050,1),
('LabTesting','Direct Coombs',950,1),
('LabTesting','Indirect Coombs',100,1),
('LabTesting','Growth Hormone',1200,1),
('LabTesting','Couple Karyotyping',5800,1),
('LabTesting','HB Electrophoresis',1400,1),
('LabTesting','Fecal Calprotectin',3400,1),
('LabTesting','APLA (Total)',5400,1);

