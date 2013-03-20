<?php
class Email
{
	public static function send($email, $subject, $body)
	{
		global $emailsmtp, $emailusername, $emailpassword, $sentfromemail, $sentfromdomain, $baseDir;
		$mail = new PHPMailer();
		$mail->isSMTP();
		$mail->SMTPDebug = 0;
		$mail->SMTPAuth = true;
		$mail->SMTPSecure = "ssl";
		$mail->Host = $emailsmtp;
		$mail->Port = "465";
		$mail->Username = $emailusername;
		$mail->Password = $emailpassword;
		$mail->SetFrom($sentfromemail, $sentfromdomain);
		$mail->Subject = $subject;
		$mail->Body = $body;
		$mail->AddAddress($email);
		if (!$mail->Send()) {
			echo "Mail error: " . $mail->ErrorInfo;
		}
		else
		{
			return "Success";
		}
	}
}
