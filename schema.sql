-- Healix schema (MySQL 5.7+/MariaDB). Import ONCE via phpMyAdmin into your InfinityFree database.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  height_cm SMALLINT UNSIGNED NULL,
  weight_kg DECIMAL(5,1) NULL,
  target_weight_kg DECIMAL(5,1) NULL,
  age TINYINT UNSIGNED NULL,
  primary_goal VARCHAR(60) NOT NULL DEFAULT 'General Wellness',
  tier_id ENUM('Free','Silver','Gold','Platinum') NOT NULL DEFAULT 'Free',
  sub_expiry DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_stats (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  steps INT UNSIGNED NOT NULL DEFAULT 0,
  water_ml INT UNSIGNED NOT NULL DEFAULT 0,
  active_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  health_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
  calories_burned INT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY uq_user_day (user_id, date),
  CONSTRAINT fk_stats_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS workouts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  workout_type VARCHAR(80) NOT NULL,
  duration_mins SMALLINT UNSIGNED NOT NULL,
  calories INT UNSIGNED NOT NULL DEFAULT 0,
  intensity ENUM('Low','Moderate','High') NOT NULL DEFAULT 'Moderate',
  created_at DATETIME NOT NULL,
  KEY idx_workout_user (user_id, created_at),
  CONSTRAINT fk_workout_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS meditation_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  session_title VARCHAR(100) NOT NULL,
  sound_track VARCHAR(60) NOT NULL DEFAULT 'None',
  duration_mins SMALLINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_med_user (user_id, created_at),
  CONSTRAINT fk_med_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS doctors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  specialty VARCHAR(60) NOT NULL,
  qualification VARCHAR(150) NOT NULL,
  experience_yrs TINYINT UNSIGNED NOT NULL,
  rating DECIMAL(2,1) NOT NULL DEFAULT 4.5,
  fee_inr INT UNSIGNED NOT NULL,
  avatar_url VARCHAR(255) NOT NULL,
  available_days VARCHAR(40) NOT NULL DEFAULT 'Mon,Tue,Wed,Thu,Fri'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS appointments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  doctor_id INT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  time_slot VARCHAR(12) NOT NULL,
  type ENUM('video','chat') NOT NULL DEFAULT 'video',
  status ENUM('upcoming','completed') NOT NULL DEFAULT 'upcoming',
  meeting_code VARCHAR(20) NOT NULL,
  KEY idx_appt_user (user_id, status),
  UNIQUE KEY uq_doc_slot (doctor_id, date, time_slot),
  CONSTRAINT fk_appt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_appt_doc FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  content VARCHAR(600) NOT NULL,
  post_type ENUM('Workout','MentalHealth','Nutrition') NOT NULL DEFAULT 'Workout',
  likes_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  KEY idx_posts_time (created_at),
  CONSTRAINT fk_post_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Extra helper table: one like per user per post (prevents duplicate likes)
CREATE TABLE IF NOT EXISTS post_likes (
  user_id INT UNSIGNED NOT NULL,
  post_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, post_id),
  CONSTRAINT fk_like_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_like_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  plan_tier ENUM('Silver','Gold','Platinum') NOT NULL,
  billing_cycle ENUM('3 Months','6 Months','12 Months') NOT NULL,
  amount_inr INT UNSIGNED NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  end_date DATE NOT NULL,
  KEY idx_sub_user (user_id),
  CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Seed data ----------
INSERT INTO doctors (name, specialty, qualification, experience_yrs, rating, fee_inr, avatar_url, available_days) VALUES
('Dr. Ananya Mehta','Cardiologist','MD, DM (Cardiology)',14,4.9,1200,'https://ui-avatars.com/api/?name=Ananya+Mehta&background=2E6B56&color=fff&size=128&bold=true','Mon,Wed,Fri'),
('Dr. Rohan Kulkarni','Cardiologist','MBBS, MD (Medicine)',9,4.7,900,'https://ui-avatars.com/api/?name=Rohan+Kulkarni&background=1F4E4A&color=fff&size=128&bold=true','Tue,Thu,Sat'),
('Dr. Priya Nair','Nutritionist','MSc Clinical Nutrition, RD',8,4.8,600,'https://ui-avatars.com/api/?name=Priya+Nair&background=E07A5F&color=fff&size=128&bold=true','Mon,Tue,Wed,Thu,Fri'),
('Dr. Meera Joshi','Nutritionist','PhD Dietetics, RD',11,4.6,700,'https://ui-avatars.com/api/?name=Meera+Joshi&background=2E6B56&color=fff&size=128&bold=true','Mon,Wed,Fri,Sat'),
('Dr. Aditi Rao','Mental Health Therapist','M.Phil Clinical Psychology',10,4.9,1000,'https://ui-avatars.com/api/?name=Aditi+Rao&background=1F4E4A&color=fff&size=128&bold=true','Mon,Wed,Thu,Sat'),
('Dr. Vikram Desai','Mental Health Therapist','MD Psychiatry',15,4.8,1500,'https://ui-avatars.com/api/?name=Vikram+Desai&background=E07A5F&color=fff&size=128&bold=true','Tue,Thu,Fri'),
('Dr. Sanjay Patel','General Physician','MBBS',12,4.7,500,'https://ui-avatars.com/api/?name=Sanjay+Patel&background=2E6B56&color=fff&size=128&bold=true','Mon,Tue,Wed,Thu,Fri,Sat'),
('Dr. Neha Bhatt','General Physician','MBBS, MD (Medicine)',7,4.6,450,'https://ui-avatars.com/api/?name=Neha+Bhatt&background=1F4E4A&color=fff&size=128&bold=true','Mon,Tue,Thu,Fri'),
('Dr. Rahul Iyer','Physiotherapist','MPT (Orthopaedics)',9,4.8,700,'https://ui-avatars.com/api/?name=Rahul+Iyer&background=E07A5F&color=fff&size=128&bold=true','Mon,Wed,Fri,Sat'),
('Dr. Kavya Menon','Physiotherapist','BPT, MPT (Sports)',6,4.7,650,'https://ui-avatars.com/api/?name=Kavya+Menon&background=2E6B56&color=fff&size=128&bold=true','Tue,Wed,Thu,Sat');

-- Seed "Healix Team" account (cannot log in: invalid hash) so the community feed is not empty
INSERT IGNORE INTO users (id, name, email, password_hash, primary_goal, tier_id) VALUES
(1,'Healix Team','team@healix.invalid','!','General Wellness','Platinum');

INSERT INTO posts (user_id, content, post_type, likes_count, created_at) VALUES
(1,'Welcome to the Healix community 🌿 Share a workout win, a calm moment, or a healthy meal today!','MentalHealth',12,NOW() - INTERVAL 3 HOUR),
(1,'Tip: a 10-minute walk after meals helps digestion and steadies energy. Who is in? 🚶','Workout',8,NOW() - INTERVAL 1 DAY),
(1,'Simple high-protein lunch: moong dal chilla + curd + cucumber salad 🥒','Nutrition',15,NOW() - INTERVAL 2 DAY);
