<?php
/**
 * Notification - In-system and email notifications
 */

namespace App\Core;

use App\Core\Database;

class Notification
{
    /**
     * Create a system notification (and optionally send email)
     */
    public static function create(
        ?int   $userId,
        string $type,
        string $titleTh,
        string $titleEn,
        string $messageTh,
        string $messageEn,
        ?int   $paperId   = null,
        string $channel   = 'both'
    ): int {
        $db  = Database::getInstance();
        $sql = "INSERT INTO notifications
                    (user_id, type, title_th, title_en, message_th, message_en, related_paper_id, channel)
                VALUES (:uid, :type, :tth, :ten, :mth, :men, :pid, :ch)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':uid'  => $userId,
            ':type' => $type,
            ':tth'  => $titleTh,
            ':ten'  => $titleEn,
            ':mth'  => $messageTh,
            ':men'  => $messageEn,
            ':pid'  => $paperId,
            ':ch'   => $channel,
        ]);
        return (int)$db->lastInsertId();
    }

    public static function getUnread(int $userId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :uid AND is_read = FALSE ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function getAll(int $userId, int $limit = 50, int $offset = 0): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT :lim OFFSET :off");
        $stmt->execute([':uid' => $userId, ':lim' => $limit, ':off' => $offset]);
        return $stmt->fetchAll();
    }

    public static function markRead(int $notifId, int $userId): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("UPDATE notifications SET is_read = TRUE WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $notifId, ':uid' => $userId]);
    }

    public static function markAllRead(int $userId): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
    }

    public static function countUnread(int $userId): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = FALSE");
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    // ── Convenience helpers ───────────────────────────────────

    public static function paperSubmitted(int $authorId, string $paperCode, string $titleEn): void
    {
        // Notify author
        self::create($authorId, 'paper_submitted',
            'ส่งบทคัดย่อเรียบร้อยแล้ว',
            'Paper Submitted',
            "บทคัดย่อ {$paperCode} ได้รับการส่งเรียบร้อยแล้ว",
            "Your paper {$paperCode} has been submitted successfully.",
            null, 'system'
        );
        // Notify all active admins
        self::notifyAdmins('paper_submitted',
            'มีบทคัดย่อใหม่เข้ามา',
            'New Paper Submitted',
            "บทคัดย่อใหม่ {$paperCode}: {$titleEn}",
            "New paper submitted: {$paperCode} — {$titleEn}"
        );
    }

    public static function reviewAssigned(int $reviewerId, string $paperCode, string $titleEn, int $paperId): void
    {
        self::create($reviewerId, 'review_assigned',
            'ได้รับมอบหมายบทคัดย่อใหม่',
            'New Paper Assigned',
            "คุณได้รับมอบหมายให้ตรวจสอบบทคัดย่อ {$paperCode}",
            "You have been assigned to review paper {$paperCode}: {$titleEn}",
            $paperId, 'both'
        );
    }

    public static function reviewResultAvailable(int $authorId, string $paperCode, string $decision, int $paperId): void
    {
        self::create($authorId, 'review_result',
            'ผลการพิจารณาพร้อมแล้ว',
            'Review Result Available',
            "บทคัดย่อ {$paperCode} มีผลการพิจารณาแล้ว: {$decision}",
            "Review result for paper {$paperCode}: {$decision}",
            $paperId, 'both'
        );
    }

    public static function paperAccepted(int $authorId, string $paperCode, int $paperId): void
    {
        self::create($authorId, 'accepted',
            'บทคัดย่อได้รับการยอมรับ',
            'Paper Accepted',
            "ยินดีด้วย! บทคัดย่อ {$paperCode} ได้รับการยอมรับ",
            "Congratulations! Your paper {$paperCode} has been accepted.",
            $paperId, 'both'
        );
    }

    public static function paperPublished(int $authorId, string $paperCode, int $paperId): void
    {
        self::create($authorId, 'published',
            'บทคัดย่อถูกเผยแพร่แล้ว',
            'Paper Published',
            "บทคัดย่อ {$paperCode} ถูกเผยแพร่ในระบบเรียบร้อยแล้ว",
            "Your paper {$paperCode} has been published in the repository.",
            $paperId, 'both'
        );
    }

    public static function revisionRequired(int $authorId, string $paperCode, int $paperId): void
    {
        self::create($authorId, 'revision_required',
            'ต้องการแก้ไขบทคัดย่อ',
            'Revision Required',
            "บทคัดย่อ {$paperCode} ต้องการการแก้ไข กรุณาตรวจสอบความเห็นของผู้ทรงคุณวุฒิ",
            "Paper {$paperCode} requires revision. Please review the comments.",
            $paperId, 'both'
        );
    }

    public static function underReview(int $authorId, string $paperCode, int $paperId): void
    {
        self::create($authorId, 'review_assigned',
            'บทคัดย่ออยู่ระหว่างพิจารณา',
            'Paper Under Review',
            "บทคัดย่อ {$paperCode} ได้รับการมอบหมายผู้ทรงคุณวุฒิครบแล้วและอยู่ระหว่างพิจารณา",
            "Paper {$paperCode} has been assigned to reviewers and is now under review.",
            $paperId, 'both'
        );
    }

    public static function paperUnpublished(int $authorId, string $paperCode, int $paperId): void
    {
        self::create($authorId, 'system',
            'ยกเลิกการเผยแพร่บทคัดย่อ',
            'Paper Unpublished',
            "บทคัดย่อ {$paperCode} ถูกยกเลิกการเผยแพร่ชั่วคราว กรุณาติดต่อผู้ดูแลระบบ",
            "Paper {$paperCode} has been temporarily unpublished. Please contact the administrator.",
            $paperId, 'system'
        );
    }

    /** Notify all admins (used when reviewer submits a review) */
    public static function notifyAdmins(
        string $type,
        string $titleTh,
        string $titleEn,
        string $messageTh,
        string $messageEn,
        ?int   $paperId = null
    ): void {
        try {
            $db    = Database::getInstance();
            $stmt  = $db->query("SELECT id FROM users WHERE role = 'admin' AND account_status = 'active'");
            $admins = $stmt->fetchAll();
            foreach ($admins as $admin) {
                self::create((int)$admin['id'], $type, $titleTh, $titleEn, $messageTh, $messageEn, $paperId, 'system');
            }
        } catch (\Throwable $e) {
            error_log('Notification::notifyAdmins error: ' . $e->getMessage());
        }
    }
}
