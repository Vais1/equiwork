-- database/02_dummy_data.sql
-- Generates realistic dummy data formatted for Malaysia (MYR, local states)
-- to thoroughly test the Accommodation Matching Engine.
SET time_zone = "+08:00";

-- 1. Insert Dummy Employers
-- The password hash uses a standard BCRYPT hash for 'password123'
-- This ensures you can actually log in using these accounts to test the dashboard.
INSERT IGNORE INTO `users` (`user_id`, `username`, `email`, `password_hash`, `role_type`, `created_at`) VALUES
(1001, 'techcorp_my', 'hr@techcorp.com.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Employer', NOW()),
(1002, 'inclusivityInc', 'careers@inclusivity.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Employer', NOW()),
(1003, 'kl_innovators', 'talent@klinnovators.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Employer', NOW());

-- 2. Insert Core Accessibility Accommodations
INSERT IGNORE INTO `accommodations` (`accommodation_id`, `name`, `category`) VALUES
(1, '100% Remote', 'Work Arrangement'),
(2, 'Flexible Hours', 'Work Arrangement'),
(3, 'Screen Reader Compatible', 'Assistive Technology'),
(4, 'Voice Control Ready', 'Assistive Technology'),
(5, 'Async-first Workplace', 'Communication'),
(6, 'No Video Required', 'Communication'),
(7, 'Custom Hardware Provided', 'Physical'),
(8, 'Wheelchair Accessible Office', 'Physical');

-- 3. Insert Realistic Regional Job Postings (10 Records)
INSERT IGNORE INTO `jobs` (`job_id`, `employer_id`, `title`, `company_name`, `description`, `location_type`, `employment_type`, `salary_min_myr`, `salary_max_myr`, `state_region`, `status`, `posted_at`) VALUES
(101, 1001, 'Frontend Web Developer', 'TechCorp Malaysia', 'Join our diverse team to build accessible digital products! We fully support screen readers and adaptive tech in our development environment. Enjoy an async-first culture.', 'Remote', 'Full-time', 4500.00, 7500.00, 'Kuala Lumpur', 'Active', NOW()),
(102, 1001, 'Data Entry Specialist', 'TechCorp Malaysia', 'Seeking an attention-oriented data specialist. All workflows are async, requiring no video calls. We provide specialized ergonomic keyboards if needed.', 'Remote', 'Part-time', 2000.00, 3500.00, 'Selangor', 'Active', NOW()),
(103, 1002, 'Senior Digital Product Designer', 'Inclusivity Inc', 'Design beautiful interfaces that work for everyone. You will occasionally meet the team at our fully wheelchair accessible KL office.', 'Hybrid', 'Full-time', 6000.00, 9500.00, 'Kuala Lumpur', 'Active', NOW()),
(104, 1003, 'Customer Support Representative', 'KL Innovators', 'Help our clients succeed! Role requires presence in our fully modified, wheelchair friendly branch. We provide any needed physical accommodations.', 'On-site', 'Full-time', 2500.00, 4000.00, 'Penang', 'Active', NOW()),
(105, 1002, 'Content Writer & Editor', 'Inclusivity Inc', 'We need creative minds to write engaging articles. We focus on low-stress communication, completely async with zero mandatory meetings.', 'Remote', 'Freelance', 3000.00, 5000.00, 'Johor', 'Active', NOW()),
(106, 1003, 'Backend PHP Engineer', 'KL Innovators', 'Help us build robust APIs in an inclusive environment. Fully remote role with completely flexible hours.', 'Remote', 'Full-time', 5500.00, 8500.00, 'Selangor', 'Active', NOW()),
(107, 1001, 'SEO Growth Specialist', 'TechCorp Malaysia', 'Optimize our marketing funnel. Role involves occasional on-site brainstorming sessions, in an entirely accessible office layout.', 'Hybrid', 'Contract', 4000.00, 6000.00, 'Perak', 'Active', NOW()),
(108, 1002, 'UX Accessibility Analyst', 'Inclusivity Inc', 'Ensure digital products meet WCAG standards. This role provides specialized software and custom hardware completely free to you.', 'Remote', 'Full-time', 7000.00, 10000.00, 'Sabah', 'Active', NOW()),
(109, 1003, 'HR & Culture Administrator', 'KL Innovators', 'Manage employee relations at our flagship branch. Our facilities are updated with modern ramps and adjusted elevation desks.', 'On-site', 'Full-time', 3500.00, 5500.00, 'Sarawak', 'Active', NOW()),
(110, 1001, 'Social Media Manager', 'TechCorp Malaysia', 'Drive our community engagement. Strictly no video calls, communicating purely via Slack and project management boards.', 'Remote', 'Part-time', 2500.00, 4500.00, 'Pahang', 'Active', NOW());

-- 4. Map the Intersection Table (Guaranteeing Referential Integrity)
INSERT IGNORE INTO `job_accommodations` (`job_id`, `accommodation_id`) VALUES
(101, 1), (101, 3), (101, 5), (101, 7),  -- Frontend Developer (Remote, Screen Reader, Async, Hardware)
(102, 1), (102, 5), (102, 6), (102, 7),  -- Data Entry (Remote, Async, No Video, Hardware)
(103, 2), (103, 8),                      -- Senior Designer (Flexible Hours, Wheelchair)
(104, 7), (104, 8),                      -- CS Rep (Hardware, Wheelchair)
(105, 1), (105, 2), (105, 5), (105, 6),  -- Content Writer (Remote, Flexible, Async, No Video)
(106, 1), (106, 2),                      -- Backend Engineer (Remote, Flexible)
(107, 2), (107, 8),                      -- SEO Specialist (Flexible, Wheelchair)
(108, 1), (108, 3), (108, 4), (108, 7),  -- UX Analyst (Remote, Screen Reader, Voice Control, Hardware)
(109, 8),                                -- HR Admin (Wheelchair Accessible)
(110, 1), (110, 2), (110, 6);            -- Social Media (Remote, Flexible, No Video)
