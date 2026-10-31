-- Healix v2 upgrade. Run ONCE in phpMyAdmin on an existing Healix database (skip if you imported the new schema.sql).
SET NAMES utf8mb4;
ALTER TABLE users
  ADD COLUMN gender ENUM('Male','Female','Other') NULL,
  ADD COLUMN activity_level VARCHAR(20) NOT NULL DEFAULT 'Moderate',
  ADD COLUMN city VARCHAR(80) NULL,
  ADD COLUMN bio VARCHAR(200) NULL,
  ADD COLUMN blood_group VARCHAR(5) NULL,
  ADD COLUMN medical_notes VARCHAR(300) NULL,
  ADD COLUMN step_goal INT UNSIGNED NOT NULL DEFAULT 10000,
  ADD COLUMN water_goal_ml INT UNSIGNED NOT NULL DEFAULT 2500;
ALTER TABLE posts
  MODIFY post_type ENUM('Workout','MentalHealth','Nutrition','Yoga','Progress') NOT NULL DEFAULT 'Workout',
  ADD COLUMN image_path VARCHAR(255) NULL AFTER post_type;

CREATE TABLE IF NOT EXISTS comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  content VARCHAR(300) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_comment_post (post_id, created_at),
  CONSTRAINT fk_comment_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS follows (
  follower_id INT UNSIGNED NOT NULL,
  following_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (follower_id, following_id),
  CONSTRAINT fk_follow_a FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_follow_b FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_saves (
  user_id INT UNSIGNED NOT NULL,
  post_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, post_id),
  CONSTRAINT fk_save_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_save_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO doctors (name, specialty, qualification, experience_yrs, rating, fee_inr, avatar_url, available_days) VALUES
('Dr. Ishita Verma','Dermatologist','MD (Dermatology)',10,4.8,800,'https://ui-avatars.com/api/?name=Ishita+Verma&background=2E6B56&color=fff&size=128&bold=true','Mon,Wed,Fri'),
('Dr. Arjun Malhotra','Dermatologist','MBBS, DDVL',7,4.6,650,'https://ui-avatars.com/api/?name=Arjun+Malhotra&background=1F4E4A&color=fff&size=128&bold=true','Tue,Thu,Sat'),
('Dr. Sunita Reddy','Endocrinologist','MD, DM (Endocrinology)',16,4.9,1400,'https://ui-avatars.com/api/?name=Sunita+Reddy&background=E07A5F&color=fff&size=128&bold=true','Mon,Thu'),
('Dr. Faisal Khan','Endocrinologist','MD (Diabetes & Metabolism)',8,4.7,1100,'https://ui-avatars.com/api/?name=Faisal+Khan&background=7C3AED&color=fff&size=128&bold=true','Wed,Fri,Sat'),
('Dr. Pooja Sharma','Sports Medicine','MD (Sports Medicine)',9,4.8,900,'https://ui-avatars.com/api/?name=Pooja+Sharma&background=0EA5E9&color=fff&size=128&bold=true','Mon,Tue,Fri'),
('Dr. Dev Chopra','Sports Medicine','MBBS, Dip. SEM',6,4.6,750,'https://ui-avatars.com/api/?name=Dev+Chopra&background=DB2777&color=fff&size=128&bold=true','Tue,Thu,Sat'),
('Dr. Lakshmi Pillai','Nutritionist','MSc Dietetics, RD',12,4.9,650,'https://ui-avatars.com/api/?name=Lakshmi+Pillai&background=2E6B56&color=fff&size=128&bold=true','Mon,Tue,Wed,Thu,Fri'),
('Dr. Manish Gupta','General Physician','MBBS, MD (Medicine)',18,4.8,600,'https://ui-avatars.com/api/?name=Manish+Gupta&background=1F4E4A&color=fff&size=128&bold=true','Mon,Tue,Wed,Thu,Fri,Sat'),
('Dr. Tanvi Kapoor','Mental Health Therapist','MA Counselling Psychology',6,4.7,800,'https://ui-avatars.com/api/?name=Tanvi+Kapoor&background=E07A5F&color=fff&size=128&bold=true','Tue,Wed,Fri,Sat'),
('Dr. Harsh Vora','Physiotherapist','MPT (Neurology)',11,4.8,750,'https://ui-avatars.com/api/?name=Harsh+Vora&background=7C3AED&color=fff&size=128&bold=true','Mon,Tue,Thu,Fri'),
('Dr. Rina Das','Cardiologist','MD (Cardiology)',12,4.8,1100,'https://ui-avatars.com/api/?name=Rina+Das&background=0EA5E9&color=fff&size=128&bold=true','Tue,Wed,Sat'),
('Dr. Shreya Bhatia','Yoga Therapist','BNYS, MSc Yoga',12,4.9,500,'https://ui-avatars.com/api/?name=Shreya+Bhatia&background=DB2777&color=fff&size=128&bold=true','Mon,Tue,Wed,Thu,Fri,Sat');

INSERT IGNORE INTO users (name, email, password_hash, primary_goal, tier_id, bio, city) VALUES
('Aarav Sharma','aarav@healix.invalid','!','Build Muscle','Free','Lifting, rice & repeat 💪','Mumbai'),
('Meera Iyer','meera@healix.invalid','!','Reduce Stress','Free','Yoga teacher. Chai enthusiast 🍵','Bengaluru'),
('Rohan Das','rohan@healix.invalid','!','Lose Weight','Free','Down 8 kg and counting 🏃','Kolkata');
INSERT INTO posts (user_id, content, post_type, likes_count, created_at) VALUES
((SELECT id FROM users WHERE email='aarav@healix.invalid'),'Hit a new deadlift PR today — 120 kg! Consistency beats motivation every time 💪','Workout',34,NOW() - INTERVAL 7 HOUR),
((SELECT id FROM users WHERE email='meera@healix.invalid'),'Ten minutes of slow breathing before work changed my entire morning. Try it tomorrow 🌅','MentalHealth',29,NOW() - INTERVAL 9 HOUR),
((SELECT id FROM users WHERE email='rohan@healix.invalid'),'Day 30: walked 10k steps every single day this month. Scale says -3 kg 🎉','Progress',41,NOW() - INTERVAL 1 DAY),
((SELECT id FROM users WHERE email='meera@healix.invalid'),'Sun salutations at sunrise ☀️ Who else is starting the day on the mat?','Yoga',22,NOW() - INTERVAL 30 HOUR),
((SELECT id FROM users WHERE email='aarav@healix.invalid'),'Post-workout bowl: paneer tikka, quinoa, cucumber and a squeeze of lime 🥗','Nutrition',18,NOW() - INTERVAL 2 DAY),
((SELECT id FROM users WHERE email='rohan@healix.invalid'),'Swapped evening snacks for fruit + nuts. Cravings gone in a week!','Nutrition',15,NOW() - INTERVAL 3 DAY);
