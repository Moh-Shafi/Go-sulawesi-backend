-- Cancellation policies table (per business)
CREATE TABLE IF NOT EXISTS cancellation_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    -- Hours before booking date; if cancelled within this window, refund_percent applies
    deadline_hours INT NOT NULL DEFAULT 72,
    -- Percentage refunded if cancelled before deadline
    refund_before_deadline INT NOT NULL DEFAULT 100,
    -- Percentage refunded if cancelled after deadline
    refund_after_deadline INT NOT NULL DEFAULT 0,
    -- Whether business must approve cancellation requests
    requires_approval TINYINT(1) DEFAULT 1,
    -- Additional notes shown to customer
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_business_policy (business_id)
);

-- Cancellation requests table
CREATE TABLE IF NOT EXISTS cancellation_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    -- Reason provided by tourist
    reason TEXT,
    -- Status: pending → approved / rejected / auto
    status ENUM('pending','approved','rejected','auto') DEFAULT 'pending',
    -- Calculated refund percentage at time of request
    refund_percent INT DEFAULT 0,
    -- Refund amount in IDR
    refund_amount DECIMAL(10,2) DEFAULT 0.00,
    -- Who handled the request
    handled_by INT DEFAULT NULL,
    -- Admin/business notes when handling
    handler_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    handled_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (handled_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Add refund fields to bookings table
ALTER TABLE bookings ADD COLUMN refund_amount DECIMAL(10,2) DEFAULT 0.00 AFTER total_price;
ALTER TABLE bookings ADD COLUMN cancelled_at TIMESTAMP NULL DEFAULT NULL AFTER status;
