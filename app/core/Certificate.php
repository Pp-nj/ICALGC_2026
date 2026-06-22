<?php
/**
 * Certificate - PDF generation using mPDF with Thai font support
 */

namespace App\Core;

use Mpdf\Mpdf;
use Mpdf\MpdfException;
use App\Core\Database;

class Certificate
{
    private const CERT_TYPES = ['attendance', 'presentation', 'reviewer', 'acceptance'];

    public static function generate(
        string $certType,
        int    $userId,
        string $recipientName,
        ?int   $paperId   = null,
        string $paperTitle = ''
    ): ?string {
        if (!in_array($certType, self::CERT_TYPES)) return null;

        $db  = Database::getInstance();
        $dir = CERT_PATH;

        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename  = strtoupper($certType) . '_' . $userId . '_' . time() . '.pdf';
        $filepath  = $dir . '/' . $filename;
        $relPath   = 'certificates/' . $filename;

        $html = self::buildHtml($certType, $recipientName, $paperTitle);

        try {
            $mpdf = new Mpdf([
                'mode'          => 'utf-8',
                'format'        => 'A4-L', // Landscape
                'margin_top'    => 20,
                'margin_bottom' => 20,
                'margin_left'   => 20,
                'margin_right'  => 20,
                'default_font'  => 'THSarabunNew',
            ]);

            // Add Thai font (bundled with mPDF or custom)
            $mpdf->SetDisplayMode('fullpage');
            $mpdf->WriteHTML($html);
            $mpdf->Output($filepath, 'F');

            // Save record to DB
            $check = $db->prepare(
                "SELECT id FROM certificates WHERE cert_type = :ct AND user_id = :uid AND (paper_id = :pid OR (:pid IS NULL AND paper_id IS NULL))"
            );
            $check->execute([':ct' => $certType, ':uid' => $userId, ':pid' => $paperId]);
            $existing = $check->fetch();

            if ($existing) {
                $upd = $db->prepare("UPDATE certificates SET recipient_name = :rn, generated_at = NOW(), pdf_path = :pp WHERE id = :id");
                $upd->execute([':rn' => $recipientName, ':pp' => $relPath, ':id' => $existing['id']]);
            } else {
                $ins = $db->prepare(
                    "INSERT INTO certificates (cert_type, user_id, paper_id, recipient_name, pdf_path)
                     VALUES (:ct, :uid, :pid, :rn, :pp)"
                );
                $ins->execute([
                    ':ct'  => $certType,
                    ':uid' => $userId,
                    ':pid' => $paperId,
                    ':rn'  => $recipientName,
                    ':pp'  => $relPath,
                ]);
            }

            return $relPath;

        } catch (MpdfException $e) {
            error_log('Certificate generation error: ' . $e->getMessage());
            return null;
        }
    }

    private static function buildHtml(string $type, string $name, string $paperTitle): string
    {
        $confName = CONF_NAME_EN;
        $confDate = CONF_DATE_EN;
        $venue    = CONF_VENUE_EN;

        $typeLabel = match($type) {
            'attendance'   => 'Certificate of Attendance',
            'presentation' => 'Certificate of Presentation',
            'reviewer'     => 'Certificate of Reviewer',
            'acceptance'   => 'Certificate of Acceptance',
            default        => 'Certificate',
        };

        $body = match($type) {
            'attendance' => "has attended the",
            'presentation' => "has presented a paper entitled<br><em>\"{$paperTitle}\"</em><br>at the",
            'reviewer' => "has served as a peer reviewer for the",
            'acceptance' => "has had the paper entitled<br><em>\"{$paperTitle}\"</em><br>accepted for the",
            default => "has participated in the",
        };

        $logoPath = PUBLIC_PATH . '/assets/images/logo-swu.png';
        $logoData = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : '';
        $logoTag  = $logoData ? "<img src='{$logoData}' style='height:60px;'>" : '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<style>
  @page { size: A4 landscape; margin: 15mm; }
  body {
    font-family: THSarabunNew, 'Sarabun', Arial, sans-serif;
    background: #fff;
    color: #222;
  }
  .cert-border {
    border: 8px double #c9a227;
    padding: 30px;
    min-height: 160mm;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    background: linear-gradient(135deg, #f9f6ee 0%, #fff 50%, #f9f6ee 100%);
  }
  .cert-logo { margin-bottom: 15px; }
  .cert-type { font-size: 36px; font-weight: bold; color: #003087; letter-spacing: 3px; margin: 10px 0; }
  .cert-presented { font-size: 16px; color: #555; margin: 10px 0; }
  .cert-name { font-size: 42px; font-weight: bold; color: #c9a227; border-bottom: 2px solid #c9a227; padding: 5px 30px; margin: 15px 0; }
  .cert-body { font-size: 18px; color: #333; line-height: 1.7; margin: 10px 0; }
  .cert-conf { font-size: 20px; font-weight: bold; color: #003087; margin: 10px 0; }
  .cert-date { font-size: 16px; color: #555; margin: 8px 0; }
  .cert-sigs { display: table; width: 100%; margin-top: 40px; }
  .cert-sig { display: table-cell; text-align: center; width: 50%; padding: 0 20px; }
  .cert-sig-line { border-top: 1px solid #555; padding-top: 8px; margin-top: 10px; font-size: 14px; color: #555; }
  .cert-corner { position: absolute; font-size: 40px; color: rgba(201,162,39,.15); font-weight: bold; }
</style>
</head>
<body>
<div class='cert-border'>
  <div class='cert-logo'>{$logoTag}</div>
  <div class='cert-type'>{$typeLabel}</div>
  <div class='cert-presented'>This is to certify that</div>
  <div class='cert-name'>{$name}</div>
  <div class='cert-body'>{$body}</div>
  <div class='cert-conf'>{$confName}</div>
  <div class='cert-date'>{$confDate} | {$venue}</div>
  <div class='cert-sigs'>
    <div class='cert-sig'>
      <div style='margin-bottom:30px;'>&nbsp;</div>
      <div class='cert-sig-line'>
        Assoc. Prof. Dr. Natacha Sriuranpong<br>
        Dean, Faculty of Humanities<br>
        Srinakharinwirot University
      </div>
    </div>
    <div class='cert-sig'>
      <div style='margin-bottom:30px;'>&nbsp;</div>
      <div class='cert-sig-line'>
        Conference Chair<br>
        ICALGC 2026
      </div>
    </div>
  </div>
</div>
</body>
</html>
HTML;
    }
}
