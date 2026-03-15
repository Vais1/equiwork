-- database/01_jobs_module_enhancement.sql
-- Migration: Enhance Jobs table for Malaysian localization & comprehensive employment details
-- Timezone set to MYT (Malaysia Time)
SET time_zone = "+08:00";

-- 1. Enhance the `jobs` table with new columns
ALTER TABLE `jobs`
  ADD COLUMN `company_name` varchar(255) NULL AFTER `title`,
  ADD COLUMN `employment_type` enum('Full-time', 'Part-time', 'Contract', 'Freelance') NOT NULL DEFAULT 'Full-time' AFTER `location_type`,
  ADD COLUMN `salary_min_myr` decimal(10,2) NULL COMMENT 'Minimum salary in MYR' AFTER `employment_type`,
  ADD COLUMN `salary_max_myr` decimal(10,2) NULL COMMENT 'Maximum salary in MYR' AFTER `salary_min_myr`,
  ADD COLUMN `state_region` varchar(100) NULL COMMENT 'Malaysian state or region (e.g., Kuala Lumpur, Selangor)' AFTER `salary_max_myr`;

-- 2. Add optimal indexing for fast search filtering and localization
-- Indexing employment_type and state_region for fast faceted search
ALTER TABLE `jobs`
  ADD INDEX `idx_employment_type` (`employment_type`),
  ADD INDEX `idx_state_region` (`state_region`),
  ADD INDEX `idx_salary_range` (`salary_min_myr`, `salary_max_myr`);

-- Note: job_accommodations already has PRIMARY KEY (job_id, accommodation_id) and KEY idx_accommodation_id (accommodation_id).
-- We can add a composite index on jobs table to support the Accommodation Matching Engine's primary JOIN queries: 
-- (status, location_type) composite index to speed up the main active job board query before joining accommodations
ALTER TABLE `jobs`
  ADD INDEX `idx_active_locations` (`status`, `location_type`);
