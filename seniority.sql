-- Add a seniority position to each officer (1 = most senior, shown at the top)
ALTER TABLE officers ADD COLUMN seniority_order INT NOT NULL DEFAULT 0;

-- Give existing officers a starting order (same as the order they were added in)
UPDATE officers SET seniority_order = id;
