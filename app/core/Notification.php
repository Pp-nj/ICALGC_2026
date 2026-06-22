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
        int    $userId,
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
                VALUES (:uid, :type, :tth, :ten, :mth, :men, :pid, :ch)
                RETURNING id";
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
        return (int)$stmt->fetchColumn();
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

    public static function paperSubmitted(int $authorId, int $adminId, string $paperCode, string $titleEn): void
    {
        // Notify author
        self::create($authorId, 'paper_submitted',
            'ส่งบทความเรียบร้อยแล้ว',
            'Paper Submitted',
            "บทความ {$paperCode} ได้รับการส่งเรียบร้อยแล้ว",
            "Your paper {$paperCode} has been submitted successfully.",
            null, 'system'
        );
        // Notify admin
        self::create($adminId, 'paper_submitted',
            'มีบทความใหม่เข้ามา',
            'New Paper Submitted',
            "บทความใหม่ {$paperCode}: {$titleEn}",
            "New paper submitted: {$paperCode} — {$titleEn}",
            null, 'system'
        );
    }

    public static function reviewAssigned(int $reviewerId, string $paperCode, string $titleEn, int $paperId): void
    {
        self::create($reviewerId, 'review_assigned',
            'ได้รับมอบหมายบทความใหม่',
            'New Paper Assigned',
            "คุณได้รับมอบหมายให้ตรวจสอบบทความ {$paperCode}",
            "You have been assigned to review paper {$paperCode}: {$titleEn}",
            $paperId, 'both'
        );
    }

    public static function reviewResultAvailable(int $authorId, string $paperCode, string $decision, int $paperId): void
    {
        self::create($authorId, 'review_result',
            'ผลการพิจารณาพร้อมแล้ว',
            'Review Result Available',
            "บทความ {$paperCode} มีผลการพิจารณาแล้ว: {$decision}",
            "Review result for paper {$paperCode}: {$decision}",
            $paperId, 'both'
        );
    }

    public static function paperAccepted(int $authorId, string $paperCode, int $paperId): void
    {
        self::create($authorId, 'accepted',
            'บทความได้รับการยอมรับ',
            'Paper Accepted',
            "ยินดีด้วย! บทความ {$paperCode} ได้รับการยอมรับ",
            "Congratulations! Your paper {$paperCode} has been accepted.",
            $paperId, 'both'
        );
    }

    public static function paperPublished(int $authorId, string $paperCode, int $paperId): void
    {
        self::create($authorId, 'published',
            'บทความถูกเผยแพร่แล้ว',
            'Paper Published',
            "บทความ {$paperCode} ถูกเผยแพร่ในระบบเรียบร้อยแล้ว",
            "Your paper {$paperCode} has been published in the repository.",
            $paperId, 'both'
        );
    }

    public static function revisionRequired(int $authorId, string $paperCode, int $paperId): void
    {
        self::create($authorId, 'revision_required',
            'ต้องการแก้ไขบทความ',
            'Revision Required',
            "บทความ {$paperCode} ต้องการการแก้ไข กรุณาตรวจสอบความเห็นของผู้ทรงคุณวุฒิ",
            "Paper {$paperCode} requires revision. Please review the comments.",
            $paperId, 'both'
        );
    }
}
