<?php
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'unlcp001@gmail.com';
        $mail->Password = 'dltc bjis hcaa tpze'; // Thay app password thật
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // ⭐ THÊM 2 DÒNG NÀY ĐỂ TẮT SSL VERIFICATION
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Tắt debug (sau khi test xong)
        $mail->SMTPDebug = 0;

        // Người gửi và người nhận
        $mail->setFrom('trieund002@gmail.com', 'Website Của Bạn');
        $mail->addAddress($to);

        // Nội dung email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Lỗi gửi email: " . $mail->ErrorInfo);
        return false;
    }
}
